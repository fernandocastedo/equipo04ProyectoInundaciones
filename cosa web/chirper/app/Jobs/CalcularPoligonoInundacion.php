<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Controllers\ElevationController;
use App\Models\Inundacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job: CalcularPoligonoInundacion
 *
 * Calcula el polígono de área de inundación usando datos de elevación de
 * Open Topo Data. El agua fluye hacia zonas más bajas, por lo que el
 * polígono se expande en las direcciones donde la elevación es menor
 * o igual a la del centroide de la inundación.
 *
 * Algoritmo:
 *  1. Obtener el centroide de la inundación.
 *  2. Consultar la elevación del centroide.
 *  3. Generar N puntos en anillos concéntricos alrededor del centroide.
 *  4. Consultar la elevación de todos esos puntos (en un solo request batch).
 *  5. Retener solo los puntos con elevación ≤ centroide (el agua no sube cuestas).
 *  6. Añadir el centroide siempre al polígono.
 *  7. Ordenar los puntos por ángulo para construir un polígono convexo válido.
 *  8. Guardar el polígono en inundaciones.polygon_coords.
 *
 * El Job NO sobreescribe el polígono si la autoridad lo editó manualmente
 * (polygon_editado_autoridad = true).
 */
final class CalcularPoligonoInundacion implements ShouldQueue
{
    use Queueable;

    /** Número máximo de reintentos si la API externa falla */
    public int $tries = 3;

    /** Segundos a esperar antes de reintentar */
    public int $backoff = 30;

    /**
     * Distancias (en metros) a las que se generan los anillos de puntos.
     * El primer anillo define el tamaño mínimo de la zona de inundación,
     * el segundo la expansión hacia zonas más bajas.
     */
    private const RINGS_METERS = [75, 150, 250];

    /**
     * Número de puntos por anillo (distribuidos uniformemente en 360°).
     * 8 = cada 45°, suficiente para capturar las 4 direcciones cardinales + diagonales.
     */
    private const POINTS_PER_RING = 8;

    /**
     * Tolerancia de elevación en metros.
     * Un punto se incluye si su elevación <= centroide + tolerancia.
     * Permite que pequeñas áreas planas (canales, pavimento) se incluyan.
     */
    private const ELEVATION_TOLERANCE_M = 0.5;

    public function __construct(
        private readonly int $reporteId
    ) {}

    public function handle(ElevationController $elevationService): void
    {
        $reporte = \App\Models\Reporte::find($this->reporteId);

        if ($reporte === null) {
            Log::warning("CalcularPoligonoInundacion: Reporte #{$this->reporteId} no encontrado.");
            return;
        }

        // Si ya tiene polígono (ej. recalculado manualmente), evitar doble cálculo
        if (!empty($reporte->polygon_coords)) {
            Log::info("CalcularPoligonoInundacion: Reporte #{$this->reporteId} ya tiene polígono topográfico.");
            return;
        }

        $lat = (float) $reporte->lat_reporte;
        $lng = (float) $reporte->long_reporte;

        if ($lat === 0.0 && $lng === 0.0) {
            return;
        }

        $intensidad = $reporte->intensidad_propuesta ?? 'media';
        
        $radius = match ($intensidad) {
            'alta' => 60.0,
            'media' => 35.0,
            'baja' => 15.0,
            default => 35.0,
        };

        Log::info("CalcularPoligonoInundacion: Calculando topografía para Reporte #{$this->reporteId}. Radio: {$radius}m");

        $candidatePoints = [];
        for ($i = 0; $i < self::POINTS_PER_RING; $i++) {
            $angleDeg = ($i / self::POINTS_PER_RING) * 360.0;
            [$cLat, $cLng] = $this->offsetPoint($lat, $lng, $radius, $angleDeg);
            $candidatePoints[] = [
                'lat' => $cLat,
                'lng' => $cLng,
            ];
        }

        // Preparar batch: [Centro] + [8 Candidatos] = 9 puntos
        $apiPoints = ["{$lat},{$lng}"];
        foreach ($candidatePoints as $cp) {
            $apiPoints[] = "{$cp['lat']},{$cp['lng']}";
        }

        try {
            $elevations = $elevationService->fetchElevations($apiPoints);
        } catch (\Exception $e) {
            Log::error("CalcularPoligonoInundacion: Error API para Reporte #{$this->reporteId}: " . $e->getMessage());
            $this->release(60);
            return;
        }

        $originElevation = $elevations[0]['elevation'] ?? null;
        if ($originElevation === null) {
            Log::warning("CalcularPoligonoInundacion: Sin elevación central para Reporte #{$this->reporteId}.");
            return;
        }

        $waterPoints = [['lat' => $lat, 'lng' => $lng]];

        foreach ($candidatePoints as $idx => $cp) {
            $candidateElevation = $elevations[$idx + 1]['elevation'] ?? null;
            if ($candidateElevation === null) {
                continue;
            }

            // El agua fluye si el terreno es más bajo o igual
            if ($candidateElevation <= ($originElevation + self::ELEVATION_TOLERANCE_M)) {
                $waterPoints[] = ['lat' => $cp['lat'], 'lng' => $cp['lng']];
            }
        }

        $polygon = $this->calculateConvexHull($waterPoints);

        // Fallback si es un hueco (menos de 3 puntos)
        if (count($polygon) < 3) {
            // Un radio pequeño fijo para el fallback del reporte
            $polygon = $this->buildCircularFallback($lat, $lng, $radius);
        }

        $reporte->update(['polygon_coords' => $polygon]);

        Log::info("CalcularPoligonoInundacion: Topografía guardada para Reporte #{$this->reporteId}.");
    }

    /**
     * Calcula el Casco Convexo (Convex Hull) de un conjunto de puntos usando
     * el algoritmo Monotone Chain de Andrew (O(N log N)).
     *
     * @param array<int, array{lat: float, lng: float}> $points
     * @return array<int, array{float, float}> Polígono ordenado
     */
    private function calculateConvexHull(array $points): array
    {
        // Filtrar duplicados muy cercanos para mejorar la precisión del cálculo
        $unique = [];
        foreach ($points as $p) {
            $dup = false;
            foreach ($unique as $u) {
                $distLat = abs($p['lat'] - $u['lat']);
                $distLng = abs($p['lng'] - $u['lng']);
                if ($distLat < 0.00002 && $distLng < 0.00002) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                $unique[] = $p;
            }
        }

        $n = count($unique);
        if ($n <= 3) {
            return array_map(fn($p) => [$p['lat'], $p['lng']], $unique);
        }

        // Ordenar puntos por longitud (x), y por latitud (y) en caso de empate
        usort($unique, function (array $a, array $b): int {
            if ($a['lng'] != $b['lng']) {
                return $a['lng'] <=> $b['lng'];
            }
            return $a['lat'] <=> $b['lat'];
        });

        // Función del producto cruzado
        $cross = function (array $o, array $a, array $b): float {
            return ($a['lng'] - $o['lng']) * ($b['lat'] - $o['lat']) - ($a['lat'] - $o['lat']) * ($b['lng'] - $o['lng']);
        };

        // Construir casco inferior
        $lower = [];
        foreach ($unique as $p) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }

        // Construir casco superior
        $upper = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $p = $unique[$i];
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }

        // Eliminar último punto de cada mitad (está duplicado en los extremos)
        array_pop($lower);
        array_pop($upper);

        $hull = array_merge($lower, $upper);
        return array_map(fn($p) => [$p['lat'], $p['lng']], $hull);
    }

    /**
     * Calcula un nuevo punto (lat, lng) dado un origen, una distancia y un ángulo.
     */
    private function offsetPoint(float $lat, float $lng, float $distanceMeters, float $bearingDegrees): array
    {
        $rEarth = 6378137.0; // Radio de la tierra en metros
        $brng = deg2rad($bearingDegrees);
        $lat1 = deg2rad($lat);
        $lon1 = deg2rad($lng);

        $lat2 = asin(sin($lat1) * cos($distanceMeters / $rEarth) +
                     cos($lat1) * sin($distanceMeters / $rEarth) * cos($brng));
        $lon2 = $lon1 + atan2(sin($brng) * sin($distanceMeters / $rEarth) * cos($lat1),
                              cos($distanceMeters / $rEarth) - sin($lat1) * sin($lat2));

        return [rad2deg($lat2), rad2deg($lon2)];
    }

    /**
     * Construye un polígono circular de fallback cuando no hay suficientes datos de elevación.
     *
     * @return array<int, array{float, float}>
     */
    private function buildCircularFallback(float $centroLat, float $centroLng, float $radiusMeters): array
    {
        $points = [];
        $numPoints = 12; // Dodecágono

        for ($i = 0; $i < $numPoints; $i++) {
            $angle = ($i / $numPoints) * 360.0;
            [$lat, $lng] = $this->offsetPoint($centroLat, $centroLng, $radiusMeters, $angle);
            $points[] = [$lat, $lng];
        }

        return $points;
    }

}
