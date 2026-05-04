<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CentroAsistencia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CentroAsistenciaController extends Controller
{
    /**
     * Retorna la lista de centros de asistencia.
     * Si fuera a crecer mucho, consideraríamos paginación, pero para visualización de mapas solemos retornar un batch completo.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CentroAsistencia::with('municipio.provincia');

        if ($request->filled('provincia')) {
            $query->whereHas('municipio.provincia', function ($q) use ($request) {
                $q->where('nombre', $request->provincia);
            });
        }

        if ($request->filled('municipio')) {
            $query->whereHas('municipio', function ($q) use ($request) {
                $q->where('nombre', $request->municipio);
            });
        }

        $centros = $query->get()->map(function ($centro) {
            $data = $centro->toArray();
            $data['provincia'] = $centro->municipio?->provincia?->nombre;
            $data['municipio'] = $centro->municipio?->nombre;
            return $data;
        });

        return response()->json([
            'data' => $centros
        ]);
    }

    /**
     * Almacena un nuevo centro de asistencia en la base de datos.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden crear centros de asistencia.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'provincia' => 'required|string|max:255',
            'municipio' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'hora_apertura' => 'required|date_format:H:i',
            'hora_cierre' => 'required|date_format:H:i',
            'contacto' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
        ]);

        $muni = \App\Models\Municipio::where('nombre', $validated['municipio'])
            ->whereHas('provincia', fn($q) => $q->where('nombre', $validated['provincia']))
            ->first();
            
        $validated['municipio_id'] = $muni?->id;
        unset($validated['provincia'], $validated['municipio']);

        // Guardamos
        $centro = CentroAsistencia::create($validated);

        return response()->json([
            'data' => $centro
        ], 201);
    }

    /**
     * Actualiza un centro de asistencia existente.
     */
    public function update(Request $request, $id_centro): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden modificar centros de asistencia.');
        }

        $centro = CentroAsistencia::findOrFail($id_centro);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'provincia' => 'sometimes|required|string|max:255',
            'municipio' => 'sometimes|required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'sometimes|required|numeric',
            'longitud' => 'sometimes|required|numeric',
            'hora_apertura' => 'sometimes|required|date_format:H:i',
            'hora_cierre' => 'sometimes|required|date_format:H:i',
            'contacto' => 'nullable|string|max:255',
            'encargado' => 'nullable|string|max:255',
        ]);

        if (isset($validated['municipio']) && isset($validated['provincia'])) {
            $muni = \App\Models\Municipio::where('nombre', $validated['municipio'])
                ->whereHas('provincia', fn($q) => $q->where('nombre', $validated['provincia']))
                ->first();
            $validated['municipio_id'] = $muni?->id;
        } elseif (isset($validated['municipio'])) {
            $muni = \App\Models\Municipio::where('nombre', $validated['municipio'])->first();
            $validated['municipio_id'] = $muni?->id;
        }
        
        unset($validated['provincia'], $validated['municipio']);

        $centro->fill($validated);
        
        // Actualizar manualmente la hora ya que desactivamos timestamps
        $centro->ultima_actualizacion = now();

        $centro->save();

        return response()->json([
            'data' => $centro
        ]);
    }

    /**
     * Elimina un centro de asistencia existente.
     */
    public function destroy(Request $request, $id_centro): JsonResponse
    {
        if (!$request->user()->isAuthority()) {
            abort(403, 'Solo administradores pueden eliminar centros de asistencia.');
        }

        $centro = CentroAsistencia::findOrFail($id_centro);
        $centro->delete();

        return response()->json([
            'message' => 'Centro eliminado correctamente'
        ]);
    }
}
