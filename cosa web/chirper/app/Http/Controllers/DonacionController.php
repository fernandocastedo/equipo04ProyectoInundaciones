<?php

namespace App\Http\Controllers;

use App\Models\Donacion;
use App\Models\CentroAsistencia;
use Illuminate\Http\Request;

class DonacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Donacion::with(['centro', 'donor'])->latest();

        // Filtro por Centro de Asistencia
        if ($request->filled('centro_id')) {
            $query->where('centro_id', $request->centro_id);
        }

        // Filtro por Estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro "Mis Donaciones" (si es ciudadano)
        if ($request->boolean('mine')) {
            $apiUser = (array) $request->session()->get('api_user', []);
            $myCarnet = $apiUser['carnet'] ?? null;
            if ($myCarnet) {
                $query->where('donor_carnet', $myCarnet);
            }
        }

        $donaciones = $query->paginate(15)->withQueryString();
        $centros = CentroAsistencia::orderBy('nombre')->get(['id_centro', 'nombre']);

        return view('donaciones.index', [
            'donaciones' => $donaciones,
            'centros'    => $centros,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'centro_id'         => 'required|exists:centros_asistencia,id_centro',
            'donor_carnet'      => 'nullable|string|exists:users,carnet',
            'items_description' => 'required|string',
            'is_anonymous'      => 'boolean',
        ]);

        $validated['is_anonymous'] = $request->boolean('is_anonymous');
        $validated['status'] = 'recibido'; // Por defecto al registrar
        
        Donacion::create($validated);

        return redirect()->route('donaciones.index')->with('success', 'Donación registrada exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $donacion = Donacion::findOrFail($id);

        $validated = $request->validate([
            'status'        => 'required|string|in:recibido,en_uso,entregado',
            'usage_details' => 'nullable|string',
        ]);

        $donacion->update($validated);

        return redirect()->route('donaciones.index')->with('success', 'Uso de la donación actualizado exitosamente.');
    }
}
