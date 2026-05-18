@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Mapa de Reportes</h1>
            <p class="mt-1 text-sm text-gray-600">Visualiza los reportes de inundación en tiempo real.</p>
        </div>
    </div>

    <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Buscar Reportes por Ubicación</h3>
        <x-location-filter formAction="{{ route('maps.index', [], false) }}" />
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative" style="height: 600px;">
        <div id="map" class="absolute inset-0 z-0"></div>
        
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
            <div id="radar-legend-cloud-colors" class="hidden space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-sky-200 opacity-60 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Nubes Dispersas</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-sky-600 opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Nubosidad Densa</span>
                </div>
            </div>
            <p class="mt-3 text-[10px] text-gray-400 text-center italic">Datos de OpenWeatherMap</p>
            <button type="button" onclick="window.mapObj.setView([-17.78, -63.18], 7)" class="mt-2 text-xs bg-blue-50 text-blue-600 px-2 py-1.5 rounded w-full border border-blue-100 hover:bg-blue-100 pointer-events-auto transition-colors font-medium">
                🌍 Alejar para ver completo
            </button>
        </div>
    </div>
</div>

<!-- LEAFLET CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<!-- LEAFLET HEATMAP PLUGIN -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
    window.floodReports = @json($reports);
    window.pendingReports = [];
    
    fetch('/api/reportes/pendientes')
        .then(res => res.json())
        .then(data => {
            window.pendingReports = data;
            renderPendingReports(data);
        });
    
    function initMap() { 
        const defaultLocation = [-17.783325, -63.182111]; // Centro de Santa Cruz de la Sierra, Bolivia 
        
        let centerLoc = defaultLocation;
        if (window.floodReports.length > 0) {
            for(let i=0; i<window.floodReports.length; i++) {
                 let lat = parseFloat(window.floodReports[i].latitud);
                 let lng = parseFloat(window.floodReports[i].longitud);
                 if(!isNaN(lat) && !isNaN(lng)) {
                     centerLoc = [lat, lng];
                     break;
                 }
            }
        }

        // 1. Inicializar Mapa de Leaflet (con Canvas para mejor rendimiento con muchas geometrías)
        const map = L.map('map', { preferCanvas: true }).setView(centerLoc, 12); window.mapObj = map;

        // 2. Cargar Capas Base (Normal y Satelital)
        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        });

        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        });

        // Añadir capa por defecto
        osmLayer.addTo(map);

        // Control de Capas
        const baseMaps = {
            "Mapa Normal (OSM)": osmLayer,
            "Satelital (Esri)": satelliteLayer
        };

        const overlayMaps = {};
        const layerControl = L.control.layers(baseMaps, overlayMaps).addTo(map);

        let markersLayer = L.layerGroup().addTo(map); // Por defecto activo
        let heatLayer = L.heatLayer([], {
            radius: 25,
            blur: 15,
            maxZoom: 17,
            gradient: { 0.4: 'blue', 0.6: 'cyan', 0.7: 'lime', 0.8: 'yellow', 1.0: 'red' }
        });
        
        layerControl.addOverlay(markersLayer, "Reportes (Puntos)");
        layerControl.addOverlay(heatLayer, "Mapa de Calor");

        // 4. Capas Meteorológicas (OpenWeatherMap)
        const precipLayer = L.layerGroup();
        const cloudLayer = L.layerGroup();
        layerControl.addOverlay(precipLayer, "Radar de Lluvia (OpenWeather)");
        layerControl.addOverlay(cloudLayer, "Nubes (OpenWeather)");

        // Limitamos los requests al bounding box de Santa Cruz y a un zoom máximo nativo de 8.
        const santaCruzBounds = [[-20.5, -64.8], [-13.5, -57.4]];
        
        L.tileLayer('/weather/tiles/precipitation_new/{z}/{x}/{y}', {
            opacity: 0.7,
            attribution: '&copy; OpenWeatherMap',
            bounds: santaCruzBounds,
            minZoom: 5,
            maxNativeZoom: 8,
            maxZoom: 18,
            updateWhenIdle: true
        }).addTo(precipLayer);

        L.tileLayer('/weather/tiles/clouds_new/{z}/{x}/{y}', {
            opacity: 0.5,
            attribution: '&copy; OpenWeatherMap',
            bounds: santaCruzBounds,
            minZoom: 5,
            maxNativeZoom: 8,
            maxZoom: 18,
            updateWhenIdle: true
        }).addTo(cloudLayer);

        // Eventos para mostrar/ocultar la leyenda del radar de lluvia
        map.on('overlayadd', function(e) {
            if (e.name === "Radar de Lluvia (OpenWeather)" || e.name === "Nubes (OpenWeather)") {
                document.getElementById('radar-legend').classList.remove('hidden');
                if (e.name === "Nubes (OpenWeather)") {
                    document.getElementById('radar-legend-title').innerHTML = '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Densidad de Nubes</span>';
                    document.getElementById('radar-legend-rain-colors').classList.add('hidden');
                    document.getElementById('radar-legend-cloud-colors').classList.remove('hidden');
                } else {
                    document.getElementById('radar-legend-title').innerHTML = '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Intensidad de Lluvia</span>';
                    document.getElementById('radar-legend-rain-colors').classList.remove('hidden');
                    document.getElementById('radar-legend-cloud-colors').classList.add('hidden');
                }
            }
        });
        map.on('overlayremove', function(e) {
            if (e.name === "Radar de Lluvia (OpenWeather)" || e.name === "Nubes (OpenWeather)") {
                if (!map.hasLayer(precipLayer) && !map.hasLayer(cloudLayer)) {
                    document.getElementById('radar-legend').classList.add('hidden');
                } else if (map.hasLayer(cloudLayer)) {
                    document.getElementById('radar-legend-title').innerHTML = '<svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Densidad de Nubes</span>';
                    document.getElementById('radar-legend-rain-colors').classList.add('hidden');
                    document.getElementById('radar-legend-cloud-colors').classList.remove('hidden');
                } else if (map.hasLayer(precipLayer)) {
                    document.getElementById('radar-legend-title').innerHTML = '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg><span>Intensidad de Lluvia</span>';
                    document.getElementById('radar-legend-rain-colors').classList.remove('hidden');
                    document.getElementById('radar-legend-cloud-colors').classList.add('hidden');
                }
            }
        });

        // 5. Red Hídrica (Canales de Drenaje)
        fetch('/red_hidrica_santa_cruz.json')
            .then(res => res.json())
            .then(data => {
                const hydroLayer = L.geoJSON(data, {
                    style: { color: '#0ea5e9', weight: 1.5, opacity: 0.8 },
                    interactive: false
                });
                layerControl.addOverlay(hydroLayer, "Red Hídrica");
            }).catch(e => console.warn("Error cargando red hídrica", e));

        function renderReports(reportsData) {
            // Actualizar Marcadores
            markersLayer.clearLayers();
            // Recolectar datos para heatmap
            const heatData = [];

            reportsData.forEach(report => {
                const lat = parseFloat(report.latitud);
                const lng = parseFloat(report.longitud);

                if (isNaN(lat) || isNaN(lng)) return;

                const severidad = report.intensidad_calculada || 'baja';
                
                // Preparar datos Heatmap (1.0 = Max, 0.6 = Medio, 0.3 = Bajo)
                let heatIntensity = 0.3;
                let markerColor = "#34A853"; // Green (baja)
                
                if (severidad === 'alta') {
                    markerColor = "#EA4335"; // Red
                    heatIntensity = 1.0;
                } else if (severidad === 'media') {
                    markerColor = "#FBBC05"; // Yellow
                    heatIntensity = 0.6;
                }

                // Ajustar calor según el quorum si existe (extra weight)
                if (report.quorum_total && report.quorum_total > 5) {
                    heatIntensity = Math.min(1.0, heatIntensity + (report.quorum_total * 0.02));
                }

                heatData.push([lat, lng, heatIntensity]);

                // Dibujar el marcador
                const customIcon = L.divIcon({
                    className: 'custom-leaflet-marker',
                    html: `<div style="background-color: ${markerColor}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const desc = report.description || 'Sin descripción.';
                const shortDesc = desc.substring(0, 100) + (desc.length > 100 ? '...' : '');

                const contentStr = `
                    <div class="max-w-xs">
                        <p class="font-semibold text-sm mb-1">${shortDesc}</p>
                        <p class="text-xs text-gray-600 mb-2"><b>Severidad:</b> ${severidad} | <b>Estado:</b> ${report.estado}</p>
                        <a href="/reports/${report.id}" class="text-xs text-blue-600 hover:underline">Ver detalle completo &rarr;</a>
                    </div>
                `;
                
                const marker = L.marker([lat, lng], { icon: customIcon })
                 .bindPopup(contentStr, { minWidth: 200 })
                 .on('click', function() {
                     map.flyTo([lat, lng], 15, { animate: true, duration: 1 });
                 });
                 
                markersLayer.addLayer(marker);
            });

            // Actualizar datos del Heatmap
            heatLayer.setLatLngs(heatData);
        }

        // 3. Pintar Reportes
        renderReports(window.floodReports);

        // -------------------------------------------------------------
        // Cargar geometr�as GeoJSON para resaltado de fronteras.
        // NOTA: A diferencia de logistics/index, aqu� NO hay clic para registrar;
        // estos datos solo se usan para el resaltado visual del filtro.
        // Las funciones de traducci�n (normalizeProvName, normalizeMuniName) est�n
        // definidas globalmente en layouts/app.blade.php y disponibles en toda la app.
        // -------------------------------------------------------------
        let provincesData = null;
        let municipalitiesData = null;
        let highlightLayer = null; // Capa activa de resaltado (naranja=provincia, rojo=municipio)

        let provincesOverlay = L.geoJSON(null, {
            style: { color: '#F97316', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
            interactive: false
        });
        let municipalitiesOverlay = L.geoJSON(null, {
            style: { color: '#EF4444', weight: 1.5, opacity: 0.8, fillOpacity: 0.05 },
            interactive: false
        });

        layerControl.addOverlay(provincesOverlay, "Fronteras Provinciales");
        layerControl.addOverlay(municipalitiesOverlay, "Fronteras Municipales");

        fetch('/provinces.geojson').then(res => res.json()).then(data => {
            provincesData = data;
            provincesOverlay.addData(data);
        });
        fetch('/municipalities.geojson').then(res => res.json()).then(data => {
            municipalitiesData = data;
            municipalitiesOverlay.addData(data);
        });

        // -------------------------------------------------------------
        // EVENTO CENTRAL DE FILTRADO: locationFilterChanged
        // -------------------------------------------------------------
        window.addEventListener('locationFilterChanged', function(e) {
            const { idPrefix, region, provincia, municipio } = e.detail;
            
            // Filtrado local SPA para reportes de inundación
            if (idPrefix === 'filter') {
                const filtered = window.floodReports.filter(r => {
                    if (region && window.geographicData && window.geographicData.regiones) {
                        const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
                        if (regData && r.municipio && !regData.municipios.includes(r.municipio)) return false;
                    }
                    if (provincia && r.provincia && r.provincia !== provincia) return false;
                    if (municipio && r.municipio && r.municipio !== municipio) return false;
                    return true;
                });
                renderReports(filtered);
            }

            if (highlightLayer) {
                map.removeLayer(highlightLayer);
                highlightLayer = null;
            }

            if (municipio && municipalitiesData) {
                // Buscar el polígono del municipio seleccionado (rojo #EF4444)
                // normalizeMuniName traduce el nombre crudo del GeoJSON ("Municipio Warnes")
                // al formato oficial limpio ("warnes") para compararlo con el valor del filtro.
                const feature = municipalitiesData.features.find(f => window.normalizeMuniName(f.properties.name) === municipio.toLowerCase());
                if (feature) {
                    highlightLayer = L.geoJSON(feature, {
                        style: { color: '#EF4444', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            } else if (provincia && provincesData) {
                // Buscar el polígono de la provincia seleccionada (naranja #F97316)
                // normalizeProvName maneja aliases como "Velasco" ? "José Miguel de Velasco"
                const feature = provincesData.features.find(f => window.normalizeProvName(f.properties.name) === provincia.toLowerCase());
                if (feature) {
                    highlightLayer = L.geoJSON(feature, {
                        style: { color: '#F97316', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
                        interactive: false
                    }).addTo(map);
                    map.fitBounds(highlightLayer.getBounds());
                }
            } else if (region && window.geographicData && municipalitiesData) {
                // Buscar los polígonos de todos los municipios de la región (púrpura #8B5CF6)
                const regData = window.geographicData.regiones.find(rg => rg.nombre === region);
                if (regData && regData.municipios) {
                    const features = municipalitiesData.features.filter(f => {
                        const mName = window.normalizeMuniName(f.properties.name);
                        return regData.municipios.some(rm => rm.toLowerCase() === mName);
                    });
                    if (features.length > 0) {
                        highlightLayer = L.geoJSON(features, {
                            style: { color: '#8B5CF6', weight: 3, opacity: 0.9, fillOpacity: 0.1 },
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

    // Leaflet init se dispara manual directo ya que no requiere URL callbacks
    document.addEventListener("DOMContentLoaded", initMap);

    if (navigator.geolocation && window.floodReports.length === 0) {
        // En un Leaflet real, podemos usar map.locate() pero para el plan se deja esto como placeholder
    }
</script>
@endsection
<script>window.renderPendingReports = function(pendingData) { pendingData.forEach(report => { const lat = parseFloat(report.lat_reporte); const lng = parseFloat(report.long_reporte); if (isNaN(lat) || isNaN(lng)) return; const customIcon = L.divIcon({ className: 'custom-leaflet-marker', html: '<div style="background-color: #F59E0B; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5); animation: pulse 2s infinite;"></div>', iconSize: [16, 16], iconAnchor: [8, 8] }); const contentStr = '<div class="max-w-xs"><p class="font-semibold text-sm mb-1 text-orange-600">Reporte Pendiente</p><p class="text-xs text-gray-600 mb-2"><b>Intensidad Propuesta:</b> ' + report.intensidad_propuesta + '</p><div class="flex flex-col space-y-2 mt-2"><button onclick="validateReport(' + report.id + ', ''vincular'');" class="bg-blue-500 text-white px-2 py-1 text-xs rounded">Vincular a Cercana</button><button onclick="validateReport(' + report.id + ', ''crear'');" class="bg-green-500 text-white px-2 py-1 text-xs rounded">Crear Nueva</button><button onclick="validateReport(' + report.id + ', ''rechazar'');" class="bg-red-500 text-white px-2 py-1 text-xs rounded">Rechazar</button></div></div>'; const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(contentStr, { minWidth: 200 }); window.mapObj.addLayer(marker); }); }; window.validateReport = function(id, action) { let body = { action: action }; if (action === 'vincular') { const inundación_id = prompt('Ingrese el ID de la inundación a la que desea vincular:'); if (!inundación_id) return; body.inundación_id = inundación_id; } fetch('/api/reportes/' + id + '/validar', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': 'Bearer {{ session('api_token') }}' }, body: JSON.stringify(body) }).then(res => res.json()).then(data => { alert(data.message); location.reload(); }); };</script>
