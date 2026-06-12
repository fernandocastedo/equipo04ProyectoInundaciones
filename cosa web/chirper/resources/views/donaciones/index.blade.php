@extends('layouts.app')

@php
    $apiUser = (array) session('api_user', []);
    $isAuthority = ($apiUser['role'] ?? '') === 'authority';
    $myCarnet = $apiUser['carnet'] ?? null;
@endphp

@section('content')
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
                <option value="recibido" {{ request('status') == 'recibido' ? 'selected' : '' }}>Recibido</option>
                <option value="en_uso" {{ request('status') == 'en_uso' ? 'selected' : '' }}>En Uso</option>
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
                        @if($donacion->status === 'recibido') bg-yellow-100 text-yellow-800
                        @elseif($donacion->status === 'en_uso') bg-blue-100 text-blue-800
                        @else bg-green-100 text-green-800 @endif
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

                @if($donacion->usage_details)
                <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 border border-gray-100">
                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Detalles de uso</span>
                    {{ $donacion->usage_details }}
                </div>
                @endif
            </div>
            
            @if($isAuthority)
            <div class="border-t border-gray-200 bg-gray-50 p-3">
                <button onclick="openUpdateModal({{ $donacion->id }}, '{{ $donacion->status }}', '{{ addslashes($donacion->usage_details ?? '') }}')" class="w-full rounded bg-white px-3 py-2 text-sm font-semibold text-blue-600 border border-blue-200 hover:bg-blue-50 transition-colors">
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
<div id="modal-create" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
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
                <label class="block text-xs font-bold text-gray-700 mb-1">Carnet del Donante</label>
                <input type="text" name="donor_carnet" placeholder="Ej. 1234567" class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
            </div>
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Descripción de lo donado</label>
                <textarea name="items_description" required rows="2" placeholder="Ej. 50 botellas de agua..." class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2"></textarea>
            </div>
            <div class="mb-4 flex items-center gap-2">
                <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="is_anonymous" class="text-xs font-medium text-gray-700 cursor-pointer">Donación anónima</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-blue-600 rounded hover:bg-blue-700 shadow-sm">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Actualizar Uso -->
<div id="modal-update" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm max-h-[90vh] flex flex-col overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-5 py-3 flex justify-between items-center shrink-0">
            <h2 class="text-base font-bold text-gray-900">Actualizar Uso</h2>
            <button type="button" onclick="document.getElementById('modal-update').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="update-form" method="POST" class="p-5 overflow-y-auto flex-1">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="block text-xs font-bold text-gray-700 mb-1">Estado</label>
                <select name="status" id="update-status" required class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="recibido">Recibido (Aún en inventario)</option>
                    <option value="en_uso">En Uso</option>
                    <option value="entregado">Entregado a Víctimas</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Detalles de uso</label>
                <textarea name="usage_details" id="update-details" rows="3" placeholder="Explique qué se hizo..." class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-update').classList.add('hidden')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-gray-800 rounded hover:bg-gray-900 shadow-sm">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(id, status, details) {
        document.getElementById('update-form').action = '/donaciones/' + id;
        document.getElementById('update-status').value = status;
        document.getElementById('update-details').value = details;
        document.getElementById('modal-update').classList.remove('hidden');
    }
</script>
@endif

@endsection
