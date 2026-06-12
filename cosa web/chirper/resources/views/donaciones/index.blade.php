@extends('layouts.app')

@php
    $apiUser = (array) session('api_user', []);
    $isAuthority = ($apiUser['role'] ?? '') === 'authority';
    $myCarnet = $apiUser['carnet'] ?? null;
@endphp

@section('content')
<!-- Leaflet CSS & JS for Maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Transparencia de Donaciones</h1>
        <p class="text-sm text-gray-500">Listado público de donaciones recibidas y su estado.</p>
    </div>
    
    @if($isAuthority)
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        Registrar Donación
    </button>
    @endif
</div>

<!-- Filtros -->
<div class="mb-4">
    <form method="GET" action="{{ route('donaciones.index') }}" class="flex flex-wrap items-center gap-3 text-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-xs uppercase tracking-wider text-gray-500 hidden sm:inline">Filtros:</span>
            <select name="centro_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                <option value="">Todos los centros</option>
                @foreach($centros as $c)
                    <option value="{{ $c->id_centro }}" {{ request('centro_id') == $c->id_centro ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-8 text-sm focus:border-blue-500 focus:ring-blue-500 shadow-sm">
                <option value="">Cualquier estado</option>
                <option value="en_inventario" {{ request('status') == 'en_inventario' ? 'selected' : '' }}>En Inventario</option>
                <option value="entregado" {{ request('status') == 'entregado' ? 'selected' : '' }}>Entregado</option>
            </select>
        </div>
        
        @if(!$isAuthority && $myCarnet)
        <label class="flex items-center gap-1.5 cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 shadow-sm hover:bg-gray-50 transition-colors">
            <input type="checkbox" name="mine" value="1" onchange="this.form.submit()" {{ request('mine') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700">Solo mis donaciones</span>
        </label>
        @endif

        @if(request()->anyFilled(['centro_id', 'status', 'mine']))
        <a href="{{ route('donaciones.index') }}" class="text-xs font-semibold text-gray-500 hover:text-gray-800 underline transition-colors" title="Limpiar">Limpiar filtros</a>
        @endif
    </form>
</div>

<!-- Lista de Donaciones -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($donaciones as $donacion)
        <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-5 flex-1">
                <div class="flex justify-between items-start mb-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider
                        @if($donacion->status === 'en_inventario') bg-blue-100 text-blue-800
                        @elseif($donacion->status === 'entregado') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800 @endif
                    ">
                        {{ str_replace('_', ' ', $donacion->status) }}
                    </span>
                    <span class="text-xs text-gray-500 font-medium">{{ $donacion->created_at->format('d/m/Y') }}</span>
                </div>
                
                <h3 class="font-bold text-lg text-gray-900 mb-1">
                    {{ $donacion->items_description }}
                </h3>
                
                <div class="text-sm text-gray-600 mb-4 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    {{ $donacion->centro ? $donacion->centro->nombre : 'Centro desconocido' }}
                </div>

                <div class="text-sm border-t border-gray-100 pt-3">
                    <span class="text-gray-500">Donado por:</span>
                    <span class="font-medium text-gray-900">
                        @if($donacion->is_anonymous)
                            Anónimo
                        @else
                            {{ $donacion->donor ? $donacion->donor->name : 'Desconocido' }}
                        @endif
                    </span>
                </div>

                @if($donacion->inundacion || $donacion->victima)
                <div class="mt-3 rounded-lg bg-blue-50 p-3 text-sm border border-blue-100">
                    <span class="block text-xs font-bold uppercase tracking-wider text-blue-800 mb-1">Destino</span>
                    @if($donacion->inundacion)
                        <div class="flex items-center gap-1 text-blue-700 mb-1 font-medium">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Inundación en 
                            @if(isset($donacion->inundacion->municipio->nombre))
                                {{ $donacion->inundacion->municipio->nombre }}
                            @else
                                <span class="location-name-fetch" data-lat="{{ $donacion->inundacion->latitud }}" data-lon="{{ $donacion->inundacion->longitud }}">Ubicación Desconocida</span>
                            @endif
                        </div>
                    @endif
                    @if($donacion->victima)
                        <div class="flex items-center gap-1 text-blue-700 font-medium">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Víctima: {{ $donacion->victima->nombre_completo }}
                        </div>
                    @endif
                </div>
                @endif

                @if($donacion->usage_details)
                <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 border border-gray-100">
                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Detalles de uso</span>
                    {{ $donacion->usage_details }}
                </div>
                @endif
                
                @if($donacion->photo_path)
                <div class="mt-4 border-t border-gray-100 pt-3">
                    <img src="{{ asset('storage/' . $donacion->photo_path) }}" alt="Foto comprobante" class="w-full h-40 object-cover rounded-lg border border-gray-200">
                </div>
                @endif
            </div>
            
            @if($isAuthority)
            <div class="border-t border-gray-200 bg-gray-50 p-3">
                <button onclick="openUpdateModal({{ $donacion->id }}, '{{ $donacion->status }}', '{{ addslashes($donacion->usage_details ?? '') }}', '{{ $donacion->inundacion_id ?? '' }}', '{{ $donacion->victima_id ?? '' }}')" class="w-full rounded bg-white px-3 py-2 text-sm font-semibold text-blue-600 border border-blue-200 hover:bg-blue-50 transition-colors">
                    Actualizar Uso
                </button>
            </div>
            @endif
        </div>
    @empty
        <div class="col-span-full py-12 text-center rounded-xl border border-dashed border-gray-300 bg-white">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay donaciones</h3>
            <p class="mt-1 text-sm text-gray-500">Intenta cambiar los filtros o no se ha registrado ninguna donación aún.</p>
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $donaciones->links() }}
</div>

@if($isAuthority)
<!-- Modal Crear Donación -->
<div id="modal-create" class="hidden fixed inset-0 z-[9990] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm max-h-[90vh] flex flex-col overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 flex justify-between items-center shrink-0">
            <h2 class="text-base font-bold text-gray-900">Registrar Donación</h2>
            <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form action="{{ route('donaciones.store') }}" method="POST" class="p-5 overflow-y-auto flex-1">
            @csrf
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Centro de Acopio</label>
                <select name="centro_id" required class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="">Seleccione un centro</option>
                    @foreach($centros as $c)
                        <option value="{{ $c->id_centro }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Carnet del Donante (6-8 dígitos)</label>
                <input type="text" name="donor_carnet" id="create_donor_carnet" placeholder="Ej. 1234567" minlength="6" maxlength="8" pattern="\d{6,8}" required class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
            </div>
            
            <div class="mb-4 flex items-center gap-2">
                <input type="checkbox" name="is_anonymous" id="create_is_anonymous" value="1" onchange="toggleDonorCarnet(this.checked, 'create_donor_carnet')" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="create_is_anonymous" class="text-xs font-medium text-gray-700 cursor-pointer">Donación anónima</label>
            </div>

            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Descripción de lo donado</label>
                <textarea name="items_description" required rows="2" placeholder="Ej. 50 botellas de agua..." class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2"></textarea>
            </div>

            <div class="mb-3 border-t border-gray-100 pt-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Destino (Opcional)</label>
                <input type="hidden" name="inundacion_id" id="create_inundacion_id">
                
                <button type="button" onclick="openInundacionModal('create_inundacion_id', 'create_inundacion_preview')" class="w-full flex items-center justify-between text-left rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:border-blue-500 focus:ring-blue-500">
                    <span id="create_inundacion_preview">Seleccionar Inundación...</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <button type="button" onclick="clearInundacionSelection('create_inundacion_id', 'create_inundacion_preview')" class="text-xs text-red-500 hover:text-red-700 mt-1 hidden" id="create_inundacion_clear">Quitar selección</button>
            </div>
            
            <div class="mb-4">
                <select name="victima_id" class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="">Seleccionar Víctima (Opcional)...</option>
                    @foreach($victimas as $v)
                        <option value="{{ $v->id }}">{{ $v->nombre_completo }} - {{ $v->carnet }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-blue-600 rounded hover:bg-blue-700 shadow-sm">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Actualizar Uso -->
<div id="modal-update" class="hidden fixed inset-0 z-[9990] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm max-h-[90vh] flex flex-col overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 flex justify-between items-center shrink-0">
            <h2 class="text-base font-bold text-gray-900">Actualizar Uso</h2>
            <button type="button" onclick="document.getElementById('modal-update').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="update-form" method="POST" enctype="multipart/form-data" class="p-5 overflow-y-auto flex-1">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Estado</label>
                <select name="status" id="update-status" onchange="toggleUpdateStatusFields()" required class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="en_inventario">En Inventario</option>
                    <option value="entregado">Entregado a Víctimas/Inundación</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Foto de prueba (Obligatoria al cambiar)</label>
                <input type="file" name="photo" id="update_photo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded p-1 cursor-pointer">
            </div>

            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Detalles de uso</label>
                <textarea name="usage_details" id="update-details" rows="2" placeholder="Explique qué se hizo..." class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2"></textarea>
            </div>

            <div id="entregado_fields" class="hidden border-t border-gray-100 pt-3 mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Destino (Inundación Obligatoria)</label>
                <input type="hidden" name="inundacion_id" id="update_inundacion_id">
                
                <button type="button" onclick="openInundacionModal('update_inundacion_id', 'update_inundacion_preview')" class="w-full flex items-center justify-between text-left rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:border-blue-500 focus:ring-blue-500 mb-2">
                    <span id="update_inundacion_preview">Seleccionar Inundación...</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                
                <select name="victima_id" id="update_victima_id" class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="">Seleccionar Víctima (Opcional)...</option>
                    @foreach($victimas as $v)
                        <option value="{{ $v->id }}">{{ $v->nombre_completo }} - {{ $v->carnet }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-update').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-gray-800 rounded hover:bg-gray-900 shadow-sm">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Selección de Inundación -->
<div id="modal-inundacion" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md flex flex-col overflow-hidden max-h-[90vh]">
        <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 flex justify-between items-center shrink-0">
            <h2 class="text-base font-bold text-gray-900">Seleccionar Inundación</h2>
            <button type="button" onclick="closeInundacionModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5 overflow-y-auto flex-1 bg-gray-50/50">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Activas</h3>
            <div class="space-y-3">
                @forelse($inundacionesActivas as $inundacion)
                <button type="button" onclick="selectInundacion({{ $inundacion->id }}, 'Inundación en @if(isset($inundacion->municipio->nombre)){{$inundacion->municipio->nombre}}@else Desconocida @endif')" class="w-full flex items-center text-left bg-white border border-gray-200 rounded-lg p-3 hover:border-blue-500 hover:shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 relative">
                    <!-- Map Preview Container -->
                    <div id="mini-map-{{ $inundacion->id }}" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}" class="mini-map-container w-16 h-16 shrink-0 bg-gray-100 rounded-md border border-gray-200 mr-4 z-0 pointer-events-none"></div>
                    
                    <div class="z-10 pointer-events-none w-full pr-2">
                        <div class="font-bold text-sm text-gray-900 location-title" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}">
                            Inundación en {{ $inundacion->municipio->nombre ?? 'Desconocida' }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1 location-detail truncate w-full" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}">
                            Aprox: Lat {{ $inundacion->latitud }}, Lng {{ $inundacion->longitud }}
                        </div>
                    </div>
                </button>
                @empty
                <div class="text-sm text-gray-500 italic">No hay inundaciones activas.</div>
                @endforelse
            </div>

            <div class="mt-6 text-center" id="btn-show-past">
                <button type="button" onclick="document.getElementById('past-inundaciones').classList.remove('hidden'); this.classList.add('hidden'); initializePastMaps();" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                    Mostrar inundaciones pasadas ▼
                </button>
            </div>

            <div id="past-inundaciones" class="hidden mt-4 pt-4 border-t border-gray-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Pasadas</h3>
                <div class="space-y-3">
                    @forelse($inundacionesTerminadas as $inundacion)
                    <button type="button" onclick="selectInundacion({{ $inundacion->id }}, 'Inundación en @if(isset($inundacion->municipio->nombre)){{$inundacion->municipio->nombre}}@else Desconocida @endif')" class="w-full flex items-center text-left bg-white border border-gray-200 rounded-lg p-3 hover:border-gray-400 hover:shadow-sm transition-all opacity-80 focus:outline-none focus:ring-2 focus:ring-gray-400 relative">
                        <!-- Map Preview Container -->
                        <div id="mini-map-past-{{ $inundacion->id }}" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}" class="mini-map-past-container w-16 h-16 shrink-0 bg-gray-100 rounded-md border border-gray-200 mr-4 z-0 pointer-events-none"></div>
                        
                        <div class="z-10 pointer-events-none w-full pr-2">
                            <div class="font-semibold text-sm text-gray-600 location-title" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}">
                                Inundación en {{ $inundacion->municipio->nombre ?? 'Desconocida' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">Activa del {{ $inundacion->created_at ? $inundacion->created_at->format('d/m/Y') : 'N/A' }} al {{ $inundacion->updated_at ? $inundacion->updated_at->format('d/m/Y') : 'N/A' }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 location-detail truncate w-full" data-lat="{{ $inundacion->latitud }}" data-lon="{{ $inundacion->longitud }}">
                                Aprox: Lat {{ $inundacion->latitud }}, Lng {{ $inundacion->longitud }}
                            </div>
                        </div>
                    </button>
                    @empty
                    <div class="text-sm text-gray-500 italic">No hay inundaciones pasadas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables for tracking which input to update
    let currentInundacionInputId = null;
    let currentInundacionPreviewId = null;
    let mapsInitialized = false;
    let pastMapsInitialized = false;
    let leafMaps = [];

    // Custom map marker
    const redIcon = L.divIcon({
        className: 'custom-div-icon',
        html: `<svg class="w-6 h-6 text-red-600 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24" style="margin-left:-12px; margin-top:-24px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`,
        iconSize: [24, 24],
        iconAnchor: [12, 24]
    });

    function initMaps(selector) {
        document.querySelectorAll(selector).forEach(el => {
            if(el.classList.contains('leaflet-container')) return; // Already initialized
            
            const lat = parseFloat(el.dataset.lat);
            const lon = parseFloat(el.dataset.lon);
            const mapId = el.id;
            
            if (!isNaN(lat) && !isNaN(lon)) {
                const map = L.map(mapId, {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false
                }).setView([lat, lon], 14);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                L.marker([lat, lon], {icon: redIcon}).addTo(map);
                
                leafMaps.push({ map: map, lat: lat, lon: lon });
            }
        });
    }

    function forceMapCentering() {
        let count = 0;
        let interval = setInterval(() => {
            leafMaps.forEach(item => {
                item.map.invalidateSize(true);
                item.map.setView([item.lat, item.lon], 14, {animate: false});
            });
            count++;
            if(count > 10) clearInterval(interval); // Run 10 times over 1 second
        }, 100);
    }

    function openUpdateModal(id, status, details, inundacionId, victimaId) {
        document.getElementById('update-form').action = '/donaciones/' + id;
        
        // Forzar directamente a 'entregado' según requerimiento
        document.getElementById('update-status').value = 'entregado';
        
        document.getElementById('update-details').value = details;
        document.getElementById('update_inundacion_id').value = inundacionId;
        document.getElementById('update_victima_id').value = victimaId;
        
        // Reset file input
        document.getElementById('update_photo').value = '';
        
        // Check if there was an inundacion selected to show preview
        if(inundacionId) {
            document.getElementById('update_inundacion_preview').innerText = 'Inundación Seleccionada (ID: '+inundacionId+')';
        } else {
            document.getElementById('update_inundacion_preview').innerText = 'Seleccionar Inundación...';
        }

        toggleUpdateStatusFields();
        document.getElementById('modal-update').classList.remove('hidden');
    }

    function toggleDonorCarnet(isAnonymous, inputId) {
        const input = document.getElementById(inputId);
        if (isAnonymous) {
            input.disabled = true;
            input.value = '';
            input.required = false;
        } else {
            input.disabled = false;
            input.required = true;
        }
    }

    function toggleUpdateStatusFields() {
        const status = document.getElementById('update-status').value;
        const entregadoFields = document.getElementById('entregado_fields');
        if (status === 'entregado') {
            entregadoFields.classList.remove('hidden');
        } else {
            entregadoFields.classList.add('hidden');
        }
    }

    function openInundacionModal(inputId, previewId) {
        currentInundacionInputId = inputId;
        currentInundacionPreviewId = previewId;
        document.getElementById('modal-inundacion').classList.remove('hidden');
        
        if(!mapsInitialized) {
            initMaps('.mini-map-container');
            mapsInitialized = true;
        }
        
        forceMapCentering();
    }

    function initializePastMaps() {
        if(!pastMapsInitialized) {
            initMaps('.mini-map-past-container');
            pastMapsInitialized = true;
        }
        forceMapCentering();
    }

    function closeInundacionModal() {
        document.getElementById('modal-inundacion').classList.add('hidden');
        currentInundacionInputId = null;
        currentInundacionPreviewId = null;
    }

    function selectInundacion(id, name) {
        if(currentInundacionInputId) {
            document.getElementById(currentInundacionInputId).value = id;
            
            // Si el nombre dice Desconocida y logramos obtener el nombre por OSM, tratamos de pasarlo
            // Pero como se lo pasamos fijo en el onclick desde Blade, lo más fácil es leer el texto del boton
            // o simplemente inyectarlo.
            // Para asegurar el nombre más preciso, buscamos el texto renderizado en el modal
            const btn = document.querySelector(`button[onclick*="selectInundacion(${id}"]`);
            let finalName = name;
            if(btn) {
                const nameSpan = btn.querySelector('.font-bold') || btn.querySelector('.font-semibold');
                if(nameSpan) {
                    finalName = nameSpan.innerText.split('\n')[0].trim(); // Get "Inundacion en X"
                }
            }
            
            document.getElementById(currentInundacionPreviewId).innerText = finalName;
            
            // Si es el modal de creación, mostrar el botón de limpiar
            if (currentInundacionInputId === 'create_inundacion_id') {
                document.getElementById('create_inundacion_clear').classList.remove('hidden');
            }
        }
        closeInundacionModal();
    }
    
    function clearInundacionSelection(inputId, previewId) {
        document.getElementById(inputId).value = '';
        document.getElementById(previewId).innerText = 'Seleccionar Inundación...';
        document.getElementById(inputId + '_clear').classList.add('hidden');
    }

    // Proceso de Nominatim (Reverse Geocoding)
    document.addEventListener('DOMContentLoaded', function() {
        // Collect all locations regardless of whether they have a name, so we can update the detail string
        const locationTitles = document.querySelectorAll('.location-title');
        let fetchQueue = [];
        
        const uniqueCoords = new Set();
        locationTitles.forEach(span => {
            const lat = span.dataset.lat;
            const lon = span.dataset.lon;
            const key = lat + ',' + lon;
            if(!uniqueCoords.has(key)) {
                uniqueCoords.add(key);
                fetchQueue.push({lat, lon});
            }
        });
        
        function processQueue() {
            if (fetchQueue.length === 0) return;
            
            const coord = fetchQueue.shift();
            const lat = coord.lat;
            const lon = coord.lon;
            
            if (lat && lon) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=14&addressdetails=1`, {
                    headers: {
                        'Accept-Language': 'es' // Para obtener nombres en español
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.address) {
                        let addr = data.address;
                        // Get the most specific street/place name available
                        let streetName = addr.road || addr.pedestrian || addr.path || addr.square || addr.neighbourhood || addr.suburb || addr.residential || addr.village || addr.town || addr.city || 'Desconocida';
                        let title = "Inundación en " + streetName;
                        let detail = data.display_name || "Detalle de ubicación no disponible";
                        
                        // Actualizar todos los elementos correspondientes
                        document.querySelectorAll(`.location-title[data-lat="${lat}"][data-lon="${lon}"]`).forEach(el => {
                            // Preserve the original name if we couldn't find a better one and it's not empty
                            if(streetName !== 'Desconocida') {
                                el.innerText = title;
                            }
                        });
                        
                        document.querySelectorAll(`.location-detail[data-lat="${lat}"][data-lon="${lon}"]`).forEach(el => {
                            el.innerText = detail;
                        });
                    }
                })
                .catch(err => console.error('Error fetching location:', err))
                .finally(() => {
                    // Esperar 1.2 segundos para respetar el rate limit de Nominatim (1 req/sec absoluto)
                    setTimeout(processQueue, 1200);
                });
            } else {
                processQueue();
            }
        }
        
        processQueue();
    });
</script>
@endif

@endsection
