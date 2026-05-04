@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Crear reporte</h1>
                <p class="mt-1 text-sm text-gray-600">Ingresá ubicación y descripción del evento.</p>
            </div>
            <a href="{{ route('reports.index', [], false) }}" class="rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">Volver</a>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('reports.store', [], false) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="latitud">Latitud</label>
                    <input id="latitud" name="latitud" type="number" step="any" value="{{ old('latitud') }}" class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" readonly required>
                    @error('latitud')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="longitud">Longitud</label>
                    <input id="longitud" name="longitud" type="number" step="any" value="{{ old('longitud') }}" class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" readonly required>
                    @error('longitud')
                        <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mb-4 bg-gray-50 p-3 rounded-md border border-gray-200 pointer-events-none opacity-70">
                <p class="text-xs text-gray-500 mb-2">Calculado automáticamente:</p>
                <x-location-filter idPrefix="form" />
            </div>

            <div class="-mt-1">
                <p id="locationStatus" class="text-sm text-gray-600"></p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="address">Dirección (opcional)</label>
                <input id="address" name="address" type="text" value="{{ old('address') }}" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900">
                @error('address')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="intensidad_actual">Intensidad</label>
                <select id="intensidad_actual" name="intensidad_actual" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>
                    @php($sev = old('intensidad_actual', 'baja'))
                    <option value="baja" @selected($sev === 'baja')>Baja</option>
                    <option value="media" @selected($sev === 'media')>Media</option>
                    <option value="alta" @selected($sev === 'alta')>Alta</option>
                </select>
                @error('intensidad_actual')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="description">Descripción</label>
                <textarea id="description" name="description" rows="5" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 shadow-sm focus:border-gray-900 focus:ring-1 focus:ring-gray-900" required>{{ old('description') }}</textarea>
                @error('description')
                    <div class="text-sm text-red-700 mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    Crear
                </button>
            </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const latInput = document.getElementById('latitud');
            const lngInput = document.getElementById('longitud');
            const locStatus = document.getElementById('locationStatus');

            let santaCruzPolygon = null;
            let provincesData = null;
            let municipalitiesData = null;

            fetch('/santacruz_boundary.json')
                .then(res => res.json())
                .then(geoJson => {
                    santaCruzPolygon = geoJson;
                });
            fetch('/provinces.geojson').then(res => res.json()).then(data => provincesData = data);
            fetch('/municipalities.geojson').then(res => res.json()).then(data => municipalitiesData = data);

            function fetchLocation() {
                if (!navigator.geolocation) {
                    locStatus.textContent = "Tu navegador no soporta geolocalización.";
                    locStatus.classList.replace('text-gray-600', 'text-red-600');
                    enableInputs();
                    return;
                }

                locStatus.textContent = "Obteniendo ubicación...";
                locStatus.className = "text-sm text-gray-600";
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    let foundProv = null;
                    let foundMuni = null;
                    
                    if (santaCruzPolygon && typeof turf !== 'undefined') {
                        const pt = turf.point([lng, lat]);
                        if (!turf.booleanPointInPolygon(pt, santaCruzPolygon)) {
                            locStatus.textContent = "Estás fuera de Santa Cruz. Por favor, selecciona una ubicación dentro del departamento.";
                            locStatus.classList.replace('text-gray-600', 'text-red-600');
                            enableInputs();
                            return;
                        }

                        if (provincesData && municipalitiesData) {
                            for (let feature of provincesData.features) {
                                if (turf.booleanPointInPolygon(pt, feature)) {
                                    foundProv = feature.properties.name;
                                    break;
                                }
                            }
                            for (let feature of municipalitiesData.features) {
                                if (turf.booleanPointInPolygon(pt, feature)) {
                                    foundMuni = feature.properties.name;
                                    break;
                                }
                            }
                        }
                    }
                    
                    latInput.value = lat;
                    lngInput.value = lng;
                    locStatus.textContent = "Ubicación obtenida exitosamente dentro de Santa Cruz.";
                    locStatus.classList.replace('text-gray-600', 'text-green-600');

                    if (foundProv) {
                        const provSelect = document.getElementById('form_provincia');
                        if (provSelect) {
                            provSelect.value = foundProv;
                            provSelect.dispatchEvent(new Event('change'));
                            
                            if (foundMuni) {
                                setTimeout(() => {
                                    const munSelect = document.getElementById('form_municipio');
                                    if (munSelect) {
                                        munSelect.value = foundMuni;
                                        munSelect.dispatchEvent(new Event('change'));
                                    }
                                }, 100);
                            }
                        }
                    }
                }, function(error) {
                    locStatus.textContent = "Error al obtener ubicación. Puedes ingresarla manualmente (asegúrate de que sea en Santa Cruz).";
                    locStatus.classList.replace('text-gray-600', 'text-red-600');
                    enableInputs();
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            }

            function enableInputs() {
                latInput.removeAttribute('readonly');
                latInput.classList.replace('bg-gray-50', 'bg-white');
                lngInput.removeAttribute('readonly');
                lngInput.classList.replace('bg-gray-50', 'bg-white');
            }

            if (!latInput.value || !lngInput.value) {
                fetchLocation();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
@endsection
