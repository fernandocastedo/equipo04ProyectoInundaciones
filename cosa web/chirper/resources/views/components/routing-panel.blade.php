<div id="routing-panel" class="absolute top-[130px] left-3 bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-2xl border border-gray-100 z-[1000] w-80 transition-all duration-300">
    <div id="routing-panel-header" class="flex justify-between items-center mb-4 cursor-move" title="Arrastrar panel">
        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Rutas Seguras
        </h3>
        <button id="toggle-routing-panel" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <div class="space-y-4">
        <!-- Origen -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Punto de Partida</label>
            <div class="flex gap-1.5 items-center">
                <input type="text" id="route-start-input" placeholder="Clic mapa o escribe Lat, Lng" class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-inner">
                
                <button id="btn-focus-start" title="Centrar mapa en origen" class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors border border-transparent hover:border-blue-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </button>
                <button id="btn-clear-start" title="Borrar origen" class="text-red-500 hover:bg-red-100 p-2 rounded-lg transition-colors border border-transparent hover:border-red-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>

                <button id="btn-use-location" title="Usar mi ubicación GPS" class="bg-blue-50 text-blue-600 border border-blue-200 p-2 rounded-lg hover:bg-blue-100 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </button>
            </div>
        </div>

        <!-- Destino -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Destino</label>
            <div class="flex gap-1.5 items-center">
                <input type="text" id="route-end-input" placeholder="Clic mapa o escribe Lat, Lng" class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-inner">
                
                <button id="btn-focus-end" title="Centrar mapa en destino" class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors border border-transparent hover:border-blue-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </button>
                <button id="btn-clear-end" title="Borrar destino" class="text-red-500 hover:bg-red-100 p-2 rounded-lg transition-colors border border-transparent hover:border-red-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
            <button id="btn-calculate-route" disabled class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2.5 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2">
                <span>Calcular Ruta Alterna</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
        
        <div class="flex justify-between items-center mt-2">
            <button id="btn-clear-route" class="text-xs text-gray-500 hover:text-red-500 font-medium transition-colors hidden">Limpiar ruta</button>
        </div>
    </div>

    <!-- Resultados -->
    <div id="route-results" class="mt-4 pt-4 border-t border-gray-100 hidden">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-gray-600">Distancia:</span>
            <span id="route-distance" class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">0 km</span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-600">Tiempo est.:</span>
            <span id="route-duration" class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded">0 min</span>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div id="routing-loading" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl flex-col items-center justify-center hidden z-10">
        <svg class="animate-spin h-8 w-8 text-emerald-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-xs font-bold text-emerald-700">Trazando ruta...</span>
    </div>
</div>

<!-- Botón para reabrir el panel si se cierra -->
<button id="btn-open-routing" class="hidden absolute top-[130px] left-3 bg-emerald-600 text-white p-3 rounded-full shadow-xl hover:bg-emerald-700 hover:-translate-y-0.5 transition-all z-[900]">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
</button>
