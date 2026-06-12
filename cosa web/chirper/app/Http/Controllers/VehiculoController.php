<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehiculo;
use App\Models\User;
use App\Models\CentroAsistencia;

class VehiculoController extends Controller
{
    public function index()
    {
        $vehiculos = Vehiculo::with(['encargado', 'centroAsistencia'])->get();
        $usuarios = User::where('role', User::ROLE_AUTHORITY)->get();
        $centros = CentroAsistencia::all();

        return view('vehiculos.index', compact('vehiculos', 'usuarios', 'centros'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'placa' => 'required|string|unique:vehiculos',
            'tipo' => 'required|in:ambulancia,camion_rescate,camioneta',
            'capacidad' => 'nullable|integer',
            'estado' => 'required|in:activo,inactivo,mantenimiento',
            'encargado_carnet' => 'nullable|exists:users,carnet',
            'centro_asistencia_id' => 'nullable|exists:centros_asistencia,id_centro',
        ]);

        Vehiculo::create($request->all());

        return redirect()->route('vehiculos.index')->with('success', 'Vehículo registrado correctamente.');
    }

    public function mapa()
    {
        return view('vehiculos.mapa');
    }

    public function activos()
    {
        $vehiculos = Vehiculo::with(['encargado'])
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get()
            ->map(function ($vehiculo) {
                return [
                    'id' => $vehiculo->id,
                    'placa' => $vehiculo->placa,
                    'tipo' => $vehiculo->tipo,
                    'latitud' => $vehiculo->latitud,
                    'longitud' => $vehiculo->longitud,
                    'estado' => $vehiculo->estado,
                    'en_ruta' => $vehiculo->en_ruta,
                    'ultima_ubicacion' => $vehiculo->ultima_ubicacion_at ? $vehiculo->ultima_ubicacion_at->diffForHumans() : 'Desconocido',
                    'conductor' => $vehiculo->encargado ? $vehiculo->encargado->name : 'Sin asignar'
                ];
            });

        return response()->json($vehiculos);
    }
}
