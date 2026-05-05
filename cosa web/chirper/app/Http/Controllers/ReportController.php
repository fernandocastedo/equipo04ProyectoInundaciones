<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inundacion;
use App\Services\FloodApiClient;
use App\Services\FloodApiExceptions\ApiRequestException;
use App\Services\FloodApiExceptions\ApiUnauthorizedException;
use App\Services\FloodApiExceptions\ApiValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ReportController
{
    public function __construct(private readonly FloodApiClient $api)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');
        $page = max(1, (int) $request->query('page', '1'));

        $query = Inundacion::query()->latest();

        $reports = $query->with('reportes')->paginate(15, ['*'], 'page', $page);

        $reportesPendientes = [];
        $reportesRechazados = [];
        if ($role === 'authority') {
            // Solo reportes SIN inundación asignada y en estado pendiente
            $reportesPendientes = \App\Models\Reporte::whereNull('inundacion_id')
                ->where('estado_validacion', \App\Models\Reporte::VALIDACION_PENDIENTE)
                ->latest()
                ->get();

            // Reportes rechazados para el panel inferior
            $reportesRechazados = \App\Models\Reporte::where('estado_validacion', \App\Models\Reporte::VALIDACION_RECHAZADO)
                ->latest('updated_at')
                ->get();

            $activas = \App\Models\Inundacion::where('estado', 'activa')->get();

            foreach ($reportesPendientes as $rep) {
                $cercanas = [];
                foreach ($activas as $activa) {
                    $lat1 = deg2rad((float)$rep->lat_gps);
                    $lon1 = deg2rad((float)$rep->long_gps);
                    $lat2 = deg2rad((float)$activa->latitud);
                    $lon2 = deg2rad((float)$activa->longitud);
                    $dLat = $lat2 - $lat1;
                    $dLon = $lon2 - $lon1;
                    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
                    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                    $dist = 6371000 * $c;

                    if ($dist <= 300) {
                        $cercanas[] = $activa;
                    }
                }
                $rep->cercanas = collect($cercanas);
            }
        }

        return view('reports.index', [
            'reports'            => $reports->items(),
            'reportesPendientes' => $reportesPendientes,
            'reportesRechazados' => $reportesRechazados,
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
            ],
            'role' => $role,
        ]);
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'intensidad_actual' => ['required', 'string', 'in:baja,media,alta'],
            'provincia' => ['required', 'string'],
            'municipio' => ['required', 'string'],
        ]);

        try {
            $report = $this->api->createReport($token, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withInput()->withErrors([
                'description' => [$e->getMessage()],
            ]);
        }

        $id = Arr::get($report, 'id');

        return $id !== null
            ? redirect()->route('reports.show', ['id' => $id])
            : redirect()->route('reports.index');
    }

    public function show(Request $request, int|string $id): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        try {
            $report = $this->api->getReport($token, $id);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            abort($e->status, $e->getMessage());
        }

        return view('reports.show', [
            'report' => $report,
        ]);
    }

    public function storeResponse(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        try {
            $this->api->createResponse($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'message' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    public function updateestado(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        $data = $request->validate([
            // Estados normalizados (Opción 2): 'activa' | 'terminada' | 'falsa'
            'estado' => ['required', 'string', 'in:activa,terminada,falsa'],
        ]);

        try {
            $this->api->updateReport($token, $id, $data);
        } catch (ApiValidationException $e) {
            throw ValidationException::withMessages($e->errors);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return back()->withErrors([
                'estado' => [$e->getMessage()],
            ]);
        }

        return redirect()->route('reports.show', ['id' => $id]);
    }

    /**
     * Desactiva (termina) una inundación directamente desde el listado.
     * Llama a la API con estado='terminada' y redirige de vuelta al index.
     */
    public function desactivar(Request $request, int|string $id): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token', '');

        try {
            $this->api->updateReport($token, $id, ['estado' => 'terminada']);
        } catch (ApiUnauthorizedException) {
            $request->session()->forget(['api_token', 'api_user']);
            return redirect()->route('login');
        } catch (ApiRequestException $e) {
            return redirect()->route('reports.index')->withErrors([
                'estado' => 'No se pudo desactivar la inundación: ' . $e->getMessage(),
            ]);
        }

        return redirect()->route('reports.index')
            ->with('success', "Inundación #{$id} marcada como terminada correctamente.");
    }

    public function latestForNotifications(Request $request): JsonResponse
    {
        $user = (array) $request->session()->get('api_user', []);
        $role = (string) ($user['role'] ?? '');
        $carnet = (string) ($user['carnet'] ?? '');

        // Solo consultamos inundaciones activas; terminadas y falsas no generan notificaciones.
        $latest = Inundacion::where('estado', 'activa')->latest()->first();

        if (! $latest) {
            return response()->json(['data' => null], 200);
        }

        // intensidad_actual ya no existe en la BD; calculamos al vuelo.
        $latest->load('reportesActivosTTL');

        return response()->json([
            'data' => [
                'id'                   => (string) $latest->id,
                'intensidad_calculada' => $latest->intensidadCalculada(),
                'quorum_total'         => $latest->quorumTotal(),
                'esta_confirmada'      => $latest->estaConfirmada(),
            ],
        ]);
    }
}
