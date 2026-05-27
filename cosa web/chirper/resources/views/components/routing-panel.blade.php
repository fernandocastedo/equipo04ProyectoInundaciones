<div id="routing-panel" class="absolute top-32 left-4 pointer-events-auto bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-2xl border border-gray-100 z-[1000] w-80 transition-all duration-300">
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
                <!-- Espaciador invisible para igualar el largo del input de arriba que tiene el botón GPS -->
                <div class="w-[34px] shrink-0"></div>
            </div>
        </div>
        
        <!-- Modos de Transporte -->
        <div class="flex bg-gray-100 p-1 rounded-lg mt-3">
            <button id="mode-car" data-mode="driving-car" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-emerald-700 bg-white shadow-sm transition-all" title="Auto">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M199.2 181.4L173.1 256L466.9 256L440.8 181.4C436.3 168.6 424.2 160 410.6 160L229.4 160C215.8 160 203.7 168.6 199.2 181.4zM103.6 260.8L138.8 160.3C152.3 121.8 188.6 96 229.4 96L410.6 96C451.4 96 487.7 121.8 501.2 160.3L536.4 260.8C559.6 270.4 576 293.3 576 320L576 512C576 529.7 561.7 544 544 544L512 544C494.3 544 480 529.7 480 512L480 480L160 480L160 512C160 529.7 145.7 544 128 544L96 544C78.3 544 64 529.7 64 512L64 320C64 293.3 80.4 270.4 103.6 260.8zM192 368C192 350.3 177.7 336 160 336C142.3 336 128 350.3 128 368C128 385.7 142.3 400 160 400C177.7 400 192 385.7 192 368zM480 400C497.7 400 512 385.7 512 368C512 350.3 497.7 336 480 336C462.3 336 448 350.3 448 368C448 385.7 462.3 400 480 400z"/></svg>
            </button>
            <button id="mode-bike" data-mode="cycling-regular" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="Bicicleta">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M331.7 107.3C336 100.3 343.7 96 352 96L456 96C469.3 96 480 106.7 480 120C480 133.3 469.3 144 456 144L390.4 144L462.6 292.4C473.3 289.5 484.5 288 496 288C566.7 288 624 345.3 624 416C624 486.7 566.7 544 496 544C425.3 544 368 486.7 368 416C368 374 388.2 336.8 419.4 313.4L399 271.5L325.5 418.5C323.2 423.3 319.2 427.3 314.1 429.7C313.5 430 312.9 430.2 312.3 430.4C309.4 431.5 306.4 432 303.4 431.9L271 432C263.1 495.1 209.3 544 144 544C73.3 544 16 486.7 16 416C16 345.3 73.3 288 144 288C154.8 288 165.2 289.3 175.2 291.8L203.7 234.9L192.2 208L152 208C138.7 208 128 197.3 128 184C128 170.7 138.7 160 152 160L208 160C217.6 160 226.3 165.7 230.1 174.5L244.4 208L368.1 208L330.4 130.5C326.8 123.1 327.2 114.3 331.6 107.3zM228.5 292.7L182.9 384L267.7 384L228.6 292.7zM305.7 351L353.2 256L265 256L305.7 351zM474.4 426.5L444.7 365.5C431.9 378.5 424 396.3 424 416C424 455.8 456.2 488 496 488C535.8 488 568 455.8 568 416C568 376.2 535.8 344 496 344C493.3 344 490.5 344.2 487.9 344.5L517.6 405.5C523.4 417.4 518.4 431.8 506.5 437.6C494.6 443.4 480.2 438.4 474.4 426.5zM149.2 432C129 432 115.8 410.7 124.9 392.6L149.1 344.1C147.4 344 145.7 343.9 144 343.9C104.2 343.9 72 376.1 72 415.9C72 455.7 104.2 487.9 144 487.9C178.3 487.9 206.9 464 214.2 431.9L149.2 431.9z"/></svg>
            </button>
            <button id="mode-foot" data-mode="foot-walking" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="A pie">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M320 144C350.9 144 376 118.9 376 88C376 57.1 350.9 32 320 32C289.1 32 264 57.1 264 88C264 118.9 289.1 144 320 144zM233.4 291.9L256 269.3L256 338.6C256 366.6 268.2 393.3 289.5 411.5L360.9 472.7C366.8 477.8 370.7 484.8 371.8 492.5L384.4 580.6C386.9 598.1 403.1 610.3 420.6 607.8C438.1 605.3 450.3 589.1 447.8 571.6L435.2 483.5C431.9 460.4 420.3 439.4 402.6 424.2L368.1 394.6L368.1 279.4L371.9 284.1C390.1 306.9 417.7 320.1 446.9 320.1L480.1 320.1C497.8 320.1 512.1 305.8 512.1 288.1C512.1 270.4 497.8 256.1 480.1 256.1L446.9 256.1C437.2 256.1 428 251.7 421.9 244.1L404 221.7C381 192.9 346.1 176.1 309.2 176.1C277 176.1 246.1 188.9 223.4 211.7L188.1 246.6C170.1 264.6 160 289 160 314.5L160 352C160 369.7 174.3 384 192 384C209.7 384 224 369.7 224 352L224 314.5C224 306 227.4 297.9 233.4 291.9zM245.8 471.3C244.3 476.5 241.5 481.3 237.7 485.1L169.4 553.4C156.9 565.9 156.9 586.2 169.4 598.7C181.9 611.2 202.2 611.2 214.7 598.7L283 530.4C294.5 518.9 302.9 504.6 307.4 488.9L309.6 481.3L263.6 441.9C261.1 439.7 258.6 437.5 256.2 435.1L245.8 471.3z"/></svg>
            </button>
        </div>

        <div class="pt-2">
            <button id="btn-calculate-route" disabled class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2.5 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2">
                <span>Calcular Ruta Alterna</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
        
        <div class="mt-4 border-t border-gray-100 pt-3">
            <button id="btn-clear-route" class="w-full text-xs text-gray-600 bg-red-50 hover:text-red-700 hover:bg-red-100 border border-gray-800 hover:border-red-400 font-bold py-2 rounded-xl transition-all hidden">Limpiar Ruta Completa</button>
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
<button id="btn-open-routing" class="hidden absolute top-32 left-4 pointer-events-auto bg-emerald-600 text-white p-3 rounded-full shadow-xl hover:bg-emerald-700 hover:-translate-y-0.5 transition-all z-[900]">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
</button>
