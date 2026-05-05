@extends('layouts.app')

@section('content')
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Registro de Inundaciones</h1>
            <p class="mt-1 text-sm text-gray-600">Listado de eventos de inundación consolidados.</p>
        </div>
        <a href="{{ route('reports.create', [], false) }}" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            Nuevo reporte
        </a>
    </div>

    @if (session('error') || !empty($error))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ session('error') ?? $error ?? '' }}
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════
         TABLA: Inundaciones Activas / Pasadas
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm mb-10">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Inundaciones Activas / Pasadas</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="text-left font-medium px-3 py-2">ID</th>
                    <th class="text-left font-medium px-3 py-2">Intensidad</th>
                    <th class="text-left font-medium px-3 py-2">Estado</th>
                    <th class="text-left font-medium px-3 py-2">Quórum</th>
                    <th class="text-left font-medium px-3 py-2">Creado</th>
                    <th class="text-left font-medium px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($reports as $inundacion)
                    @php($id = data_get($inundacion, 'id'))
                    @php($estado = data_get($inundacion, 'estado', ''))
                    @php($int = data_get($inundacion, 'intensidad_calculada', null))
                    @php($quorum = data_get($inundacion, 'quorum_total', 0))
                    @php($confirmada = data_get($inundacion, 'esta_confirmada', false))
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails({{ $id }})">
                        <td class="px-3 py-2 font-medium">#{{ $id }}</td>
                        <td class="px-3 py-2">
                            @if($int)
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $int === 'alta' ? 'bg-red-100 text-red-700' : ($int === 'media' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                    {{ ucfirst($int) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500">
                                    Sin datos
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                {{ $estado === 'activa' ? 'bg-blue-100 text-blue-700' : ($estado === 'terminada' ? 'bg-gray-100 text-gray-600' : 'bg-red-100 text-red-700') }}">
                                {{ ucfirst($estado) }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="{{ $confirmada ? 'text-green-700 font-semibold' : 'text-gray-500' }}">
                                {{ $quorum }} pts
                                @if($confirmada) ✓ @endif
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ data_get($inundacion, 'created_at', '') ? \Carbon\Carbon::parse(data_get($inundacion, 'created_at'))->format('d/m/Y H:i') : '' }}</td>
                        <td class="px-3 py-2 text-right" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Botón Desactivar (solo autoridad y solo si está activa) --}}
                                @if(isset($role) && $role === 'authority' && $estado === 'activa')
                                    <form method="POST" action="{{ route('reports.desactivar', ['id' => $id], false) }}"
                                          onsubmit="return confirm('¿Desactivar la inundación #{{ $id }}? Pasará a estado Terminada.')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 hover:bg-orange-200 border border-orange-200 transition-colors"
                                                title="Marcar como terminada">
                                            Desactivar
                                        </button>
                                    </form>
                                @endif
                                <span class="text-gray-500 text-xs">▼ Ver reportes</span>
                            </div>
                        </td>
                    </tr>
                    <!-- Detalles colapsables -->
                    <tr id="details-{{ $id }}" class="hidden bg-gray-50">
                        <td colspan="6" class="p-4 border-b">
                            <h4 class="font-semibold text-sm mb-2 text-gray-700">Reportes Vinculados (dentro del TTL)</h4>
                            @php($reportesActivos = data_get($inundacion, 'reportes_activos', []))
                            @if(count($reportesActivos) > 0)
                                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                                    @foreach($reportesActivos as $rep)
                                        <li>
                                            GPS: {{ $rep['lat_reporte'] }}, {{ $rep['long_reporte'] }} —
                                            Propuso: <strong>{{ ucfirst($rep['intensidad_propuesta']) }}</strong>
                                            ({{ $rep['peso'] }} pts) — {{ $rep['created_at_human'] ?? '' }}
                                        </li>
                                    @endforeach
                                </ul>
                                @php($desglose = data_get($inundacion, 'desglose_puntos', []))
                                @if(!empty($desglose))
                                    <div class="mt-2 text-xs text-gray-500">
                                        Desglose: Alta: {{ $desglose['alta'] ?? 0 }} pts /
                                        Media: {{ $desglose['media'] ?? 0 }} pts /
                                        Baja: {{ $desglose['baja'] ?? 0 }} pts
                                    </div>
                                @endif
                            @else
                                <p class="text-xs text-gray-500">No hay reportes activos vinculados en las últimas 3h.</p>
                            @endif
                            <div class="mt-3">
                                <a class="text-blue-600 hover:text-blue-800 text-sm font-medium" href="{{ route('reports.show', ['id' => $id], false) }}">Ver Ficha Completa &rarr;</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-6 text-gray-600 text-center" colspan="6">No hay inundaciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php($currentPage = (int) ($meta['current_page'] ?? 1))
    @php($lastPage = (int) ($meta['last_page'] ?? 1))

    @if ($lastPage > 1)
        <div class="mt-5 mb-10 flex items-center justify-between text-sm">
            <div class="text-gray-600">Página {{ $currentPage }} de {{ $lastPage }}</div>
            <div class="flex items-center gap-3">
                @if ($currentPage > 1)
                    <a class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 border" href="{{ route('reports.index', ['page' => $currentPage - 1], false) }}">Anterior</a>
                @endif
                @if ($currentPage < $lastPage)
                    <a class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 border" href="{{ route('reports.index', ['page' => $currentPage + 1], false) }}">Siguiente</a>
                @endif
            </div>
        </div>
    @endif

    @if(isset($role) && $role === 'authority')

    {{-- ══════════════════════════════════════════════════════════════════
         PANEL: Reportes Pendientes de Validación
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="overflow-hidden rounded-lg border border-orange-200 bg-white shadow-sm mt-8">
        <div class="px-4 py-3 bg-orange-50 border-b border-orange-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-orange-800">Reportes Pendientes de Validación</h2>
            <a href="{{ route('maps.index') }}" class="text-sm bg-orange-600 text-white px-3 py-1 rounded hover:bg-orange-700">Validar en el Mapa</a>
        </div>
        <div class="p-4 space-y-4">
            @forelse ($reportesPendientes ?? [] as $rep)
                <div class="flex border border-orange-100 rounded-lg overflow-hidden bg-white shadow-sm">
                    <!-- Left: Mini Map -->
                    <div class="w-1/3 bg-gray-100 min-h-[160px]" id="minimap-{{ $rep->id }}"></div>

                    <!-- Right: Content & Actions -->
                    <div class="w-2/3 p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="font-semibold text-gray-800">Reporte Rápido #{{ $rep->id }}</h3>
                                <span class="text-xs text-gray-500">{{ $rep->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">Intensidad Propuesta: <span class="font-bold text-orange-600">{{ ucfirst($rep->intensidad_propuesta) }}</span></p>
                            <p class="text-xs text-gray-500 mt-1">Ubicación GPS: {{ $rep->lat_gps }}, {{ $rep->long_gps }}</p>
                        </div>

                        <div class="mt-4 flex items-center gap-2 flex-wrap">
                            <button onclick="validarRapido({{ $rep->id }}, 'crear')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 text-xs rounded font-medium shadow-sm">
                                Crear Nueva Inundación
                            </button>

                            @if(count($rep->cercanas ?? []) > 0)
                                <div class="flex border rounded border-blue-200">
                                    <select id="select-vincular-{{ $rep->id }}" class="text-xs border-0 py-1.5 pl-2 pr-6 bg-blue-50 text-blue-900 focus:ring-0">
                                        @foreach($rep->cercanas as $activa)
                                            <option value="{{ $activa->id }}">A Inundación #{{ $activa->id }}</option>
                                        @endforeach
                                    </select>
                                    <button onclick="validarRapido({{ $rep->id }}, 'vincular', document.getElementById('select-vincular-{{ $rep->id }}').value)" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 text-xs rounded-r font-medium shadow-sm">
                                        Vincular
                                    </button>
                                </div>
                            @endif

                            <button onclick="validarRapido({{ $rep->id }}, 'rechazar')" class="ml-auto bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 text-xs rounded font-medium shadow-sm">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-600 text-center py-4">No hay reportes pendientes en este momento.</p>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         PANEL: Reportes Rechazados
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="overflow-hidden rounded-lg border border-red-200 bg-white shadow-sm mt-6">
        <div class="px-4 py-3 bg-red-50 border-b border-red-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-red-800">Reportes Rechazados</h2>
            <span class="text-xs text-red-500 font-medium">{{ count($reportesRechazados ?? []) }} registro(s)</span>
        </div>
        <div class="overflow-x-auto">
            @forelse ($reportesRechazados ?? [] as $rep)
                <div class="flex border-b border-red-100 last:border-0 hover:bg-red-50 transition-colors">

                    {{-- Foto (si existe) --}}
                    <div class="w-24 flex-shrink-0 flex items-center justify-center bg-gray-50 border-r border-red-100 min-h-[100px]">
                        @if($rep->foto_path)
                            <img src="{{ asset('storage/' . $rep->foto_path) }}"
                                 alt="Foto del reporte #{{ $rep->id }}"
                                 class="w-24 h-24 object-cover">
                        @else
                            <div class="text-center p-2">
                                <span class="text-gray-400 text-2xl block">📷</span>
                                <span class="text-gray-400 text-xs">Sin foto</span>
                            </div>
                        @endif
                    </div>

                    {{-- Datos --}}
                    <div class="flex-1 p-3 grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1 text-xs text-gray-700">
                        <div>
                            <span class="font-semibold text-gray-500 block">ID</span>
                            #{{ $rep->id }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">UUID Anónimo</span>
                            {{ $rep->user_uuid ?? '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">Carnet Ciudadano</span>
                            {{ $rep->citizen_carnet ?? '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">Lat. Reporte</span>
                            {{ $rep->lat_reporte }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">Long. Reporte</span>
                            {{ $rep->long_reporte }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">Intensidad Propuesta</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $rep->intensidad_propuesta === 'alta' ? 'bg-red-100 text-red-700' : ($rep->intensidad_propuesta === 'media' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ ucfirst($rep->intensidad_propuesta) }}
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 block">Creado</span>
                            {{ $rep->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="md:col-span-2">
                            <span class="font-semibold text-gray-500 block">Fecha de Rechazo</span>
                            {{ $rep->updated_at->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    {{-- Badge estado --}}
                    <div class="flex-shrink-0 flex items-center px-4">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-red-100 text-red-700">
                            Rechazado
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-gray-600 text-center py-6 text-sm">No hay reportes rechazados.</p>
            @endforelse
        </div>
    </div>

    @endif {{-- end authority --}}

    <script>
        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }

        function validarRapido(id, action, inundacion_id = null) {
            let body = { action: action };
            if (action === 'vincular') {
                if (!inundacion_id) return;
                body.inundacion_id = inundacion_id;
            }

            if (!confirm('¿Estás seguro de ' + action + ' este reporte?')) return;

            fetch('/api/reportes/' + id + '/validar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer {{ session('api_token') }}'
                },
                body: JSON.stringify(body)
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(() => {
                alert('Ocurrió un error al procesar la solicitud.');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if(isset($role) && $role === 'authority' && count($reportesPendientes ?? []) > 0)
                const pendingReports = @json($reportesPendientes);

                pendingReports.forEach(rep => {
                    const lat = parseFloat(rep.lat_gps);
                    const lng = parseFloat(rep.long_gps);
                    const mapId = 'minimap-' + rep.id;

                    if (document.getElementById(mapId)) {
                        const map = L.map(mapId, {
                            zoomControl: false,
                            attributionControl: false
                        }).setView([lat, lng], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                        const customIcon = L.divIcon({
                            className: 'custom-leaflet-marker',
                            html: '<div style="background-color: #F59E0B; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>',
                            iconSize: [14, 14],
                            iconAnchor: [7, 7]
                        });

                        L.marker([lat, lng], { icon: customIcon }).addTo(map);
                    }
                });
            @endif
        });
    </script>
@endsection
