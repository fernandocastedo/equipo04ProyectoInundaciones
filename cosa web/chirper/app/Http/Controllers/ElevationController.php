<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ElevationController
 *
 * Proxy para la API pública de Open Topo Data (https://api.opentopodata.org).
 * Permite consultar la elevación (metros sobre el nivel del mar) de uno o
 * varios puntos geográficos, con caché de 24 horas para minimizar las
 * llamadas externas (la API pública tiene rate limit de ~1 req/s).
 *
 * Endpoint: GET /api/elevation?locations=lat1,lng1|lat2,lng2|...
 * Responde: { results: [{elevation: float, lat: float, lng: float}] }
 */
final class ElevationController extends Controller
{
    /** Dataset de Open Topo Data a usar (SRTM 30m — buena cobertura global) */
    private const DATASET = 'srtm30m';

    /** Máximo de puntos por request a la API externa */
    private const MAX_POINTS = 100;

    /** TTL del cache de elevación en horas (el terreno no cambia) */
    private const CACHE_TTL_HOURS = 24;

    /**
     * Consulta la elevación de uno o varios puntos.
     *
     * Query params:
     *   - locations: string "lat1,lng1|lat2,lng2|..." (pipe-separated)
     *
     * Retorna un array de resultados con elevation, lat, lng.
     */
    public function getElevation(Request $request): JsonResponse
    {
        $locationsStr = (string) $request->query('locations', '');

        if (empty($locationsStr)) {
            return response()->json(['error' => 'El parámetro locations es requerido.'], 422);
        }

        // Parsear los puntos "lat,lng|lat,lng|..."
        $rawPoints = array_filter(array_map('trim', explode('|', $locationsStr)));

        if (count($rawPoints) === 0) {
            return response()->json(['error' => 'No se proporcionaron puntos válidos.'], 422);
        }

        if (count($rawPoints) > self::MAX_POINTS) {
            return response()->json(['error' => 'Máximo ' . self::MAX_POINTS . ' puntos por request.'], 422);
        }

        try {
            $results = $this->fetchElevations($rawPoints);
            return response()->json(['results' => $results]);
        } catch (\Exception $e) {
            Log::error('ElevationController: Error al consultar Open Topo Data.', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo obtener la elevación del terreno.'], 503);
        }
    }

    /**
     * Obtiene las elevaciones para un array de strings "lat,lng".
     * Usa caché por punto individual para evitar reconsultar datos conocidos.
     *
     * @param  string[]  $rawPoints  ["lat,lng", "lat,lng", ...]
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    public function fetchElevations(array $rawPoints): array
    {
        $cacheHits   = [];
        $pointsToFetch = [];
        $indexMap    = []; // índice en $rawPoints => índice en $pointsToFetch

        // 1. Verificar caché para cada punto
        foreach ($rawPoints as $idx => $rawPoint) {
            $cacheKey = $this->buildCacheKey($rawPoint);
            $cached   = Cache::get($cacheKey);

            if ($cached !== null) {
                $cacheHits[$idx] = $cached;
            } else {
                $indexMap[count($pointsToFetch)] = $idx;
                $pointsToFetch[] = $rawPoint;
            }
        }

        // 2. Fetch de los puntos no cacheados en un solo request batch
        $freshResults = [];
        if (!empty($pointsToFetch)) {
            $freshResults = $this->callOpenTopoData($pointsToFetch);

            // Guardar en caché los nuevos resultados
            foreach ($freshResults as $fetchIdx => $result) {
                $rawIdx   = $indexMap[$fetchIdx] ?? $fetchIdx;
                $rawPoint = $rawPoints[$rawIdx] ?? $pointsToFetch[$fetchIdx];
                Cache::put($this->buildCacheKey($rawPoint), $result, now()->addHours(self::CACHE_TTL_HOURS));
            }
        }

        // 3. Reconstituir el array en el orden original
        $results = [];
        $freshIdx = 0;
        foreach (array_keys($rawPoints) as $idx) {
            if (isset($cacheHits[$idx])) {
                $results[] = $cacheHits[$idx];
            } else {
                $results[] = $freshResults[$freshIdx] ?? $this->parsePoint($rawPoints[$idx], null);
                $freshIdx++;
            }
        }

        return $results;
    }

    /**
     * Llama a la API de Open Topo Data con un lote de puntos.
     *
     * @param  string[]  $rawPoints
     * @return array<int, array{elevation: float|null, lat: float, lng: float}>
     */
    private function callOpenTopoData(array $rawPoints): array
    {
        $locationsStr = implode('|', $rawPoints);
        $url = "https://api.opentopodata.org/v1/" . self::DATASET;

        $response = Http::timeout(10)
            ->retry(2, 500)
            ->get($url, ['locations' => $locationsStr]);

        if (!$response->successful()) {
            Log::warning('ElevationController: Open Topo Data respondió con error.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            // Devolver null elevations para no bloquear el flujo
            return array_map(fn ($p) => $this->parsePoint($p, null), $rawPoints);
        }

        $data    = $response->json();
        $apiResults = $data['results'] ?? [];

        $parsed = [];
        foreach ($rawPoints as $idx => $rawPoint) {
            $apiResult = $apiResults[$idx] ?? null;
            $elevation = is_array($apiResult) ? ($apiResult['elevation'] ?? null) : null;
            $parsed[]  = $this->parsePoint($rawPoint, $elevation !== null ? (float) $elevation : null);
        }

        return $parsed;
    }

    /**
     * Construye la estructura de resultado para un punto.
     *
     * @return array{elevation: float|null, lat: float, lng: float}
     */
    private function parsePoint(string $rawPoint, ?float $elevation): array
    {
        $parts = explode(',', $rawPoint, 2);
        $lat   = isset($parts[0]) ? (float) trim($parts[0]) : 0.0;
        $lng   = isset($parts[1]) ? (float) trim($parts[1]) : 0.0;

        return ['elevation' => $elevation, 'lat' => $lat, 'lng' => $lng];
    }

    /**
     * Clave de caché para un punto "lat,lng" — redondeada a 4 decimales
     * (~11m de precisión, suficiente para elevación de terreno).
     */
    private function buildCacheKey(string $rawPoint): string
    {
        $parts = explode(',', $rawPoint, 2);
        $lat   = isset($parts[0]) ? round((float) trim($parts[0]), 4) : 0.0;
        $lng   = isset($parts[1]) ? round((float) trim($parts[1]), 4) : 0.0;

        return "elevation_srtm30m_{$lat}_{$lng}";
    }
}
