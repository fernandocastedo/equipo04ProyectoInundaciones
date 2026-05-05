@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════
         CABECERA
    ══════════════════════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('victimas.index', [], false) }}"
           class="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Volver al listado
        </a>
    </div>

    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Registrar Víctima</h1>
        <p class="mt-1 text-sm text-gray-500">Complete el formulario para registrar a una persona afectada.</p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════
         FORMULARIO
    ══════════════════════════════════════════════════════════════════ --}}
    <form method="POST"
          action="{{ route('victimas.store', [], false) }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        {{-- ── Selección de Inundación ─────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-blue-50 border-b border-blue-100">
                <h2 class="text-sm font-semibold text-blue-800">1. Inundación asociada</h2>
                <p class="text-xs text-blue-600 mt-0.5">Seleccione a qué evento de inundación corresponde esta víctima.</p>
            </div>
            <div class="p-5">
                <label for="inundacion_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Inundación <span class="text-red-500">*</span>
                </label>
                <select name="inundacion_id" id="inundacion_id" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 @error('inundacion_id') border-red-300 @enderror">
                    <option value="">— Seleccione una inundación —</option>
                    @foreach($inundaciones as $inundacion)
                        @php
                            $muni  = $inundacion->municipio?->nombre ?? 'Sin municipio';
                            $prov  = $inundacion->municipio?->provincia?->nombre ?? 'Sin provincia';
                            $fecha = $inundacion->created_at
                                ? \Carbon\Carbon::parse($inundacion->created_at)->format('d/m/Y H:i')
                                : '?';
                            $estado = ucfirst($inundacion->estado);
                        @endphp
                        <option value="{{ $inundacion->id }}"
                            data-municipio="{{ $muni }}"
                            data-provincia="{{ $prov }}"
                            data-fecha="{{ $fecha }}"
                            data-estado="{{ $estado }}"
                            {{ old('inundacion_id') == $inundacion->id ? 'selected' : '' }}>
                            #{{ $inundacion->id }} · {{ $fecha }} · {{ $prov }} / {{ $muni }} [{{ $estado }}]
                        </option>
                    @endforeach
                </select>
                @error('inundacion_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror

                {{-- Tarjeta informativa que se autocompleta al seleccionar inundación --}}
                <div id="inundacion-info"
                     class="mt-3 hidden rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <span class="font-semibold block">Fecha</span>
                            <span id="info-fecha">—</span>
                        </div>
                        <div>
                            <span class="font-semibold block">Provincia</span>
                            <span id="info-provincia">—</span>
                        </div>
                        <div>
                            <span class="font-semibold block">Municipio</span>
                            <span id="info-municipio">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Datos Personales ────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">2. Datos personales de la víctima</h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Nombre Completo --}}
                <div class="sm:col-span-2">
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nombre_completo" id="nombre_completo"
                           value="{{ old('nombre_completo') }}"
                           placeholder="Ej. Juan Pérez García"
                           required maxlength="255"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 @error('nombre_completo') border-red-300 @enderror">
                    @error('nombre_completo')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Carnet --}}
                <div>
                    <label for="carnet" class="block text-sm font-medium text-gray-700 mb-1">
                        Carnet de Identidad
                    </label>
                    <input type="text" name="carnet" id="carnet"
                           value="{{ old('carnet') }}"
                           placeholder="Ej. 1234567"
                           maxlength="20"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 @error('carnet') border-red-300 @enderror">
                    @error('carnet')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Fecha de Nacimiento --}}
                <div>
                    <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Nacimiento
                    </label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento') }}"
                           max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 @error('fecha_nacimiento') border-red-300 @enderror">
                    @error('fecha_nacimiento')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Estado y Descripción ────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">3. Estado y descripción</h2>
            </div>
            <div class="p-5 space-y-5">

                {{-- Estado (botones de radio estilizados) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Estado de la víctima <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $estadoConfig = [
                                'perdido'    => ['icon' => '🔍', 'bg' => 'bg-yellow-50 border-yellow-300 text-yellow-800', 'checked' => 'ring-2 ring-yellow-400'],
                                'encontrado' => ['icon' => '✅', 'bg' => 'bg-green-50 border-green-300 text-green-800', 'checked' => 'ring-2 ring-green-400'],
                                'herido'     => ['icon' => '🩹', 'bg' => 'bg-orange-50 border-orange-300 text-orange-800', 'checked' => 'ring-2 ring-orange-400'],
                                'fallecido'  => ['icon' => '✝️', 'bg' => 'bg-red-50 border-red-300 text-red-800', 'checked' => 'ring-2 ring-red-400'],
                            ];
                        @endphp
                        @foreach($estadoLabels as $val => $label)
                            @php $cfg = $estadoConfig[$val] ?? []; @endphp
                            <label for="estado_{{ $val }}"
                                   class="flex flex-col items-center justify-center p-3 rounded-lg border-2 cursor-pointer transition-all
                                          {{ $cfg['bg'] ?? 'border-gray-200' }}
                                          estado-radio-label {{ (old('estado', 'perdido') === $val) ? ($cfg['checked'] ?? '') : '' }}"
                                   data-checked-class="{{ $cfg['checked'] ?? '' }}">
                                <input type="radio" name="estado" id="estado_{{ $val }}"
                                       value="{{ $val }}"
                                       class="sr-only"
                                       {{ old('estado', 'perdido') === $val ? 'checked' : '' }}>
                                <span class="text-2xl mb-1">{{ $cfg['icon'] ?? '' }}</span>
                                <span class="text-xs font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('estado')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción adicional
                    </label>
                    <textarea name="descripcion" id="descripcion" rows="4"
                              maxlength="2000"
                              placeholder="Condición de la víctima, lugar donde fue encontrada, información relevante..."
                              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 @error('descripcion') border-red-300 @enderror">{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ── Fotografía ──────────────────────────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">4. Fotografía (opcional)</h2>
            </div>
            <div class="p-5">
                <div class="flex items-start gap-5">
                    {{-- Preview --}}
                    <div id="foto-preview-container"
                         class="flex-shrink-0 w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden">
                        <svg id="foto-placeholder" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                        </svg>
                        <img id="foto-preview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <label for="foto" class="block text-sm font-medium text-gray-700 mb-1">Subir foto</label>
                        <input type="file" name="foto" id="foto"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('foto') border-red-300 @enderror">
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG o WebP · Máximo 4 MB</p>
                        @error('foto')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Botones de acción ───────────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('victimas.index', [], false) }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Registrar Víctima
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    'use strict';

    // ── Autocomplete de la tarjeta informativa al elegir inundación ──────
    const selInun    = document.getElementById('inundacion_id');
    const infoCard   = document.getElementById('inundacion-info');
    const infoFecha  = document.getElementById('info-fecha');
    const infoProv   = document.getElementById('info-provincia');
    const infoMuni   = document.getElementById('info-municipio');

    function actualizarInfoCard() {
        const opt = selInun.options[selInun.selectedIndex];
        if (!selInun.value) {
            infoCard.classList.add('hidden');
            return;
        }
        infoFecha.textContent    = opt.dataset.fecha    || '—';
        infoProv.textContent     = opt.dataset.provincia || '—';
        infoMuni.textContent     = opt.dataset.municipio || '—';
        infoCard.classList.remove('hidden');
    }

    selInun.addEventListener('change', actualizarInfoCard);
    // Si ya hay un valor preseleccionado (old()), mostrar info inmediatamente
    if (selInun.value) actualizarInfoCard();

    // ── Radios de estado: estilos visuales ──────────────────────────────
    const radioLabels = document.querySelectorAll('.estado-radio-label');
    const radios      = document.querySelectorAll('input[name="estado"]');

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            radioLabels.forEach(function (lbl) {
                lbl.classList.remove(...(lbl.dataset.checkedClass || '').split(' ').filter(Boolean));
            });
            const parentLabel = radio.closest('label');
            if (parentLabel && radio.checked) {
                const cls = (parentLabel.dataset.checkedClass || '').split(' ').filter(Boolean);
                parentLabel.classList.add(...cls);
            }
        });
    });

    // ── Preview de foto ──────────────────────────────────────────────────
    const inputFoto    = document.getElementById('foto');
    const previewImg   = document.getElementById('foto-preview');
    const placeholder  = document.getElementById('foto-placeholder');

    inputFoto.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) {
            previewImg.classList.add('hidden');
            placeholder.classList.remove('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    });
})();
</script>
@endsection
