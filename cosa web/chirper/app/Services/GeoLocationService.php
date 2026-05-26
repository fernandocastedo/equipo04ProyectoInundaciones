<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Municipio;
use Illuminate\Support\Facades\Log;

/**
 * GeoLocationService
 *
 * Determina a qué municipio (y por ende provincia) pertenece un punto
 * geográfico (lat, lng) usando el archivo GeoJSON de límites municipales
 * del departamento de Santa Cruz.
 *
 * Estrategia:
 *  1. Carga y parsea el GeoJSON una sola vez por request (lazy singleton).
 *  2. Itera los features haciendo point-in-polygon (ray-casting algorithm).
 *  3. Normaliza el nombre obtenido del GeoJSON y lo compara contra la tabla
 *     `municipios` de la base de datos (nombres canónicos).
 *  4. Devuelve el modelo Municipio si lo encuentra, null si no.
 */
final class GeoLocationService
{
    /** @var array<int,array{name:string,province:string,rings:list<list<array{0:float,1:float}>>}>|null */
    private ?array $features = null;

    // ─────────────────────────────────────────────────────────────────────
    // API pública
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Dado un punto (lat, lng) devuelve el Municipio de la BD que contiene
     * ese punto, o null si no se puede determinar.
     */
    public function findMunicipio(float $lat, float $lng): ?Municipio
    {
        $this->loadFeatures();

        $rawName = $this->findFeatureName($lat, $lng);
        if ($rawName === null) {
            return null;
        }

        $normalized = $this->normalizeName($rawName);

        // Carga todos los municipios una sola vez y busca en memoria para
        // evitar N queries al usarlo en un loop (backfill).
        return Municipio::all()->first(function (Municipio $m) use ($normalized): bool {
            return $this->normalizeName($m->nombre) === $normalized;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Carga del GeoJSON
    // ─────────────────────────────────────────────────────────────────────

    private function loadFeatures(): void
    {
        if ($this->features !== null) {
            return;
        }

        $path = public_path('municipalities.geojson');

        if (! file_exists($path)) {
            Log::warning('GeoLocationService: municipalities.geojson no encontrado en ' . $path);
            $this->features = [];
            return;
        }

        $raw  = file_get_contents($path);
        $json = json_decode((string) $raw, true);

        if (! is_array($json) || empty($json['features'])) {
            Log::warning('GeoLocationService: GeoJSON inválido o sin features.');
            $this->features = [];
            return;
        }

        $this->features = [];

        foreach ($json['features'] as $feature) {
            $type = $feature['geometry']['type'] ?? '';
            $name = (string) ($feature['properties']['name'] ?? '');

            if ($type === 'Polygon') {
                $this->features[] = [
                    'name'  => $name,
                    'rings' => $feature['geometry']['coordinates'],
                ];
            } elseif ($type === 'MultiPolygon') {
                // MultiPolygon: cada elemento de coordinates es un Polygon
                foreach ($feature['geometry']['coordinates'] as $polygon) {
                    $this->features[] = [
                        'name'  => $name,
                        'rings' => $polygon,
                    ];
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Point-in-Polygon (Ray-Casting)
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Devuelve el nombre raw del feature GeoJSON que contiene el punto,
     * o null si ninguno lo contiene.
     *
     * El GeoJSON usa coordenadas [lng, lat] (estándar GeoJSON), así que
     * convertimos nuestro punto antes de comparar.
     */
    private function findFeatureName(float $lat, float $lng): ?string
    {
        foreach ($this->features as $feature) {
            // Primer ring = exterior; los demás son huecos (los ignoramos para
            // simplificar, ya que las municipalidades no tienen huecos relevantes).
            $ring = $feature['rings'][0] ?? [];

            if ($this->pointInPolygon($lng, $lat, $ring)) {
                return $feature['name'];
            }
        }

        return null;
    }

    /**
     * Algoritmo Ray-Casting para point-in-polygon.
     * @param  list<array{0:float,1:float}>  $polygon  Coordenadas [lng, lat]
     */
    private function pointInPolygon(float $x, float $y, array $polygon): bool
    {
        $count = count($polygon);
        if ($count < 3) {
            return false;
        }

        $inside = false;
        $j      = $count - 1;

        for ($i = 0; $i < $count; $i++) {
            $xi = (float) $polygon[$i][0];
            $yi = (float) $polygon[$i][1];
            $xj = (float) $polygon[$j][0];
            $yj = (float) $polygon[$j][1];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Normalización de nombres
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Elimina prefijos comunes ("Municipio ", "Provincia "), pasa a
     * minúsculas y aplica el mismo diccionario de correcciones que usa
     * el frontend (layouts/app.blade.php normalizeMuniName).
     */
    private function normalizeName(string $name): string
    {
        $n = preg_replace('/^(Municipio|Provincia)\s+/i', '', $name);
        $n = mb_strtolower(trim((string) $n));

        // Correcciones idénticas a las del frontend JS
        $corrections = [
            'ascención de guarayos' => 'ascensión de guarayos',
            'san antonio de lomerio' => 'san antonio de lomerío',
            'san rafael'             => 'san rafael de velasco',
            'charagua'               => 'charagua iyambae',
            'gutiérrez'              => 'kereimba iyaambae',
            'san juan'               => 'san juan de yapacaní',
            'porongo (ayacucho)'     => 'porongo',
        ];

        return $corrections[$n] ?? $n;
    }
}
