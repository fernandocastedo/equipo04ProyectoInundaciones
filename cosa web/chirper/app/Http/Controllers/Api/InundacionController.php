<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInundacionRequest;
use App\Http\Requests\Api\UpdateInundacionRequest;
use App\Http\Resources\InundacionResource;
use App\Models\Inundacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InundacionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Inundacion::query()->latest();

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

        $reports = $query->paginate(15);

        return InundacionResource::collection($reports);
    }

    public function store(StoreInundacionRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('create', Inundacion::class);

        $data = $request->validated();

        $muni = \App\Models\Municipio::where('nombre', $data['municipio'])
            ->whereHas('provincia', fn($q) => $q->where('nombre', $data['provincia']))
            ->first();

        $report = Inundacion::create([
            'validador_id' => $user->isAuthority() ? $user->carnet : null,
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud'],
            'municipio_id' => $muni?->id,
            'address' => $data['address'] ?? null,
            'description' => $data['description'],
            'intensidad_actual' => $data['intensidad_actual'],
            'estado' => 'activa',
        ]);

        return response()->json([
            'data' => new InundacionResource($report),
        ], 201);
    }

    public function show(Request $request, Inundacion $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['validador', 'reportes']);

        return response()->json([
            'data' => new InundacionResource($report),
        ]);
    }

    public function update(UpdateInundacionRequest $request, Inundacion $report): JsonResponse
    {
        $this->authorize('update', $report);

        $data = $request->validated();

        $user = $request->user();

        if (! $user->isAuthority() && array_key_exists('estado', $data)) {
            unset($data['estado']);
        }

        $report->fill($data);
        $report->save();

        if ($user->isAuthority()) {
            $report->load('reportes');
            foreach ($report->reportes as $rep) {
                if ($rep->citizen_carnet) {
                    $this->refreshCitizenBanStatus((string) $rep->citizen_carnet);
                }
            }
        }

        $report->load(['validador', 'reportes']);

        return response()->json([
            'data' => new InundacionResource($report),
        ]);
    }

    private function refreshCitizenBanStatus(string $citizenCarnet): void
    {
        $falseReportsCount = \App\Models\Reporte::query()
            ->where('citizen_carnet', $citizenCarnet)
            ->whereHas('inundacion', function($q) {
                $q->where('estado', 'falso_reporte');
            })
            ->count();

        User::query()
            ->where('carnet', $citizenCarnet)
            ->update([
                'is_banned' => $falseReportsCount >= 2,
            ]);
    }
}
