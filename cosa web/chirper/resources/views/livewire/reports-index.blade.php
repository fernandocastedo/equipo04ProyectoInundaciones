<div>
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
        }
        .glass-panel-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        }
        .glass-table th { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); }
        .glass-table tr:hover { background: rgba(255, 255, 255, 0.5); }
        .gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <!-- Main Container with custom gradient background -->
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50/50 to-teal-50/30 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        
        <div class="max-w-7xl mx-auto">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-10 gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight gradient-text">Registro de Inundaciones</h1>
                    <p class="mt-2 text-sm font-medium text-slate-500">Centro de Monitoreo y Validación de Eventos Hidrológicos.</p>
                </div>
                <a href="{{ route('reports.create', [], false) }}" class="group relative inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-2.5 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all duration-300 hover:-translate-y-0.5 overflow-hidden">
                    <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full transition-transform duration-500 ease-out -translate-x-full skew-x-12"></div>
                    <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span class="relative z-10">Nuevo Reporte</span>
                </a>
            </div>

    <!-- Map Filters -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200 mt-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Reportes por Ubicación</h3>
        <x-location-filter formAction="{{ route('reports.index', [], false) }}" />
    </div>

    <div class="relative mb-10">
        <div id="map-container" class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative z-0" style="height: 600px;">
            <div id="map" class="absolute inset-0 z-0"></div>
            
            <!-- Botón Pantalla Completa -->
            <button id="btn-fullscreen-map" class="absolute top-[80px] left-[10px] z-[1000] bg-white text-gray-700 p-1.5 rounded-[4px] shadow-[0_1px_5px_rgba(0,0,0,0.65)] hover:bg-gray-100 transition-colors" title="Pantalla Completa" onclick="toggleMapFullscreen()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
            </button>
            
            <!-- LEYENDA DEL RADAR (UI/UX) -->
            <div id="radar-legend" class="hidden absolute bottom-6 left-6 bg-white/95 backdrop-blur p-4 rounded-xl shadow-xl border border-gray-100 z-[1000] pointer-events-none transition-all duration-300">
                <h4 id="radar-legend-title" class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    <span>Intensidad de Lluvia</span>
                </h4>
                <div id="radar-legend-rain-colors" class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-md bg-blue-400 opacity-80 shadow-inner"></div>
                        <span class="text-xs text-gray-600 font-medium">Lluvia Débil / Llovizna</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-md bg-yellow-400 opacity-80 shadow-inner"></div>
                        <span class="text-xs text-gray-600 font-medium">Lluvia Moderada</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-md bg-red-500 opacity-80 shadow-inner"></div>
                        <span class="text-xs text-gray-600 font-medium">Tormenta Fuerte</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de Rutas Seguras -->
        <x-routing-panel />
    </div>


            @if (session('error') || !empty($error))
                <div class="mb-6 rounded-2xl border border-red-200/60 bg-red-50/80 backdrop-blur-sm p-4 text-sm shadow-sm animate-pulse flex items-center gap-3">
                    <span class="text-red-500 text-xl">⚠️</span>
                    <span class="text-red-800 font-medium">{{ session('error') ?? $error ?? '' }}</span>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-teal-200/60 bg-teal-50/80 backdrop-blur-sm p-4 text-sm shadow-sm flex items-center gap-3">
                    <span class="text-teal-500 text-xl"></span>
                    <span class="text-teal-800 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(count($misReportes ?? []) > 0 || (isset($role) && $role === 'citizen'))
                <div class="glass-panel rounded-3xl overflow-hidden mb-10">
                    <div class="px-6 py-5 border-b border-white/50 flex items-center justify-between bg-white/30">
                        <h2 class="text-xl font-semibold text-slate-800 flex items-center gap-2">
                            <span class="text-indigo-500">👤</span> Mis reportes enviados
                        </h2>
                        <span class="bg-indigo-100 text-indigo-700 py-1 px-3 rounded-full text-xs font-bold">{{ count($misReportes ?? []) }} registro(s)</span>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                            <thead class="text-slate-600">
                                <tr>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                    <th class="text-left font-semibold px-4 py-3">Estado</th>
                                    <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                    <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Actualización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50">
                                @forelse(($misReportes ?? []) as $rep)
                                    <tr class="transition-colors duration-200">
                                        <td class="px-4 py-3 font-semibold text-slate-700">#{{ $rep->id }}</td>
                                        <td class="px-4 py-3">
                                            @php($estadoVal = (string) $rep->estado_validacion)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold shadow-sm
                                                {{ $estadoVal === 'pendiente' ? 'bg-amber-100 text-amber-700' : ($estadoVal === 'aceptado' ? 'bg-teal-100 text-teal-700' : 'bg-rose-100 text-rose-700') }}">
                                                {{ ucfirst($estadoVal) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-600">{{ ucfirst((string) $rep->intensidad_propuesta) }}</td>
                                        <td class="px-4 py-3 text-slate-500">{{ optional($rep->updated_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-8 text-slate-500 text-center font-medium" colspan="4">Aún no has enviado reportes. ¡Tu reporte salva vidas!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Inundaciones Activas
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="glass-panel rounded-3xl overflow-hidden mb-12">
                <div class="px-6 py-5 border-b border-white/50 flex items-center justify-between bg-gradient-to-r from-blue-50/50 to-indigo-50/50">
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="text-blue-500"></span> Inundaciones Activas
                    </h2>
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold shadow-inner">
                        {{ count($inundacionesActivas) }}
                    </div>
                </div>
                <div class="p-4 flex flex-col gap-3">
                    @forelse ($inundacionesActivas as $inundacion)
                        @php($id = data_get($inundacion, 'id'))
                        @php($estado = data_get($inundacion, 'estado', ''))
                        @php($int = data_get($inundacion, 'intensidad_calculada', null))
                        @php($quorum = data_get($inundacion, 'quorum_total', 0))
                        @php($confirmada = data_get($inundacion, 'esta_confirmada', false))
                        
                        <div class="bg-white/40 border border-white/60 rounded-2xl overflow-hidden shadow-sm glass-panel-hover transition-all duration-300">
                            <!-- Header / Resumen -->
                            <div class="p-5 flex flex-wrap items-center justify-between gap-4 cursor-pointer hover:bg-white/30 transition-colors" onclick="toggleDetails({{ $id }})">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-700 font-bold text-lg shadow-sm">
                                        #{{ $id }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            @if($int)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider shadow-sm
                                                    {{ $int === 'alta' ? 'bg-rose-500 text-white' : ($int === 'media' ? 'bg-amber-400 text-amber-900' : 'bg-teal-400 text-teal-900') }}">
                                                    {{ $int }}
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 shadow-sm">
                                                {{ $estado }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-500">
                                            Creado el {{ data_get($inundacion, 'created_at', '') ? \Carbon\Carbon::parse(data_get($inundacion, 'created_at'))->format('d M, Y H:i') : '' }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">Quórum Global</p>
                                        <p class="text-lg font-extrabold {{ $confirmada ? 'text-teal-600' : 'text-slate-700' }}">
                                            {{ $quorum }} pts @if($confirmada) <span class="text-teal-500 ml-1">✓</span> @endif
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        @if(isset($role) && $role === 'authority' && $estado === 'activa')
                                            <button wire:click="desactivar({{ $id }})" wire:confirm="¿Desactivar la inundación #{{ $id }}? Pasará a estado Terminada." class="p-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-colors shadow-sm" title="Finalizar Inundación" onclick="event.stopPropagation()">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                <span wire:loading wire:target="desactivar({{ $id }})" class="absolute ml-2 text-xs">...</span>
                                            </button>
                                        @endif
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg id="chevron-{{ $id }}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalles colapsables -->
                            <div id="details-{{ $id }}" class="hidden bg-slate-50/50 border-t border-white/50 p-5">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-sm text-indigo-900 uppercase tracking-wide mb-3 flex items-center gap-2">
                                            📍 Reportes Vinculados (Últimas 3h)
                                        </h4>
                                        @php($reportesActivos = data_get($inundacion, 'reportes_activos', []))
                                        @if(count($reportesActivos) > 0)
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach($reportesActivos as $rep)
                                                    <div class="bg-white/70 border border-white rounded-xl p-3 shadow-sm flex items-start justify-between group hover:border-indigo-200 transition-colors">
                                                        <div>
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <span class="font-bold text-slate-700 text-sm">#{{ $rep['id'] }}</span>
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                                                    {{ $rep['intensidad_propuesta'] === 'alta' ? 'bg-rose-100 text-rose-700' : ($rep['intensidad_propuesta'] === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">
                                                                    {{ $rep['intensidad_propuesta'] }}
                                                                </span>
                                                            </div>
                                                            <p class="text-xs text-slate-500 mb-1">GPS: {{ number_format((float)$rep['lat_reporte'], 4) }}, {{ number_format((float)$rep['long_reporte'], 4) }}</p>
                                                            <p class="text-[11px] font-medium text-slate-400">{{ $rep['created_at_human'] ?? '' }} • Aportó {{ $rep['peso'] }} pts</p>
                                                        </div>
                                                        
                                                        {{-- Botón de Renovación para Autoridades --}}
                                                        @if(isset($role) && $role === 'authority')
                                                            <button wire:click="renovarReporte({{ $rep['id'] }})" class="opacity-0 group-hover:opacity-100 transition-opacity bg-indigo-50 hover:bg-indigo-100 text-indigo-600 p-1.5 rounded-lg shadow-sm border border-indigo-100" title="Renovar TTL (+3h)">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="bg-slate-100/50 rounded-xl p-4 text-center">
                                                <p class="text-sm text-slate-500 font-medium">No hay reportes activos en las últimas 3h.</p>
                                            </div>
                                        @endif
                                        @php($reportesInactivos = data_get($inundacion, 'reportes_inactivos', []))
                                        @if(count($reportesInactivos) > 0)
                                            <h4 class="font-bold text-sm text-slate-500 uppercase tracking-wide mt-6 mb-3 flex items-center gap-2 border-t border-slate-200/60 pt-4">
                                                ⏳ Reportes Inactivos (TTL Caducado)
                                            </h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 opacity-75 grayscale-[30%]">
                                                @foreach($reportesInactivos as $rep)
                                                    <div class="bg-white/40 border border-slate-200/50 rounded-xl p-3 shadow-sm flex items-start justify-between group hover:border-slate-300 transition-colors">
                                                        <div>
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <span class="font-bold text-slate-500 text-sm">#{{ $rep['id'] }}</span>
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                                                    {{ $rep['intensidad_propuesta'] === 'alta' ? 'bg-rose-50 text-rose-500' : ($rep['intensidad_propuesta'] === 'media' ? 'bg-amber-50 text-amber-500' : 'bg-teal-50 text-teal-500') }}">
                                                                    {{ $rep['intensidad_propuesta'] }}
                                                                </span>
                                                            </div>
                                                            <p class="text-[11px] font-medium text-slate-400">Caducó hace: {{ $rep['caducado_hace'] }}</p>
                                                        </div>
                                                        
                                                        {{-- Botón de Renovación para Autoridades --}}
                                                        @if(isset($role) && $role === 'authority')
                                                            <button wire:click="renovarReporte({{ $rep['id'] }})" class="opacity-100 transition-opacity bg-indigo-50 hover:bg-indigo-100 text-indigo-600 p-1.5 rounded-lg shadow-sm border border-indigo-100" title="Renovar TTL (+3h) para reactivarlo">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="w-full md:w-64 flex flex-col gap-4">
                                        <div class="bg-white/60 border border-white rounded-2xl p-4 shadow-sm">
                                            <h4 class="font-bold text-[11px] text-slate-400 uppercase tracking-widest mb-3">Distribución de Quórum</h4>
                                            @php($desglose = data_get($inundacion, 'desglose_puntos', ['alta'=>0,'media'=>0,'baja'=>0]))
                                            <div class="space-y-2">
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-rose-600 font-bold">Alta</span>
                                                    <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['alta'] }} pts</span>
                                                </div>
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-amber-600 font-bold">Media</span>
                                                    <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['media'] }} pts</span>
                                                </div>
                                                <div class="flex justify-between items-center text-sm">
                                                    <span class="text-teal-600 font-bold">Baja</span>
                                                    <span class="bg-teal-100 text-teal-800 px-2 py-0.5 rounded-full font-semibold text-xs">{{ $desglose['baja'] }} pts</span>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('reports.show', ['id' => $id], false) }}" class="flex items-center justify-center gap-2 bg-gradient-to-r from-slate-800 to-slate-900 text-white rounded-xl py-2.5 px-4 font-semibold hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                                            Ver Ficha Completa
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4 shadow-inner">
                                <span class="text-2xl"></span>
                            </div>
                            <p class="text-slate-500 font-medium text-lg">No hay inundaciones registradas en este momento.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @php($currentPage = (int) ($meta['current_page'] ?? 1))
            @php($lastPage = (int) ($meta['last_page'] ?? 1))

            @if ($lastPage > 1)
                <div class="mt-6 mb-12 flex items-center justify-between">
                    <div class="text-slate-500 font-medium bg-white/50 px-4 py-1.5 rounded-full shadow-sm border border-white/60">
                        Página {{ $currentPage }} de {{ $lastPage }}
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($currentPage > 1)
                            <a class="bg-white/60 hover:bg-white text-slate-700 font-semibold py-2 px-5 rounded-full shadow-sm border border-white/60 transition-colors" href="{{ route('reports.index', ['page' => $currentPage - 1], false) }}">Anterior</a>
                        @endif
                        @if ($currentPage < $lastPage)
                            <a class="bg-white/60 hover:bg-white text-slate-700 font-semibold py-2 px-5 rounded-full shadow-sm border border-white/60 transition-colors" href="{{ route('reports.index', ['page' => $currentPage + 1], false) }}">Siguiente</a>
                        @endif
                    </div>
                </div>
            @endif

            @if(isset($role) && $role === 'authority')

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Reportes Pendientes de Validación
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="glass-panel rounded-3xl overflow-hidden mt-12 mb-12">
                <div class="px-6 py-5 border-b border-white/50 flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gradient-to-r from-amber-50/50 to-orange-50/50 gap-4">
                    <h2 class="text-2xl font-bold text-amber-900 flex items-center gap-2">
                        <span class="text-amber-500"></span> Pendientes de Validación
                    </h2>
                    <a href="#map-container" onclick="document.getElementById('map-container').scrollIntoView({behavior: 'smooth'})" class="bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold px-5 py-2 rounded-full shadow-md shadow-amber-500/20 hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all">Validar en el Mapa</a>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @forelse ($reportesPendientes ?? [] as $rep)
                            <div class="bg-white/50 border border-white/80 rounded-2xl overflow-hidden shadow-sm glass-panel-hover transition-all flex flex-col h-full">
                                <div class="relative h-48 bg-slate-200 z-0" wire:ignore
                                     x-data="minimapComponent({{ $rep->lat_gps ?? 0 }}, {{ $rep->long_gps ?? 0 }}, {{ $rep->lat_reporte ?? 0 }}, {{ $rep->long_reporte ?? 0 }})">
                                </div>
                                <div class="p-5 flex flex-col flex-grow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-bold text-slate-800 text-lg">Reporte Rápido #{{ $rep->id }}</h3>
                                            <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $rep->created_at->format('d M, Y H:i') }}</p>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] text-slate-400 font-bold uppercase mb-1 text-right">Intensidad propuesta</span>
                                            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full uppercase">{{ $rep->intensidad_propuesta }}</span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-slate-600 mb-2">
                                        <span class="font-bold text-slate-700">Dirección:</span>
                                        {{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}
                                    </p>
                                    <p class="text-sm text-slate-600 mb-4 flex-grow">
                                        <span class="font-bold text-slate-700">Descripción:</span>
                                        {{ !empty($rep->description) ? $rep->description : 'Sin descripción.' }}
                                    </p>
                                    @if(!empty($rep->foto_path))
                                        <div class="mb-4">
                                            <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-full h-32 object-cover rounded-xl shadow-sm border border-white/50 cursor-pointer hover:opacity-90 transition-opacity">
                                        </div>
                                    @else
                                        <div class="mb-4 w-full h-32 rounded-xl bg-slate-100 flex flex-col items-center justify-center border border-dashed border-slate-300">
                                            <span class="text-3xl opacity-50 mb-1">📷</span>
                                            <span class="text-xs font-bold text-slate-400 uppercase">Sin foto adjunta</span>
                                        </div>
                                    @endif
                                    
                                    <div class="mt-auto pt-4 border-t border-white/50 flex flex-wrap gap-2">
                                        <button onclick="validarRapido({{ $rep->id }}, 'crear')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 text-sm rounded-xl font-bold shadow-sm transition-colors flex-1">
                                            Aprobar Nuevo
                                        </button>
                                        
                                        @if(count($rep->cercanas ?? []) > 0)
                                            <div class="flex border border-blue-200 rounded-xl overflow-hidden w-full shadow-sm mt-2">
                                                <select id="select-vincular-{{ $rep->id }}" class="text-xs border-0 py-2.5 pl-3 pr-8 bg-blue-50 text-blue-900 focus:ring-0 flex-grow font-medium truncate">
                                                    @foreach($rep->cercanas as $activa)
                                                        <option value="{{ $activa->id }}">A Inundación #{{ $activa->id }}</option>
                                                    @endforeach
                                                </select>
                                                <button onclick="validarRapido({{ $rep->id }}, 'vincular', document.getElementById('select-vincular-{{ $rep->id }}').value)" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 text-xs font-bold transition-colors whitespace-nowrap">
                                                    Vincular
                                                </button>
                                            </div>
                                        @endif
                                        
                                        <button onclick="validarRapido({{ $rep->id }}, 'rechazar')" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-4 py-2 text-sm rounded-xl font-bold shadow-sm transition-colors">
                                            Rechazar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full p-10 text-center bg-white/40 rounded-2xl border border-white/50">
                                <span class="text-3xl block mb-2"></span>
                                <p class="text-slate-600 font-medium">No hay reportes pendientes de revisión!.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Reportes Rechazados
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="glass-panel rounded-3xl overflow-hidden mb-12">
                <div class="px-6 py-4 border-b border-white/50 flex justify-between items-center bg-gradient-to-r from-rose-50/50 to-red-50/50">
                    <h2 class="text-lg font-bold text-rose-900 flex items-center gap-2">
                        <span class="text-rose-500"></span> Reportes Rechazados
                    </h2>
                    <span class="bg-rose-100 text-rose-700 font-bold px-3 py-1 rounded-full text-xs shadow-sm">{{ count($reportesRechazados ?? []) }} registro(s)</span>
                </div>
                <div class="divide-y divide-white/50">
                    @forelse ($reportesRechazados ?? [] as $rep)
                        <div class="p-5 flex flex-col md:flex-row gap-5 hover:bg-white/30 transition-colors">
                            <div class="w-full md:w-32 flex-shrink-0 flex items-center justify-center bg-white/50 border border-white/60 rounded-2xl overflow-hidden h-32 shadow-sm">
                                @if($rep->foto_path)
                                    <img src="{{ asset('storage/' . $rep->foto_path) }}" alt="Foto" onclick="openImageModal('{{ asset('storage/' . $rep->foto_path) }}')" class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-opacity">
                                @else
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="text-3xl opacity-20 mb-1">📷</span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase text-center leading-tight px-2">Sin foto<br>adjunta</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex-grow grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-slate-600">
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">ID</span><span class="font-semibold text-slate-800">#{{ $rep->id }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Intensidad</span>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 bg-rose-100 text-rose-700 font-bold text-[10px] rounded uppercase">{{ $rep->intensidad_propuesta }}</span>
                                </div>
                                <div class="col-span-2"><span class="block text-[10px] font-bold text-slate-400 uppercase">Dirección</span><span class="font-medium text-xs">{{ !empty($rep->address) ? $rep->address : 'Ubicación GPS' }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Creado</span><span class="font-medium">{{ $rep->created_at->format('d/m/Y H:i') }}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Rechazado</span><span class="font-medium">{{ $rep->updated_at->format('d/m/Y H:i') }}</span></div>
                                
                                <div class="col-span-full mt-2 pt-4 border-t border-white/40">
                                    <form wire:submit.prevent="updateEstadoValidacion({{ $rep->id }})" class="flex flex-wrap items-end gap-3">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Estado</label>
                                            <select wire:model="estadoValidacionUpdates.{{ $rep->id }}" class="text-xs font-medium border-0 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                                                <option value="pendiente">Pendiente</option>
                                                <option value="aceptado">Aceptado</option>
                                                <option value="rechazado">Rechazado</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Vincular a Inundación (Si Aceptado)</label>
                                            <select wire:model="inundacionVincularIds.{{ $rep->id }}" class="text-xs font-medium border-0 rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-indigo-500">
                                                <option value="">Ninguna</option>
                                                @foreach(($inundacionesActivasParaVincular ?? []) as $inundacionActiva)
                                                    <option value="{{ $inundacionActiva->id }}">
                                                        Inundación #{{ $inundacionActiva->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-colors">
                                            <span wire:loading.remove wire:target="updateEstadoValidacion({{ $rep->id }})">Guardar Cambios</span>
                                            <span wire:loading wire:target="updateEstadoValidacion({{ $rep->id }})">Guardando...</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 font-medium text-sm">No hay reportes rechazados.</div>
                    @endforelse
                </div>
            </div>

            @endif {{-- end authority --}}

            {{-- ══════════════════════════════════════════════════════════════════
                 PANEL: Inundaciones Terminadas (Historial)
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="glass-panel rounded-3xl overflow-hidden mb-10 opacity-90">
                <div class="px-6 py-4 border-b border-white/50 flex justify-between items-center bg-slate-100/50">
                    <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
                        <span></span> Historial de Inundaciones Terminadas
                    </h2>
                    <span class="bg-white text-slate-600 font-bold px-3 py-1 rounded-full text-xs shadow-sm border border-slate-200">{{ count($inundacionesTerminadas) }} evento(s)</span>
                </div>
                <div class="divide-y divide-white/50 p-2">
                    @forelse ($inundacionesTerminadas as $term)
                        @php($tid = data_get($term, 'id'))
                        @php($desglose = data_get($term, 'desglose_historico', ['alta'=>0,'media'=>0,'baja'=>0]))
                        @php($totalQ = data_get($term, 'quorum_historico', 0))
                        @php($intGanadora = $desglose['alta'] >= $desglose['media'] && $desglose['alta'] >= $desglose['baja'] ? 'alta' : ($desglose['media'] >= $desglose['baja'] ? 'media' : 'baja'))
                        
                        <div class="bg-white/40 rounded-xl m-2 overflow-hidden hover:bg-white/60 transition-colors">
                            <div class="flex items-center justify-between p-4 cursor-pointer" onclick="toggleDetails('term-{{ $tid }}')">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-lg bg-slate-200 flex items-center justify-center font-bold text-slate-600">#{{ $tid }}</div>
                                    <div>
                                        <div class="flex gap-2">
                                            @if($totalQ > 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $intGanadora === 'alta' ? 'bg-rose-100 text-rose-700' : ($intGanadora === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">{{ $intGanadora }}</span>
                                            @endif
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-600">Terminada</span>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-500 mt-1">Duración: {{ data_get($term, 'duracion_texto', '—') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="hidden sm:flex gap-1 text-[10px] font-bold uppercase">
                                        <span class="bg-rose-50 text-rose-600 px-2 py-1 rounded">A: {{ $desglose['alta'] }}</span>
                                        <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded">M: {{ $desglose['media'] }}</span>
                                        <span class="bg-teal-50 text-teal-600 px-2 py-1 rounded">B: {{ $desglose['baja'] }}</span>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            
                            <div id="details-term-{{ $tid }}" class="hidden bg-slate-50/50 border-t border-white/50 p-4">
                                @php($repsVinc = data_get($term, 'reportes_vinculados', []))
                                @if(count($repsVinc) > 0)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($repsVinc as $rv)
                                            <div class="bg-white rounded-lg p-2.5 text-xs border border-slate-100 shadow-sm flex justify-between items-center">
                                                <div>
                                                    <span class="font-bold text-slate-700">#{{ $rv['id'] }}</span>
                                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $rv['intensidad_propuesta'] === 'alta' ? 'bg-rose-50 text-rose-700' : ($rv['intensidad_propuesta'] === 'media' ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700') }}">{{ $rv['intensidad_propuesta'] }}</span>
                                                </div>
                                                <span class="text-slate-400 font-medium">{{ $rv['peso'] }}pts</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-slate-500 font-medium text-center">Sin reportes.</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500 font-medium">No hay eventos en el historial.</div>
                    @endforelse
                </div>
            </div>

        </div> <!-- /max-w-7xl -->
    </div> <!-- /min-h-screen -->

    <!-- LEAFLET CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- LEAFLET HEATMAP PLUGIN -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<!-- RUTAS SEGURAS -->
<script>
    window.ORS_API_KEY = "{{ $ors_key ?? '' }}";
</script>
<script src="{{ asset('js/safe-routing.js') }}"></script>

<style>
/* ── Animación de pulso para zonas de alta intensidad ────────────────── */
@keyframes flood-pulse {
    0%   { fill-opacity: 0.55; stroke-opacity: 0.9; }
    50%  { fill-opacity: 0.30; stroke-opacity: 0.5; }
    100% { fill-opacity: 0.55; stroke-opacity: 0.9; }
}
.flood-polygon-alta path {
    animation: flood-pulse 2.5s ease-in-out infinite;
}

/* Leyenda flotante del mapa */
.map-legend-float {
    position: absolute;
    bottom: 24px;
    left: 16px;
    z-index: 1000;
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    border: 1px solid #e5e7eb;
    padding: 14px 16px;
    min-width: 190px;
    pointer-events: none;
    transition: opacity 0.3s;
}
.map-legend-float.hidden { opacity: 0; pointer-events: none; }
.map-legend-float h4 {
    font-size: 11px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.map-legend-float .legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 12px;
    color: #4b5563;
    font-weight: 500;
}
.map-legend-float .legend-swatch {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    flex-shrink: 0;
    border: 1.5px solid rgba(0,0,0,0.15);
}
.map-legend-float .legend-note {
    font-size: 10px;
    color: #9ca3af;
    text-align: center;
    margin-top: 8px;
    font-style: italic;
}
</style>

<script>
window.floodReports = @json($inundacionesActivas);
window.pendingReports = @json($reportesPendientes ?? []);
window.pendingReports = [];



function initMap() {
    const defaultLocation = [-17.783325, -63.182111]; // Centro de Santa Cruz de la Sierra, Bolivia

    let centerLoc = defaultLocation;
    if (window.floodReports.length > 0) {
        for (let i = 0; i < window.floodReports.length; i++) {
            let lat = parseFloat(window.floodReports[i].latitud);
            let lng = parseFloat(window.floodReports[i].longitud);
            if (!isNaN(lat) && !isNaN(lng)) {
                centerLoc = [lat, lng];
                break;
            }
        }
    }

    // ── 1. Inicializar Mapa ───────────────────────────────────────────────
    const map = L.map('map', { preferCanvas: true }).setView(centerLoc, 12);
    window.mapObj = map;

    // Bounding box del departamento de Santa Cruz (para limitar capas externas)
    const santaCruzBounds = [[-20.5, -64.8], [-13.5, -57.4]];

    // ── 2. Capas Base ─────────────────────────────────────────────────────
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 19,
    });

    // OpenTopoMap — mapa base con curvas de nivel y relieve
    const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
        maxZoom: 17,
        opacity: 1.0,
    });

    // Capa activa por defecto
    osmLayer.addTo(map);

    const baseMaps = {
        "Mapa Normal (OSM)": osmLayer,
        "Satelital (Esri)": satelliteLayer,
        "Topográfico (OpenTopoMap)": topoLayer,
    };

    // ── 3. Overlays ───────────────────────────────────────────────────────
    const overlayMaps = {};
    const layerControl = L.control.layers(baseMaps, overlayMaps, { collapsed: true }).addTo(map);

    // ── 3a. Capas de Reportes ─────────────────────────────────────────────
    const markersLayer           = L.layerGroup().addTo(map); // Centroides principales de inundación
    const polygonLayer           = L.layerGroup().addTo(map); // Polígonos topográficos (activo por defecto)
    const individualReportsLayer = L.layerGroup().addTo(map); // Reportes atómicos (activo por defecto)

    layerControl.addOverlay(markersLayer,           "Centros de Inundación (Centroides)");
    layerControl.addOverlay(polygonLayer,           "Mapa de Calor Inteligente (Topográfico)");
    layerControl.addOverlay(individualReportsLayer, "Reportes Ciudadanos (Detalle)");

    // ── 3b. ESRI Shaded Relief — relieve topográfico superpuesto ─────────
    const reliefOverlay = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri',
        opacity: 0.45,
        bounds: santaCruzBounds,
        minZoom: 5,
        maxZoom: 18,
        zIndex: 5,
    });
    layerControl.addOverlay(reliefOverlay, "Relieve del Terreno (ESRI)");

    // ── 3c. Capas Meteorológicas (OpenWeatherMap) ─────────────────────────
    const precipLayer = L.layerGroup();
    const cloudLayer  = L.layerGroup();
    layerControl.addOverlay(precipLayer, "Radar de Lluvia (OpenWeather)");
    layerControl.addOverlay(cloudLayer,  "Nubes (OpenWeather)");

    L.tileLayer('/weather/tiles/precipitation_new/{z}/{x}/{y}?v=2', {
        opacity: 0.85, attribution: '&copy; OpenWeatherMap',
        bounds: santaCruzBounds, minZoom: 5, maxNativeZoom: 8, maxZoom: 18,
        updateWhenIdle: true, zIndex: 10
    }).addTo(precipLayer);

    L.tileLayer('/weather/tiles/clouds_new/{z}/{x}/{y}?v=2', {
        opacity: 0.85, attribution: '&copy; OpenWeatherMap',
        bounds: santaCruzBounds, minZoom: 5, maxNativeZoom: 8, maxZoom: 18,
        updateWhenIdle: true, zIndex: 10
    }).addTo(cloudLayer);

    // ── 3d. Red Hídrica ───────────────────────────────────────────────────
    fetch('/red_hidrica_santa_cruz.json')
        .then(res => res.json())
        .then(data => {
            const hydroLayer = L.geoJSON(data, {
                style: { color: '#0ea5e9', weight: 1.5, opacity: 0.8 },
                interactive: false
            });
            layerControl.addOverlay(hydroLayer, "Red Hídrica");
        }).catch(e => console.warn("Error cargando red hídrica", e));

    // ── 3e. Fronteras Geográficas ─────────────────────────────────────────
    let provincesData       = null;
    let municipalitiesData  = null;
    let highlightLayer      = null;

    const provincesOverlay = L.geoJSON(null, {
        style: { color: '#F97316', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
        interactive: false
    });
    const municipalitiesOverlay = L.geoJSON(null, {
        style: { color: '#EF4444', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
        interactive: false
    });

    layerControl.addOverlay(provincesOverlay,     "Fronteras Provinciales");
    layerControl.addOverlay(municipalitiesOverlay, "Fronteras Municipales");

    fetch('/provinces.geojson').then(r => r.json()).then(data => {
        provincesData = data;
        provincesOverlay.addData(data);
    });
    fetch('/municipalities.geojson').then(r => r.json()).then(data => {
        municipalitiesData = data;
        municipalitiesOverlay.addData(data);
    });

    // ── 4. Lógica de Leyenda ──────────────────────────────────────────────
    const radarLegend = document.getElementById('radar-legend');

    map.on('overlayadd', function (e) {
        if (e.name === "🌧️ Radar de Lluvia (OpenWeather)" || e.name === "☁️ Nubes (OpenWeather)") {
            if (radarLegend) radarLegend.classList.remove('hidden');
            const isCloud = e.name === "☁️ Nubes (OpenWeather)";
            document.getElementById('radar-legend-title').innerHTML = isCloud
                ? '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Densidad de Nubes</span>'
                : '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Intensidad de Lluvia</span>';
            document.getElementById('radar-legend-rain-colors').classList.toggle('hidden', isCloud);
            document.getElementById('radar-legend-cloud-colors').classList.toggle('hidden', !isCloud);
        }
    });
    map.on('overlayremove', function (e) {
        if (e.name === "🌧️ Radar de Lluvia (OpenWeather)" || e.name === "☁️ Nubes (OpenWeather)") {
            if (!map.hasLayer(precipLayer) && !map.hasLayer(cloudLayer)) {
                if (radarLegend) radarLegend.classList.add('hidden');
            } else if (map.hasLayer(cloudLayer)) {
                document.getElementById('radar-legend-rain-colors').classList.add('hidden');
                document.getElementById('radar-legend-cloud-colors').classList.remove('hidden');
            } else {
                document.getElementById('radar-legend-rain-colors').classList.remove('hidden');
                document.getElementById('radar-legend-cloud-colors').classList.add('hidden');
            }
        }
    });

    // ── 5. Renderizado Inteligente de Reportes ────────────────────────────
    /**
     * Paleta de colores por intensidad de inundación (Escala de Azules y Celestes).
     * Usada tanto para polígonos consolidados como para marcadores y centroides.
     */
    const INTENSITY_PALETTE = {
        alta:  { fill: '#1e3a8a', stroke: '#172554', marker: '#2563eb' },
        media: { fill: '#0ea5e9', stroke: '#0369a1', marker: '#38bdf8' },
        baja:  { fill: '#2dd4bf', stroke: '#0f766e', marker: '#14b8a6' },
        null:  { fill: '#94a3b8', stroke: '#475569', marker: '#cbd5e1' },
    };

    function getPalette(intensidad) {
        return INTENSITY_PALETTE[intensidad] || INTENSITY_PALETTE['null'];
    }

    function renderReports(reportsData) {
        markersLayer.clearLayers();
        polygonLayer.clearLayers();
        individualReportsLayer.clearLayers();

        if (window.activeHeatLayer) {
            map.removeLayer(window.activeHeatLayer);
            window.activeHeatLayer = null;
        }

        const heatPoints = [];

        reportsData.forEach(report => {
            const lat = parseFloat(report.latitud);
            const lng = parseFloat(report.longitud);
            if (isNaN(lat) || isNaN(lng)) return;

            const intensidad = report.intensidad_calculada || 'baja';
            const palette    = getPalette(intensidad);

            // ── Popups Informativos del Centro de Inundación ─────────────────
            const confirmadoBadge = report.esta_confirmada
                ? '<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full">✓ Confirmada</span>'
                : '<span class="inline-flex items-center gap-1 bg-sky-100 text-sky-800 text-[10px] font-bold px-2 py-0.5 rounded-full">⏳ En espera</span>';

            const intensidadBadgeColor = { alta: 'blue', media: 'sky', baja: 'teal' }[intensidad] || 'gray';
            const intensidadBadge = `<span class="inline-flex items-center bg-${intensidadBadgeColor}-100 text-${intensidadBadgeColor}-800 text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize">${intensidad}</span>`;

            const desc        = report.description || 'Sin descripción del evento.';
            const shortDesc   = desc.length > 120 ? desc.substring(0, 120) + '…' : desc;
            const quorumStr   = report.quorum_total !== undefined ? `<b>Quórum Global:</b> ${report.quorum_total} pts` : '';
            
            let numReports = 0;
            if (report.reportes_activos && Array.isArray(report.reportes_activos)) {
                numReports = report.reportes_activos.length;
            }

            const polygonNote = report.polygon_coords
                ? `<p class="text-[10px] text-blue-600 mt-1">🌊 Polígono expansivo recalculado (${numReports} reportes asociados)</p>`
                : '<p class="text-[10px] text-gray-400 mt-1">⏳ Calculando zona de impacto topográfica…</p>';

            const popupContent = `
                <div class="max-w-[240px] font-sans">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        ${intensidadBadge}
                        ${confirmadoBadge}
                    </div>
                    <h5 class="text-xs font-bold text-gray-800 mb-1">Evento de Inundación #${report.id}</h5>
                    <p class="text-xs text-gray-700 mb-1 leading-snug">${shortDesc}</p>
                    <p class="text-xs text-gray-500">${quorumStr}</p>
                    ${polygonNote}
                    <a href="/reports/${report.id}" class="block mt-2 text-center text-xs text-blue-600 hover:underline font-medium">Ver detalles de Inundación →</a>
                </div>`;

            // ── Marcador de Centroide (Boya de Inundación con animación pulse) ──
            const customIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="background-color:${palette.marker};width:20px;height:20px;border-radius:50%;border:3px solid white;box-shadow:0 0 10px rgba(0,0,0,0.3);animation:pulse 2s infinite;"></div>`,
                iconSize: [20, 20], iconAnchor: [10, 10]
            });

            const marker = L.marker([lat, lng], { icon: customIcon })
                .bindPopup(popupContent, { minWidth: 220 })
                .on('click', () => map.flyTo([lat, lng], 15, { animate: true, duration: 0.8 }));

            markersLayer.addLayer(marker);

            // ── Polígono de Autoridad (Edición Manual) ────
            let drawIndividualReports = true;
            if (report.polygon_coords && Array.isArray(report.polygon_coords) && report.polygon_coords.length >= 3) {
                // Si la inundación tiene polígono, significa que la autoridad la dibujó.
                drawIndividualReports = false;

                const authorityPolygon = L.polygon(report.polygon_coords, {
                    color:       '#1e3a8a', // Borde azul profundo
                    fillColor:   '#3b82f6', // Relleno azul
                    fillOpacity: 0.45,
                    weight:      3,
                    dashArray:   '10,5', // Línea punteada de autoridad
                    smoothFactor: 1.0,
                });

                authorityPolygon.bindPopup(popupContent, { minWidth: 220 });
                polygonLayer.addLayer(authorityPolygon);
            }

            // ── Render de Reportes Ciudadanos Individuales (Detalle) ─────────
            let hasActiveReports = false;
            if (report.reportes_activos && Array.isArray(report.reportes_activos)) {
                if (report.reportes_activos.length > 0) hasActiveReports = true;
                
                report.reportes_activos.forEach(rep => {
                    const repLat = parseFloat(rep.lat_reporte);
                    const repLng = parseFloat(rep.long_reporte);
                    if (isNaN(repLat) || isNaN(repLng)) return;

                    // Agregar al heatmap con peso basado directamente en SU intensidad propuesta
                    let weight = 0.3; // baja por defecto
                    if (rep.intensidad_propuesta === 'alta') weight = 1.0;
                    else if (rep.intensidad_propuesta === 'media') weight = 0.6;
                    
                    heatPoints.push([repLat, repLng, weight]);

                    // Topografía individual del reporte (el "charco" de agua)
                    if (drawIndividualReports && rep.polygon_coords && Array.isArray(rep.polygon_coords) && rep.polygon_coords.length >= 3) {
                        const repPolygon = L.polygon(rep.polygon_coords, {
                            color: 'transparent', // Sin borde duro, se fusionará visualmente
                            fillColor: '#38bdf8', // Celeste de agua somera
                            fillOpacity: 0.3,     // Transparente para sumar opacidades si se solapan
                            interactive: false    // No interfiere con clics
                        });
                        polygonLayer.addLayer(repPolygon);
                    }

                    // Si está demasiado cerca del centroide exacto, se puede dibujar igual
                    // Un icono de punto de agua pequeño celeste
                    const repIcon = L.divIcon({
                        className: 'individual-report-dot',
                        html: `<div style="background-color:#60a5fa;width:10px;height:10px;border-radius:50%;border:1.5px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [10, 10], iconAnchor: [5, 5]
                    });

                    const repIntensityColor = { alta: 'blue', media: 'sky', baja: 'teal' }[rep.intensidad_propuesta] || 'gray';
                    const repIntensityBadge = `<span class="inline-flex items-center bg-${repIntensityColor}-50 text-${repIntensityColor}-700 text-[10px] font-medium px-1.5 py-0.25 rounded capitalize">Propuesta: ${rep.intensidad_propuesta}</span>`;

                    const repPopupContent = `
                        <div class="max-w-[200px] font-sans text-xs">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="bg-gray-100 text-gray-800 text-[9px] font-bold px-1.5 py-0.5 rounded">Reporte #${rep.id}</span>
                                ${repIntensityBadge}
                            </div>
                            <p class="text-gray-600 font-medium">Aportó <b>${rep.peso} pts</b> al quórum.</p>
                            <p class="text-[10px] text-gray-400 mt-1">${rep.created_at_human || ''}</p>
                        </div>`;

                    const repMarker = L.marker([repLat, repLng], { icon: repIcon })
                        .bindPopup(repPopupContent, { minWidth: 160 });

                    individualReportsLayer.addLayer(repMarker);
                });

                // --- LÓGICA DE PUENTES TERMICOS Y TOPOGRÁFICOS ---
                // Si hay más de un reporte activo, calculamos la distancia entre ellos y añadimos manchas intermedias
                if (drawIndividualReports && report.reportes_activos.length > 1) {
                    let activeReps = report.reportes_activos;
                    for (let i = 0; i < activeReps.length; i++) {
                        for (let j = i + 1; j < activeReps.length; j++) {
                            let p1 = L.latLng(activeReps[i].lat_reporte, activeReps[i].long_reporte);
                            let p2 = L.latLng(activeReps[j].lat_reporte, activeReps[j].long_reporte);
                            let dist = p1.distanceTo(p2);
                            
                            // Si están a menos de 250 metros, se conectan con manchas (puente de inundación)
                            if (dist > 10 && dist <= 250) {
                                // 1 punto térmico cada 15 metros para asegurar continuidad sin círculos duros
                                let steps = Math.floor(dist / 15);
                                for (let k = 1; k < steps; k++) {
                                    let fraction = k / steps;
                                    let interLat = p1.lat + (p2.lat - p1.lat) * fraction;
                                    let interLng = p1.lng + (p2.lng - p1.lng) * fraction;
                                    
                                    // Mancha térmica de conexión (heatmap fusion pura, sin polígonos)
                                    // Peso 0.35 para que el gradiente se note pero no alcance el nivel 'alta' (1.0)
                                    heatPoints.push([interLat, interLng, 0.35]);
                                }
                            }
                        }
                    }
                }
            }
            
            // Si no hay reportes activos, NO inyectamos el centroide artificial oscuro
            // Solo los reportes reales pintarán calor para mayor precisión clínica.
        });

        // ── Creación Final de la Capa de Degradado ──────────────────────────
        if (heatPoints.length > 0) {
            window.activeHeatLayer = L.heatLayer(heatPoints, {
                radius: 75,       // Aumentado drásticamente para que se toquen incluso al hacer zoom in
                blur: 45,         // Suavizado controlado para no perder la solidez
                minOpacity: 0.4,  // Clave: Esto hace que el "puente" y los bordes sean mucho menos transparentes
                maxZoom: 18,      // Mantener la intensidad correcta en niveles de zoom altos
                gradient: {
                    0.2: '#38bdf8', // Bordes (Agua somera escurriendo hacia zonas bajas)
                    0.5: '#2563eb', // Zonas intermedias (Intensidad media)
                    1.0: '#1e3a8a'  // Epicentro profundo (Intensidad alta)
                }
            });
            // Añadir el heatmap al polygonLayer para que ambos se toggleen juntos con el checkbox
            polygonLayer.addLayer(window.activeHeatLayer);
        }
    }

    // Renderizado inicial
    renderReports(window.floodReports);

    // ── 6. Filtrado por Ubicación ─────────────────────────────────────────
    window.addEventListener('locationFilterChanged', function (e) {
        const { idPrefix, region, provincia, municipio } = e.detail;

        if (idPrefix === 'filter') {
            const filtered = window.floodReports.filter(r => {
                if (region && window.geographicData?.regiones) {
                    const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
                    if (regData && r.municipio && !regData.municipios.includes(r.municipio)) return false;
                }
                if (provincia && r.provincia && r.provincia !== provincia) return false;
                if (municipio && r.municipio && r.municipio !== municipio) return false;
                return true;
            });
            renderReports(filtered);
        }

        if (highlightLayer) { map.removeLayer(highlightLayer); highlightLayer = null; }

        if (municipio && municipalitiesData) {
            const feature = municipalitiesData.features.find(f =>
                window.normalizeMuniName(f.properties.name) === municipio.toLowerCase()
            );
            if (feature) {
                highlightLayer = L.geoJSON(feature, {
                    style: { color: '#EF4444', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                    interactive: false
                }).addTo(map);
                map.fitBounds(highlightLayer.getBounds());
            }
        } else if (provincia && provincesData) {
            const feature = provincesData.features.find(f =>
                window.normalizeProvName(f.properties.name) === provincia.toLowerCase()
            );
            if (feature) {
                highlightLayer = L.geoJSON(feature, {
                    style: { color: '#F97316', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                    interactive: false
                }).addTo(map);
                map.fitBounds(highlightLayer.getBounds());
            }
        } else if (region && window.geographicData && municipalitiesData) {
            const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
            if (regData?.municipios) {
                const features = municipalitiesData.features.filter(f =>
                    regData.municipios.some(rm => rm.toLowerCase() === window.normalizeMuniName(f.properties.name))
                );
                if (features.length > 0) {
                    highlightLayer = L.geoJSON(features, {
                        style: { color: '#8B5CF6', weight: 3, opacity: 0.9, fillOpacity: 0.08 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            }
        } else if (idPrefix === 'filter') {
            map.setView([-17.783325, -63.182111], 12);
        }
    });
}

// Lógica de Pantalla Completa
function toggleMapFullscreen() {
    const container = document.getElementById('map-container');
    if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
        if (container.requestFullscreen) {
            container.requestFullscreen();
        } else if (container.msRequestFullscreen) {
            container.msRequestFullscreen();
        } else if (container.mozRequestFullScreen) {
            container.mozRequestFullScreen();
        } else if (container.webkitRequestFullscreen) {
            container.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
}

document.addEventListener("DOMContentLoaded", initMap);
</script>
<script>
window.renderPendingReports = function(pendingData) {
    pendingData.forEach(report => {
        const lat = parseFloat(report.lat_reporte);
        const lng = parseFloat(report.long_reporte);
        if (isNaN(lat) || isNaN(lng)) return;

        const customIcon = L.divIcon({
            className: 'custom-leaflet-marker',
            html: '<div style="background-color: #F59E0B; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5); animation: pulse 2s infinite;"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        const contentStr = '<div class="max-w-xs"><p class="font-semibold text-sm mb-1 text-orange-600">Reporte Pendiente</p><p class="text-xs text-gray-600 mb-2"><b>Intensidad Propuesta:</b> ' + report.intensidad_propuesta + '</p><div class="flex flex-col space-y-2 mt-2"><button onclick="validateReport(' + report.id + ', \'vincular\');" class="bg-blue-500 text-white px-2 py-1 text-xs rounded">Vincular a Cercana</button><button onclick="validateReport(' + report.id + ', \'crear\');" class="bg-green-500 text-white px-2 py-1 text-xs rounded">Crear Nueva</button><button onclick="validateReport(' + report.id + ', \'rechazar\');" class="bg-red-500 text-white px-2 py-1 text-xs rounded">Rechazar</button></div></div>';

        const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(contentStr, { minWidth: 200 });
        if (window.mapObj) window.mapObj.addLayer(marker);
    });
};

window.validateReport = function(id, action) {
    let body = { action: action };
    if (action === 'vincular') {
        const inundacion_id = prompt('Ingrese el ID de la inundación a la que desea vincular:');
        if (!inundacion_id) return;
        body.inundacion_id = inundacion_id;
    }

    fetch('/api/reportes/' + id + '/validar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer {{ session("api_token") }}'
        },
        body: JSON.stringify(body)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
};
</script>


<script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('minimapComponent', (latGps, lngGps, latRep, lngRep) => ({
                map: null,
                init() {
                    const lGps = parseFloat(latGps);
                    const lnGps = parseFloat(lngGps);
                    const lRep = parseFloat(latRep);
                    const lnRep = parseFloat(lngRep);
                    
                    setTimeout(() => {
                        this.map = L.map(this.$el, {
                            zoomControl: true, attributionControl: false, dragging: true, scrollWheelZoom: true, doubleClickZoom: true
                        }).setView([lRep, lnRep], 15);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                        
                        const iconGps = L.divIcon({ className: '', html: '<div style=\'background-color: rgba(245,158,11,0.6); width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(245,158,11,0.9); box-shadow: 0 0 8px rgba(0,0,0,0.4);\'></div>', iconSize: [14, 14], iconAnchor: [7, 7] });
                        L.marker([lGps, lnGps], { icon: iconGps })
                            .bindTooltip("Ubicacion del usuario (GPS)", { direction: 'top' })
                            .bindPopup("<div class='text-xs'><b>GPS del Ciudadano</b><br>Desde aquí se tomó la foto o se envió el reporte.</div>")
                            .addTo(this.map);
                            
                        L.circle([lGps, lnGps], { radius: 500, color: '#3B82F6', fillColor: '#3B82F6', fillOpacity: 0.1, weight: 1.5, dashArray: '4 4' }).addTo(this.map);
                        
                        const iconRep = L.divIcon({ className: '', html: '<div style=\'background-color: #EF4444; width: 18px; height: 18px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(239,68,68,0.8);\'></div>', iconSize: [18, 18], iconAnchor: [9, 9] });
                        L.marker([lRep, lnRep], { icon: iconRep })
                            .bindTooltip("Punto Reportado", { direction: 'top', className: 'font-bold text-rose-600' })
                            .bindPopup("<div class='text-xs text-rose-700'><b>Punto Reportado</b><br>Lugar exacto del reporte.</div>")
                            .addTo(this.map);
                    }, 50);
                    
                    
                }
            }));
        });

        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('chevron-' + id);
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.classList.add('rotate-180');
            } else if (el) {
                el.classList.add('hidden');
                if(icon) icon.classList.remove('rotate-180');
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
                Livewire.dispatch('refreshReports');
            })
            .catch(() => {
                alert('Ocurrió un error al procesar la solicitud.');
            });
        }



        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            img.src = src;
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('modalImage').src = '';
            }, 300);
        }
    </script>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-[100] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-12 right-0 sm:-right-12 sm:top-0 text-white hover:text-rose-400 bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modalImage" src="" alt="Report Image" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/20">
        </div>
    </div>
</div>
