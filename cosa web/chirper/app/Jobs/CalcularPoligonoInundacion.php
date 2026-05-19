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
    private const ELEVATION_TOLERANCE_M = 2.0;

    public function __construct(
        private readonly int $inundacionId
    ) {}

    public function handle(ElevationController $elevationService): void
    {
        $inundacion = Inundacion::find($this->inundacionId);

        if ($inundacion === null) {
            Log::warning("CalcularPoligonoInundacion: Inundacion #{$this->inundacionId} no encontrada.");
            return;
        }

        // No sobreescribir polígonos editados manualmente por autoridades
        if ($inundacion->polygon_editado_autoridad) {
            Log::info("CalcularPoligonoInundacion: #{$this->inundacionId} tiene polígono manual, se omite recálculo.");
            return;
        }

        // Solo calcular para inundaciones activas
        if ($inundacion->estado !== Inundacion::ESTADO_ACTIVA) {
            return;
        }

        $centroLat = (float) $inundacion->latitud;
        $centroLng = (float) $inundacion->longitud;

        if ($centroLat === 0.0 && $centroLng === 0.0) {
            Log::warning("CalcularPoligonoInundacion: #{$this->inundacionId} tiene coordenadas nulas.");
            return;
        }

        Log::info("CalcularPoligonoInundacion: Calculando polígono para #{$this->inundacionId} en ({$centroLat}, {$centroLng}).");

        // 1. Generar todos los puntos (centro + anillos)
        $candidatePoints = $this->generateCandidatePoints($centroLat, $centroLng);

        // 2. Construir la lista de puntos para la API (centro primero)
        $allPointsForApi = array_merge(
            ["{$centroLat},{$centroLng}"],
            array_map(fn ($p) => "{$p['lat']},{$p['lng']}", $candidatePoints)
        );

        // 3. Consultar elevaciones en batch
        try {
            $elevations = $elevationService->fetchElevations($allPointsForApi);
        } catch (\Exception $e) {
            Log::error("CalcularPoligonoInundacion: Error al consultar elevaciones para #{$this->inundacionId}.", [
                'error' => $e->getMessage(),
            ]);
            $this->release(60); // Reintentar en 60 segundos
            return;
        }

        // 4. Obtener la elevación del centroide (primer resultado)
        $centroElevation = $elevations[0]['elevation'] ?? null;

        if ($centroElevation === null) {
            Log::warning("CalcularPoligonoInundacion: No se pudo obtener la elevación del centroide #{$this->inundacionId}.");
            // Usar un polígono circular de fallback si no hay datos de elevación
            $polygon = $this->buildCircularFallback($centroLat, $centroLng, 150);
            $this->savePolygon($inundacion, $polygon);
            return;
        }

        // 5. Filtrar puntos por elevación (agua fluye hacia abajo)
        $validPoints = [['lat' => $centroLat, 'lng' => $centroLng]]; // El centro siempre está incluido

        foreach ($candidatePoints as $idx => $point) {
            $elev = $elevations[$idx + 1]['elevation'] ?? null; // +1 porque índice 0 es el centro

            if ($elev === null) {
                continue; // Si no hay dato, excluir el punto por seguridad
            }

            // El agua fluye hacia puntos más bajos o igual de altos (con tolerancia)
            if ($elev <= ($centroElevation + self::ELEVATION_TOLERANCE_M)) {
                $validPoints[] = ['lat' => $point['lat'], 'lng' => $point['lng']];
            }
        }

        // 6. Si hay muy pocos puntos válidos, usar los del anillo más cercano como mínimo
        if (count($validPoints) < 4) {
            Log::info("CalcularPoligonoInundacion: Pocos puntos válidos para #{$this->inundacionId}, usando fallback circular.");
            $polygon = $this->buildCircularFallback($centroLat, $centroLng, self::RINGS_METERS[0]);
        } else {
            // 7. Ordenar puntos por ángulo para formar un polígono válido (convex hull simplificado)
            $polygon = $this->sortPointsByAngle($validPoints, $centroLat, $centroLng);
        }

        // 8. Guardar el polígono
        $this->savePolygon($inundacion, $polygon);

        Log::info("CalcularPoligonoInundacion: Polígono guardado para #{$this->inundacionId} con " . count($polygon) . " vértices.");
    }

    /**
     * Genera los puntos candidatos en anillos concéntricos alrededor del centroide.
     *
     * @return array<int, array{lat: float, lng: float, ring: int}>
     */
    private function generateCandidatePoints(float $centroLat, float $centroLng): array
    {
        $points = [];

        foreach (self::RINGS_METERS as $ringIdx => $distanceMeters) {
            for ($i = 0; $i < self::POINTS_PER_RING; $i++) {
                $angleDeg = ($i / self::POINTS_PER_RING) * 360.0;
                [$lat, $lng] = $this->offsetPoint($centroLat, $centroLng, $distanceMeters, $angleDeg);
                $points[] = ['lat' => $lat, 'lng' => $lng, 'ring' => $ringIdx];
            }
        }

        return $points;
    }

    /**
     * Calcula un punto a una distancia y ángulo dado desde el origen.
     * Usa conversión esférica simple (suficientemente precisa a escala de 500m).
     *
     * @return array{float, float} [lat, lng]
     */
    private function offsetPoint(float $lat, float $lng, float $distanceMeters, float $angleDeg): array
    {
        $earthRadius = 6371000.0; // metros
        $angleRad    = deg2rad($angleDeg);

        $dLat = ($distanceMeters * cos($angleRad)) / $earthRadius;
        $dLng = ($distanceMeters * sin($angleRad)) / ($earthRadius * cos(deg2rad($lat)));

        return [
            $lat + rad2deg($dLat),
            $lng + rad2deg($dLng),
        ];
    }

    /**
     * Ordena los puntos por ángulo polar alrededor del centroide.
     * Esto garantiza que el polígono dibujado sea válido (sin auto-intersecciones).
     *
     * @param  array<int, array{lat: float, lng: float}>  $points
     * @return array<int, array{float, float}>  Array de [lat, lng] ordenado
     */
    private function sortPointsByAngle(array $points, float $centroLat, float $centroLng): array
    {
        usort($points, function (array $a, array $b) use ($centroLat, $centroLng): int {
            $angleA = atan2($a['lat'] - $centroLat, $a['lng'] - $centroLng);
            $angleB = atan2($b['lat'] - $centroLat, $b['lng'] - $centroLng);
            return $angleA <=> $angleB;
        });

        return array_map(fn ($p) => [$p['lat'], $p['lng']], $points);
    }

    /**
     * Construye un polígono circular de fallback cuando no hay datos de elevación.
     *
     * @return array<int, array{float, float}>
     */
    private function buildCircularFallback(float $centroLat, float $centroLng, float $radiusMeters): array
    {
        $points = [];
        $numPoints = 12; // Dodecágono — suficientemente circular

        for ($i = 0; $i < $numPoints; $i++) {
            $angle = ($i / $numPoints) * 360.0;
            [$lat, $lng] = $this->offsetPoint($centroLat, $centroLng, $radiusMeters, $angle);
            $points[] = [$lat, $lng];
        }

        return $points;
    }

    /**
     * Persiste el polígono calculado en la base de datos.
     *
     * @param  array<int, array{float, float}>  $polygon
     */
    private function savePolygon(Inundacion $inundacion, array $polygon): void
    {
        $inundacion->polygon_coords         = $polygon;
        $inundacion->polygon_calculado_at   = now();
        $inundacion->saveQuietly(); // sin disparar eventos adicionales
    }
}
