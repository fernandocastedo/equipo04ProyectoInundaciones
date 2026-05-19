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
    const layerControl = L.control.layers(baseMaps, overlayMaps, { collapsed: false }).addTo(map);

    // ── 3a. Capas de Reportes ─────────────────────────────────────────────
    const markersLayer    = L.layerGroup().addTo(map);
    const polygonLayer    = L.layerGroup().addTo(map); // Polígonos inteligentes (activo por defecto)
    const heatLayer       = L.heatLayer([], {
        radius: 28,
        blur: 18,
        maxZoom: 17,
        gradient: { 0.3: '#3b82f6', 0.5: '#06b6d4', 0.65: '#84cc16', 0.8: '#f59e0b', 1.0: '#ef4444' }
    });

    layerControl.addOverlay(markersLayer, "Reportes (Puntos)");
    layerControl.addOverlay(polygonLayer, "Zonas de Inundación");
    layerControl.addOverlay(heatLayer,    "Mapa de Calor (clásico)");

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
     * Paleta de colores por intensidad de inundación.
     * Usada tanto para polígonos como para marcadores y heatmap.
     */
    const INTENSITY_PALETTE = {
        alta:  { fill: '#dc2626', stroke: '#991b1b', marker: '#EA4335', heat: 1.0 },
        media: { fill: '#f59e0b', stroke: '#b45309', marker: '#FBBC05', heat: 0.6 },
        baja:  { fill: '#22c55e', stroke: '#15803d', marker: '#34A853', heat: 0.3 },
        null:  { fill: '#6b7280', stroke: '#374151', marker: '#9CA3AF', heat: 0.2 },
    };

    function getPalette(intensidad) {
        return INTENSITY_PALETTE[intensidad] || INTENSITY_PALETTE['null'];
    }

    function renderReports(reportsData) {
        markersLayer.clearLayers();
        polygonLayer.clearLayers();
        const heatData = [];

        reportsData.forEach(report => {
            const lat = parseFloat(report.latitud);
            const lng = parseFloat(report.longitud);
            if (isNaN(lat) || isNaN(lng)) return;

            const intensidad = report.intensidad_calculada || 'baja';
            const palette    = getPalette(intensidad);

            // ── Peso para el heatmap ───────────────────────────────────────
            let heatIntensity = palette.heat;
            if (report.quorum_total && report.quorum_total > 5) {
                heatIntensity = Math.min(1.0, heatIntensity + (report.quorum_total * 0.015));
            }
            heatData.push([lat, lng, heatIntensity]);

            // ── Popup informativo ─────────────────────────────────────────
            const confirmadoBadge = report.esta_confirmada
                ? '<span class="inline-flex items-center gap-1 bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full">✓ Confirmada</span>'
                : '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-0.5 rounded-full">⏳ En espera</span>';

            const intensidadBadgeColor = { alta: 'red', media: 'yellow', baja: 'green' }[intensidad] || 'gray';
            const intensidadBadge = `<span class="inline-flex items-center bg-${intensidadBadgeColor}-100 text-${intensidadBadgeColor}-800 text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize">${intensidad}</span>`;

            const desc        = report.description || 'Sin descripción.';
            const shortDesc   = desc.length > 120 ? desc.substring(0, 120) + '…' : desc;
            const quorumStr   = report.quorum_total !== undefined ? `<b>Quórum:</b> ${report.quorum_total} pts` : '';
            const polygonNote = report.polygon_coords
                ? '<p class="text-[10px] text-blue-600 mt-1">🌊 Polígono calculado por elevación</p>'
                : '<p class="text-[10px] text-gray-400 mt-1">⏳ Calculando zona de impacto…</p>';

            const popupContent = `
                <div class="max-w-[240px] font-sans">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        ${intensidadBadge}
                        ${confirmadoBadge}
                    </div>
                    <p class="text-xs text-gray-700 mb-1 leading-snug">${shortDesc}</p>
                    <p class="text-xs text-gray-500">${quorumStr}</p>
                    ${polygonNote}
                    <a href="/reports/${report.id}" class="block mt-2 text-center text-xs text-blue-600 hover:underline font-medium">Ver detalle completo →</a>
                </div>`;

            // ── Marcador de punto ─────────────────────────────────────────
            const customIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="background-color:${palette.marker};width:18px;height:18px;border-radius:50%;border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
                iconSize: [18, 18], iconAnchor: [9, 9]
            });

            const marker = L.marker([lat, lng], { icon: customIcon })
                .bindPopup(popupContent, { minWidth: 220 })
                .on('click', () => map.flyTo([lat, lng], 15, { animate: true, duration: 0.8 }));

            markersLayer.addLayer(marker);

            // ── Polígono inteligente (si está disponible) ─────────────────
            if (report.polygon_coords && Array.isArray(report.polygon_coords) && report.polygon_coords.length >= 3) {
                // Los coords están en formato [[lat, lng], ...] — directo para Leaflet
                const floodPolygon = L.polygon(report.polygon_coords, {
                    color:       palette.stroke,
                    fillColor:   palette.fill,
                    weight:      2,
                    opacity:     0.85,
                    fillOpacity: 0.45,
                    smoothFactor: 1.5,
                });

                // Animación de pulso para alta intensidad
                if (intensidad === 'alta') {
                    floodPolygon.on('add', function () {
                        const el = this.getElement();
                        if (el) el.closest('.leaflet-overlay-pane')
                            ?.querySelectorAll(`path`)
                            ?.forEach(p => p.style.animation = 'flood-pulse 2.5s ease-in-out infinite');
                    });
                }

                floodPolygon.bindPopup(popupContent, { minWidth: 220 });
                floodPolygon.on('click', () => map.flyTo([lat, lng], 14, { animate: true, duration: 0.8 }));
                polygonLayer.addLayer(floodPolygon);
            } else {
                // Fallback: círculo semitransparente cuando no hay polígono calculado aún
                const circle = L.circle([lat, lng], {
                    radius:      120,
                    color:       palette.stroke,
                    fillColor:   palette.fill,
                    weight:      1.5,
                    opacity:     0.6,
                    fillOpacity: 0.25,
                    dashArray:   '6,4', // punteado para indicar que es estimado
                });
                circle.bindPopup(popupContent, { minWidth: 220 });
                polygonLayer.addLayer(circle);
            }
        });

        // Actualizar Heatmap
        heatLayer.setLatLngs(heatData);
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