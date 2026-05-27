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

    <div id="map-container" class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden relative" style="height: 600px;">
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
        <!-- LEYENDA DEL MAPA DE CALOR INTELIGENTE -->
        <div id="heatmap-legend" class="absolute bottom-6 right-6 bg-white/95 backdrop-blur p-4 rounded-xl shadow-xl border border-gray-100 z-[1000] pointer-events-none transition-all duration-300 min-w-[200px]">
            <h4 class="text-xs font-bold text-gray-800 mb-3 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Calor Inteligente</span>
            </h4>
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-[#1e3a8a] opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Severidad Alta (Azul Oscuro)</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-[#0ea5e9] opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Severidad Media (Celeste)</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-5 h-5 rounded-md bg-[#2dd4bf] opacity-80 shadow-inner"></div>
                    <span class="text-xs text-gray-600 font-medium">Severidad Baja (Turquesa)</span>
                </div>
                <hr class="border-gray-100 my-2" />
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full bg-[#2563eb] border-2 border-white shadow"></div>
                    <span class="text-xs text-gray-600 font-medium">Centro de Inundación</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#60a5fa] border border-white shadow"></div>
                    <span class="text-xs text-gray-600 font-medium">Reportes Ciudadanos</span>
                </div>
            </div>
            <p class="mt-3 text-[10px] text-gray-400 text-center italic leading-tight">Zonas modeladas con topografía real (OpenTopoMap / SRTM)</p>
        </div>
    </div>
    
    <!-- Panel de Rutas Seguras -->
    <x-routing-panel />
</div>

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
window.floodReports = @json($reports);
window.pendingReports = [];

fetch('/api/reportes/pendientes', {
    headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer {{ session("api_token") }}'
    }
})
    .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
    })
    .then(data => {
        window.pendingReports = data;
        if (window.renderPendingReports) window.renderPendingReports(data);
    })
    .catch(err => console.error("Error fetching pending reports:", err));

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
@endsection