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
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDetails({{ $id }})">
                        <td class="px-3 py-2 font-medium">#{{ $id }}</td>
                        <td class="px-3 py-2">
                            @php($int = data_get($inundacion, 'intensidad_actual', 'baja'))
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium 
                                {{ $int === 'alta' ? 'bg-red-100 text-red-700' : ($int === 'media' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ ucfirst($int) }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ ucfirst(data_get($inundacion, 'estado', '')) }}</td>
                        <td class="px-3 py-2">{{ data_get($inundacion, 'puntos_quorum', 0) }} pts</td>
                        <td class="px-3 py-2">{{ data_get($inundacion, 'created_at', '') ? \Carbon\Carbon::parse(data_get($inundacion, 'created_at'))->format('d/m/Y H:i') : '' }}</td>
                        <td class="px-3 py-2 text-right">
                            <span class="text-gray-500 text-xs">▼ Ver reportes</span>
                        </td>
                    </tr>
                    <!-- Detalles colapsables -->
                    <tr id="details-{{ $id }}" class="hidden bg-gray-50">
                        <td colspan="6" class="p-4 border-b">
                            <h4 class="font-semibold text-sm mb-2 text-gray-700">Reportes Rápidos Vinculados</h4>
                            @php($reportes = data_get($inundacion, 'reportes', []))
                            @if(count($reportes) > 0)
                                <ul class="list-disc pl-5 text-sm text-gray-600 space-y-1">
                                    @foreach($reportes as $rep)
                                        <li>Reporte GPS: {{ $rep['lat_reporte'] }}, {{ $rep['long_reporte'] }} - Propuso intensidad: {{ $rep['intensidad_propuesta'] }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-gray-500">No hay reportes rápidos vinculados a esta inundación.</p>
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
        <div class="overflow-hidden rounded-lg border border-orange-200 bg-white shadow-sm mt-8">
            <div class="px-4 py-3 bg-orange-50 border-b border-orange-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-orange-800">Reportes Rápidos Pendientes de Validación</h2>
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
                                                <option value="{{ $activa->id }}">A Inundación #{{ $activa->id }} ({{ ucfirst($activa->intensidad_actual) }})</option>
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
                    <p class="text-gray-600 text-center py-4">No hay reportes rápidos pendientes en este momento.</p>
                @endforelse
            </div>
        </div>
    @endif

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
                    'Accept': 'application/json', 'Authorization': 'Bearer {{ session('api_token') }}'
                },
                body: JSON.stringify(body)
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(err => {
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
