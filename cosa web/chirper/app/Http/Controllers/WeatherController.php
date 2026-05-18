<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class WeatherController extends Controller
{
    /**
     * Proxy para obtener tiles de OpenWeatherMap.
     */
    public function getTile($layer, $z, $x, $y)
    {
        $allowedLayers = ['precipitation_new', 'clouds_new'];
        
        // Mapeo retrocompatible por si acaso
        if ($layer === 'precipitationIntensity') {
            $layer = 'precipitation_new';
        } elseif ($layer === 'cloudCover') {
            $layer = 'clouds_new';
        }

        if (!in_array($layer, $allowedLayers)) {
            abort(404);
        }

        $cacheKey = "openweathermap_tile_{$layer}_{$z}_{$x}_{$y}";

        if (Cache::has($cacheKey)) {
            $tileData = Cache::get($cacheKey);
            if ($tileData && $tileData !== 'EMPTY') {
                return Response::make(base64_decode($tileData), 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'public, max-age=1800'
                ]);
            }
        }

        $apiKey = env('OPENWEATHER_API_KEY');
        $tileData = null;

        if ($apiKey) {
            $url = "https://tile.openweathermap.org/map/{$layer}/{$z}/{$x}/{$y}.png?appid={$apiKey}";
            
            try {
                $response = Http::timeout(3)->get($url);
                
                if ($response->successful() && str_contains($response->header('Content-Type'), 'image/png')) {
                    $tileData = base64_encode($response->body());
                    Cache::put($cacheKey, $tileData, now()->addMinutes(30));
                }
            } catch (\Exception $e) {
                // Silencioso
            }
        }

        if (!$tileData) {
            $transparentPixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
            return response($transparentPixel, 200, ['Content-Type' => 'image/png']);
        }

        return Response::make(base64_decode($tileData), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=1800'
        ]);
    }
}
