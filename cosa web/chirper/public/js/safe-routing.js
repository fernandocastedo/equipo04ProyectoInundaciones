document.addEventListener('DOMContentLoaded', function () {
    // Solo inicializar si el mapa existe y tenemos la API Key
    if (!window.ORS_API_KEY) {
        console.warn("OpenRouteService API Key no configurada.");
        return;
    }

    const panel = document.getElementById('routing-panel');
    if (!panel) return;

    let routeStartMarker = null;
    let routeEndMarker = null;
    let routeLayer = null;
    let selectingMode = null; // 'start' o 'end'
    let currentTransportMode = 'driving-car'; // por defecto auto

    const inputStart = document.getElementById('route-start-input');
    const inputEnd = document.getElementById('route-end-input');
    const btnCalc = document.getElementById('btn-calculate-route');
    const btnClear = document.getElementById('btn-clear-route');
    const resultsDiv = document.getElementById('route-results');
    const loadingDiv = document.getElementById('routing-loading');
    
    // Botones individuales
    const btnFocusStart = document.getElementById('btn-focus-start');
    const btnClearStart = document.getElementById('btn-clear-start');
    const btnFocusEnd = document.getElementById('btn-focus-end');
    const btnClearEnd = document.getElementById('btn-clear-end');

    // UI Toggles
    document.getElementById('toggle-routing-panel').addEventListener('click', () => {
        panel.classList.add('-translate-x-96', 'opacity-0');
        setTimeout(() => {
            panel.classList.add('hidden');
            document.getElementById('btn-open-routing').classList.remove('hidden');
        }, 300);
    });

    document.getElementById('btn-open-routing').addEventListener('click', (e) => {
        e.target.closest('button').classList.add('hidden');
        panel.classList.remove('hidden');
        setTimeout(() => {
            panel.classList.remove('-translate-x-96', 'opacity-0');
        }, 10);
    });

    // Transport Mode Selection
    const transportBtns = document.querySelectorAll('.transport-btn');
    transportBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Update active state classes
            transportBtns.forEach(b => {
                b.classList.remove('text-emerald-700', 'bg-white', 'shadow-sm');
                b.classList.add('text-gray-500', 'hover:text-gray-700');
            });
            const clicked = e.currentTarget;
            clicked.classList.remove('text-gray-500', 'hover:text-gray-700');
            clicked.classList.add('text-emerald-700', 'bg-white', 'shadow-sm');
            
            currentTransportMode = clicked.getAttribute('data-mode');
            
            // Recalcular automáticamente si los puntos ya están seteados
            if (!btnCalc.disabled && routeStartMarker && routeEndMarker) {
                btnCalc.click();
            }
        });
    });

    // Activar modo de selección al hacer clic en los inputs
    inputStart.addEventListener('click', () => {
        selectingMode = 'start';
        inputStart.classList.add('ring-2', 'ring-emerald-500', 'bg-white');
        inputEnd.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
    });

    inputEnd.addEventListener('click', () => {
        selectingMode = 'end';
        inputEnd.classList.add('ring-2', 'ring-emerald-500', 'bg-white');
        inputStart.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
    });

    // Parsear coordenadas escritas a mano
    function parseAndSetFromInput(type, inputEl) {
        const val = inputEl.value;
        const parts = val.split(',').map(s => parseFloat(s.trim()));
        if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
            setMarker(type, parts[0], parts[1]);
            window.mapObj.setView([parts[0], parts[1]], 15);
        }
    }
    inputStart.addEventListener('change', () => parseAndSetFromInput('start', inputStart));
    inputEnd.addEventListener('change', () => parseAndSetFromInput('end', inputEnd));

    // Geolocalización
    document.getElementById('btn-use-location').addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert("Tu navegador no soporta geolocalización.");
            return;
        }
        inputStart.value = "Obteniendo ubicación...";
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setMarker('start', lat, lng);
                window.mapObj.setView([lat, lng], 15);
            },
            (error) => {
                alert("Error obteniendo ubicación: " + error.message);
                inputStart.value = "";
            }
        );
    });

    // Evento de clic en el mapa
    let mapInterval = setInterval(() => {
        if (window.mapObj) {
            clearInterval(mapInterval);
            
            window.mapObj.on('click', function (e) {
                if (!selectingMode) {
                    // Si no hay modo activo, activar alternativamente
                    if (!routeStartMarker) selectingMode = 'start';
                    else if (!routeEndMarker) selectingMode = 'end';
                    else return; // Ya están los dos
                }

                setMarker(selectingMode, e.latlng.lat, e.latlng.lng);
                
                // Cambiar al otro input si falta
                if (selectingMode === 'start' && !routeEndMarker) {
                    selectingMode = 'end';
                    inputEnd.click();
                } else {
                    selectingMode = null;
                    inputStart.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
                    inputEnd.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
                }
            });
        }
    }, 500);

    // Función para poner o mover el marcador
    function setMarker(type, lat, lng) {
        const iconColor = type === 'start' ? '#10b981' : '#ef4444'; // Emerald : Red
        const labelText = type === 'start' ? 'Punto de Partida' : 'Destino';
        const svgPin = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${iconColor}" width="36" height="36" stroke="white" stroke-width="1.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>`;
        
        const customIcon = L.divIcon({
            className: 'custom-routing-marker',
            html: `<div style="filter: drop-shadow(0px 3px 3px rgba(0,0,0,0.4)); transform: translateY(-4px);">${svgPin}</div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
            tooltipAnchor: [18, -18]
        });

        if (type === 'start') {
            if (routeStartMarker) window.mapObj.removeLayer(routeStartMarker);
            routeStartMarker = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(window.mapObj);
            routeStartMarker.bindTooltip(labelText, { direction: 'right', className: 'font-bold text-gray-700' });
            routeStartMarker.on('dragend', (e) => setMarker('start', e.target.getLatLng().lat, e.target.getLatLng().lng));
            inputStart.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            btnFocusStart.classList.remove('hidden');
            btnClearStart.classList.remove('hidden');
        } else {
            if (routeEndMarker) window.mapObj.removeLayer(routeEndMarker);
            routeEndMarker = L.marker([lat, lng], { icon: customIcon, draggable: true }).addTo(window.mapObj);
            routeEndMarker.bindTooltip(labelText, { direction: 'right', className: 'font-bold text-gray-700' });
            routeEndMarker.on('dragend', (e) => setMarker('end', e.target.getLatLng().lat, e.target.getLatLng().lng));
            inputEnd.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            btnFocusEnd.classList.remove('hidden');
            btnClearEnd.classList.remove('hidden');
        }

        checkReadyToCalculate();
    }

    function checkReadyToCalculate() {
        if (routeStartMarker && routeEndMarker) {
            btnCalc.disabled = false;
        } else {
            btnCalc.disabled = true;
        }
    }

    // Botones de enfoque y borrado individual
    btnFocusStart.addEventListener('click', () => { if (routeStartMarker) window.mapObj.flyTo(routeStartMarker.getLatLng(), 15); });
    btnFocusEnd.addEventListener('click', () => { if (routeEndMarker) window.mapObj.flyTo(routeEndMarker.getLatLng(), 15); });

    btnClearStart.addEventListener('click', () => {
        if (routeStartMarker) window.mapObj.removeLayer(routeStartMarker);
        routeStartMarker = null;
        inputStart.value = '';
        btnFocusStart.classList.add('hidden');
        btnClearStart.classList.add('hidden');
        checkReadyToCalculate();
        if (routeLayer) clearEntireRoute();
    });

    btnClearEnd.addEventListener('click', () => {
        if (routeEndMarker) window.mapObj.removeLayer(routeEndMarker);
        routeEndMarker = null;
        inputEnd.value = '';
        btnFocusEnd.classList.add('hidden');
        btnClearEnd.classList.add('hidden');
        checkReadyToCalculate();
        if (routeLayer) clearEntireRoute();
    });

    function clearEntireRoute() {
        if (routeLayer) window.mapObj.removeLayer(routeLayer);
        routeLayer = null;
        resultsDiv.classList.add('hidden');
        btnClear.classList.add('hidden');
    }

    // Funciones Auxiliares para Generar Polígonos de Evasión
    function createCirclePolygon(lat, lng, radiusInMeters, points = 16) {
        // Fórmula aproximada para generar un polígono circular
        const R = 6378137; // Radio de la Tierra
        const latRad = lat * Math.PI / 180;
        const poly = [];
        for (let i = 0; i <= points; i++) {
            const theta = (i / points) * (2 * Math.PI);
            const dx = radiusInMeters * Math.cos(theta);
            const dy = radiusInMeters * Math.sin(theta);
            const pLat = lat + (dy / R) * (180 / Math.PI);
            const pLng = lng + (dx / (R * Math.cos(latRad))) * (180 / Math.PI);
            poly.push([pLng, pLat]); // ORS y GeoJSON usan [Lng, Lat]
        }
        return [poly]; // MultiPolygon requiere anillos anidados
    }

    function getAvoidPolygons() {
        const polygons = [];
        const allReports = (window.floodReports || []).concat(window.pendingReports || []);
        
        allReports.forEach(report => {
            const lat = parseFloat(report.latitud || report.lat_reporte);
            const lng = parseFloat(report.longitud || report.long_reporte);
            if (isNaN(lat) || isNaN(lng)) return;

            if (report.polygon_coords && Array.isArray(report.polygon_coords) && report.polygon_coords.length > 2) {
                // El backend guarda [[lat, lng], [lat, lng]]
                // ORS requiere [[lng, lat], [lng, lat]]
                const ring = report.polygon_coords.map(coord => [parseFloat(coord[1]), parseFloat(coord[0])]);
                
                // Asegurar que el polígono esté cerrado (primer y último punto idénticos)
                if (ring[0][0] !== ring[ring.length - 1][0] || ring[0][1] !== ring[ring.length - 1][1]) {
                    ring.push([...ring[0]]);
                }
                polygons.push([ring]);
            } else {
                // Radio dinámico según intensidad
                let radius = 100; // baja o null
                let intensidad = report.intensidad_calculada || report.intensidad_propuesta;
                if (intensidad === 'alta') radius = 300;
                else if (intensidad === 'media') radius = 150;
                
                polygons.push(createCirclePolygon(lat, lng, radius));
            }
        });

        return polygons;
    }

    // Calcular Ruta Segura
    btnCalc.addEventListener('click', async () => {
        if (!routeStartMarker || !routeEndMarker) return;

        loadingDiv.classList.remove('hidden');
        loadingDiv.classList.add('flex');
        btnCalc.disabled = true;

        const startCoords = [routeStartMarker.getLatLng().lng, routeStartMarker.getLatLng().lat];
        const endCoords = [routeEndMarker.getLatLng().lng, routeEndMarker.getLatLng().lat];
        const avoidPolys = getAvoidPolygons();

        const requestBody = {
            coordinates: [startCoords, endCoords],
            radiuses: [-1, -1]
        };

        if (avoidPolys.length > 0) {
            requestBody.options = {
                avoid_polygons: {
                    type: "MultiPolygon",
                    coordinates: avoidPolys
                }
            };
        }

        try {
            const response = await fetch(`https://api.openrouteservice.org/v2/directions/${currentTransportMode}/geojson`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
                    'Content-Type': 'application/json',
                    'Authorization': window.ORS_API_KEY
                },
                body: JSON.stringify(requestBody)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error ? data.error.message : 'Error calculando ruta');
            }

            if (routeLayer) window.mapObj.removeLayer(routeLayer);

            routeLayer = L.geoJSON(data, {
                style: function (feature) {
                    return { color: '#059669', weight: 6, opacity: 0.8, lineCap: 'round', lineJoin: 'round' };
                }
            }).addTo(window.mapObj);

            // Ajustar vista para ver toda la ruta
            window.mapObj.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

            // Extraer estadísticas
            const summary = data.features[0].properties.summary;
            document.getElementById('route-distance').innerText = (summary.distance / 1000).toFixed(2) + ' km';
            document.getElementById('route-duration').innerText = Math.round(summary.duration / 60) + ' min';
            
            resultsDiv.classList.remove('hidden');
            btnClear.classList.remove('hidden');

        } catch (error) {
            console.error(error);
            alert("No se pudo calcular la ruta: " + error.message);
        } finally {
            loadingDiv.classList.add('hidden');
            loadingDiv.classList.remove('flex');
            btnCalc.disabled = false;
        }
    });

    btnClear.addEventListener('click', () => {
        clearEntireRoute();
        if (routeStartMarker) window.mapObj.removeLayer(routeStartMarker);
        if (routeEndMarker) window.mapObj.removeLayer(routeEndMarker);
        routeStartMarker = null;
        routeEndMarker = null;
        inputStart.value = '';
        inputEnd.value = '';
        btnFocusStart.classList.add('hidden');
        btnClearStart.classList.add('hidden');
        btnFocusEnd.classList.add('hidden');
        btnClearEnd.classList.add('hidden');
        btnCalc.disabled = true;
        selectingMode = null;
        inputStart.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
        inputEnd.classList.remove('ring-2', 'ring-emerald-500', 'bg-white');
    });

    // --- Dragging Logic for the Panel ---
    const panelHeader = document.getElementById('routing-panel-header');
    let isDragging = false;
    let offsetX = 0, offsetY = 0;

    panelHeader.addEventListener('mousedown', (e) => {
        // Evitar que el drag inicie si se hace clic en botones u otros interactivos
        if(e.target.closest('button')) return;
        isDragging = true;
        
        // Obtener dimensiones actuales y posición
        const rect = panel.getBoundingClientRect();
        
        // Calcular la distancia desde el cursor hasta la esquina superior izquierda del panel (usando el offset local del elemento respecto a su contenedor)
        offsetX = e.clientX - panel.offsetLeft;
        offsetY = e.clientY - panel.offsetTop;
        
        // Desactivar transiciones para que el drag sea suave e instantáneo
        panel.style.transition = 'none';
        
        // Cambiar posicionamiento a absoluto respecto a la ventana en vez de clases de Tailwind (right-6 top-X)
        panel.style.right = 'auto';
        panel.style.bottom = 'auto';
        
        // Fijar la posición actual inicial usando offsets locales
        panel.style.left = panel.offsetLeft + 'px';
        panel.style.top = panel.offsetTop + 'px';
        
        document.body.style.userSelect = 'none'; // Evitar seleccionar texto al arrastrar
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        
        // Mover el panel
        let newX = e.clientX - offsetX;
        let newY = e.clientY - offsetY;
        
        // Limitar dentro del contenedor del mapa
        const container = panel.parentElement;
        newX = Math.max(0, Math.min(newX, container.offsetWidth - panel.offsetWidth));
        newY = Math.max(0, Math.min(newY, container.offsetHeight - panel.offsetHeight));

        panel.style.left = newX + 'px';
        panel.style.top = newY + 'px';
    });

    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            // Restaurar estilo
            panel.style.transition = 'opacity 0.3s'; // Quitar transform de la transición para no desfasar
            document.body.style.userSelect = '';
        }
    });

});
