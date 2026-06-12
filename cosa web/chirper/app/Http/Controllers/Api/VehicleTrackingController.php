<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehiculo;

class VehicleTrackingController extends Controller
{
    public function ping(Request $request)
    {
        $request->validate([
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'en_ruta' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        $vehiculo = Vehiculo::where('encargado_carnet', $user->carnet)->first();

        if (!$vehiculo) {
            return response()->json(['error' => 'No tienes un vehículo asignado.'], 403);
        }

        // Update current location and timestamp
        $vehiculo->latitud = $request->latitud;
        $vehiculo->longitud = $request->longitud;
        $vehiculo->ultima_ubicacion_at = now();
        
        if ($request->has('en_ruta')) {
            $vehiculo->en_ruta = $request->en_ruta;
        }

        $vehiculo->save();

        // Only save history if en_ruta is true (as requested: track only when accepted route for help)
        if ($vehiculo->en_ruta) {
            $vehiculo->historialUbicaciones()->create([
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
            ]);
        }

        return response()->json([
            'message' => 'Ubicación actualizada correctamente.',
            'en_ruta' => $vehiculo->en_ruta
        ]);
    }
}
