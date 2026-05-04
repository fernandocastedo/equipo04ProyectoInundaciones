<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Models\Inundacion;
use App\Models\ClimaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReporteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_uuid' => 'nullable|uuid',
            'citizen_carnet' => 'nullable|string',
            'lat_gps' => 'required|numeric',
            'long_gps' => 'required|numeric',
            'lat_reporte' => 'required|numeric',
            'long_reporte' => 'required|numeric',
            'intensidad_propuesta' => 'required|string|in:baja,media,alta',
            'foto_path' => 'nullable|string'
        ]);

        if (empty($data['user_uuid']) && empty($data['citizen_carnet'])) {
            return response()->json(['message' => 'Se requiere UUID o Carnet'], 400);
        }

        // Validación Haversine: max 500m
        $dist = $this->haversine($data['lat_gps'], $data['long_gps'], $data['lat_reporte'], $data['long_reporte']);
        if ($dist > 0.5) {
            return response()->json(['message' => 'El reporte no puede estar a más de 500 metros de su ubicación actual.'], 422);
        }

        // Identificador único del reportador (carnet tiene precedencia)
        $identifier = $data['citizen_carnet'] ?? $data['user_uuid'];
        $identifierColumn = !empty($data['citizen_carnet']) ? 'citizen_carnet' : 'user_uuid';

        // Buscar si ya tiene un reporte pendiente
        $reportePrevio = Reporte::where($identifierColumn, $identifier)
            ->where('estado_validacion', 'pendiente')
            ->first();

        // Obtener datos del clima Open-Meteo
        $weatherData = $this->fetchWeatherData($data['lat_reporte'], $data['long_reporte']);

        if ($reportePrevio) {
            // Actualizar reporte
            $reportePrevio->update([
                'lat_gps' => $data['lat_gps'],
                'long_gps' => $data['long_gps'],
                'lat_reporte' => $data['lat_reporte'],
                'long_reporte' => $data['long_reporte'],
                'intensidad_propuesta' => $data['intensidad_propuesta'],
                'foto_path' => $data['foto_path'] ?? $reportePrevio->foto_path,
                'datos_clima_json' => $weatherData
            ]);
            $reporte = $reportePrevio;
            $message = 'Reporte actualizado correctamente';
        } else {
            // Crear nuevo reporte
            $reporte = Reporte::create([
                'user_uuid' => $data['user_uuid'] ?? null,
                'citizen_carnet' => $data['citizen_carnet'] ?? null,
                'lat_gps' => $data['lat_gps'],
                'long_gps' => $data['long_gps'],
                'lat_reporte' => $data['lat_reporte'],
                'long_reporte' => $data['long_reporte'],
                'intensidad_propuesta' => $data['intensidad_propuesta'],
                'foto_path' => $data['foto_path'] ?? null,
                'datos_clima_json' => $weatherData,
                'estado_validacion' => 'pendiente'
            ]);
            $message = 'Reporte creado exitosamente';
        }

        return response()->json([
            'message' => $message,
            'reporte' => $reporte
        ], $reportePrevio ? 200 : 201);
    }

    public function pending()
    {
        // Autoridad: listar reportes pendientes para validación
        return response()->json(Reporte::where('estado_validacion', 'pendiente')->latest()->get());
    }

    public function validateReport(Request $request, $id)
    {
        $reporte = Reporte::findOrFail($id);
        
        $data = $request->validate([
            'action' => 'required|in:vincular,crear,rechazar',
            'inundacion_id' => 'required_if:action,vincular|exists:inundaciones,id'
        ]);

        if ($data['action'] === 'rechazar') {
            $reporte->update(['estado_validacion' => 'rechazada']);
            return response()->json(['message' => 'Reporte rechazado']);
        }

        $puntos = $reporte->intensidad_propuesta === 'alta' ? 5 : ($reporte->intensidad_propuesta === 'media' ? 3 : 1);

        if ($data['action'] === 'crear') {
            // Crear nueva inundacion
            $inundacion = Inundacion::create([
                'latitud' => $reporte->lat_reporte,
                'longitud' => $reporte->long_reporte,
                'intensidad_actual' => $reporte->intensidad_propuesta,
                'puntos_quorum' => $puntos,
                'estado' => 'activa',
                'expira_at' => $this->calculateExpiration($reporte->intensidad_propuesta),
                'description' => 'Generada automáticamente a partir del reporte.',
                'citizen_carnet' => $reporte->citizen_carnet,
            ]);

            $reporte->update([
                'estado_validacion' => 'aprobada',
                'inundacion_id' => $inundacion->id
            ]);

            return response()->json(['message' => 'Nueva inundación creada', 'inundacion' => $inundacion]);
        }

        if ($data['action'] === 'vincular') {
            $inundacion = Inundacion::findOrFail($data['inundacion_id']);
            $inundacion->puntos_quorum += $puntos;
            
            // Escalamiento
            if ($inundacion->puntos_quorum >= 15) {
                $inundacion->intensidad_actual = 'alta';
                // Reset expira_at for alta if needed
                $inundacion->expira_at = now()->addDays(7);
            } elseif ($inundacion->puntos_quorum >= 6 && $inundacion->intensidad_actual === 'baja') {
                $inundacion->intensidad_actual = 'media';
                $inundacion->expira_at = now()->addHours(18);
            }
            
            $inundacion->save();

            $reporte->update([
                'estado_validacion' => 'aprobada',
                'inundacion_id' => $inundacion->id
            ]);

            return response()->json(['message' => 'Reporte vinculado exitosamente', 'inundacion' => $inundacion]);
        }
    }

    private function fetchWeatherData($lat, $lng)
    {
        try {
            $response = Http::timeout(5)->get("https://api.open-meteo.com/v1/forecast", [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'precipitation',
                'timezone' => 'auto'
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Silently fail or log
        }
        return null;
    }

    private function calculateExpiration($intensidad)
    {
        if ($intensidad === 'alta') return now()->addDays(7);
        if ($intensidad === 'media') return now()->addHours(18);
        return now()->addHours(5);
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
