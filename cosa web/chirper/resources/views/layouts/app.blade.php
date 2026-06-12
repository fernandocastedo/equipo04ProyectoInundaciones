<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Flood Reports') }}</title>
    <meta name="api-user-role" content="{{ $apiRole }}">
    @if (session()->has('api_token'))
        <meta name="reports-notifications-endpoint" content="{{ route('reports.notifications.feed', [], false) }}">
        <meta name="api-user-carnet" content="{{ (string) ($apiUser['carnet'] ?? '') }}">
        <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
        <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host', '127.0.0.1') }}">
        <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port', 8080) }}">
        <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme', 'http') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        /* Ocultar barra de desplazamiento en todos los navegadores para elementos con .no-scrollbar */
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;  /* IE y Edge */
            scrollbar-width: none !important;  /* Firefox */
        }
    </style>

    <script>
        // Normaliza nombre de PROVINCIA desde el GeoJSON al nombre oficial
        window.normalizeProvName = function(name) {
            let n = name.replace(/Provincia\s+/i, '').trim().toLowerCase();
            const dict = {
                'velasco':            'josé miguel de velasco',
                'warnes':             'ignacio warnes',
                'manuel m. caballero':'manuel maría caballero'
            };
            return dict[n] || n;
        };

        // Normaliza nombre de MUNICIPIO desde el GeoJSON al nombre oficial
        window.normalizeMuniName = function(name) {
            let n = name.replace(/Municipio\s+/i, '').trim().toLowerCase();
            const dict = {
                'ascención de guarayos': 'ascensión de guarayos',
                'san antonio de lomerio':'san antonio de lomerío',
                'san rafael':            'san rafael de velasco',
                'charagua':              'charagua iyambae',
                'gutiérrez':             'kereimba iyaambae',
                'san juan':              'san juan de yapacaní',
                'pampa grande':          'pampagrande',
                'postrer valle':         'postrervalle',
                'pucará':                'pucara',
                'trigal':                'el trigal',
                'porongo (ayacucho)':    'porongo'
            };
            return dict[n] || n;
        };

        // Mantener compatibilidad con código antiguo que use normalizeGeoName
        window.normalizeGeoName = window.normalizeMuniName;
    </script>
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 antialiased flex">
    @if (session()->has('api_token'))
        <!-- SIDEBAR INTERACTIVO -->
        <aside class="fixed inset-y-0 left-0 bg-[#0f172a] text-gray-300 w-16 hover:w-64 transition-all duration-300 ease-in-out z-[999] overflow-x-hidden overflow-y-hidden flex flex-col group border-r border-gray-800 shadow-2xl no-scrollbar">
            <!-- Logo / Brand -->
            <div class="h-16 flex items-center px-4 border-b border-gray-800 shrink-0 bg-[#0b1120]">
                <div class="min-w-[32px] h-8 flex items-center justify-center bg-blue-600 text-white font-bold rounded-lg shadow-lg shadow-blue-500/30">
                    ISCZ
                </div>
                <span class="ml-4 font-bold text-lg text-white opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap tracking-tight">
                    Inundaciones SCZ
                </span>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 py-6 flex flex-col gap-1 overflow-y-auto no-scrollbar">
                <a href="{{ route('reports.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('reports.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Reportes">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Reportes</span>
                </a>
                
                <a href="{{ route('maps.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('maps.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Mapas">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Mapas</span>
                </a>
                
                <a href="{{ route('vehiculos.mapa', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('vehiculos.*') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Rastreo de Vehículos">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Vehículos</span>
                </a>
                
                <a href="{{ route('logistica.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('logistica.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Logística">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Logística</span>
                </a>
                
                <a href="{{ route('victimas.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('victimas.index') ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-gray-800 hover:text-white' }}" title="Víctimas">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Víctimas</span>
                </a>
                
                @if ($apiRole === 'authority')
                <a href="{{ route('command-center.index', [], false) }}" class="flex items-center px-4 py-3 mx-2 rounded-lg transition-all {{ request()->routeIs('command-center.*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'hover:bg-gray-800 hover:text-white text-indigo-400' }}" title="Análisis de impacto">
                    <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Análisis de impacto</span>
                </a>
                @endif
            </nav>
            
            <!-- Bottom Actions -->
            <div class="p-3 border-t border-gray-800">
                <form method="POST" action="{{ route('logout', [], false) }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-3 text-red-400 hover:text-red-300 hover:bg-gray-800 rounded-lg transition-colors" title="Salir">
                        <svg class="w-6 h-6 min-w-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="ml-4 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-medium">Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>
    @endif

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-h-screen {{ session()->has('api_token') ? 'ml-16' : '' }} transition-all duration-300 w-full relative">
        
        <!-- HEADER TOP BAR -->
        <header class="border-b border-gray-200 bg-white/80 backdrop-blur-md sticky top-0 z-[90] shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between gap-4">
                
                <div class="flex items-center gap-4">
                    @if (!session()->has('api_token'))
                    <a href="{{ route('reports.index', [], false) }}" class="font-bold text-xl tracking-tight text-blue-600 hover:text-blue-700 transition-colors">
                        {{ config('app.name', 'Flood Reports') }}
                    </a>
                    @else
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-widest hidden sm:block">Panel de Control</span>
                    @endif
                </div>

                <nav class="flex items-center gap-2 sm:gap-4 text-sm">
                    @if (session()->has('api_token'))
                        
                        <!-- User Info -->
                        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 border border-gray-200">
                            <div class="w-6 h-6 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold text-xs">
                                {{ strtoupper(substr($apiUser['name'] ?? 'U', 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-700 max-w-[10rem] truncate">{{ (string) ($apiUser['name'] ?? '') }}</span>
                            @if ($apiRole !== '')
                                <span class="bg-white text-gray-500 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide border border-gray-200">{{ $apiRole }}</span>
                            @endif
                        </div>

                        <!-- Botón Geolocalización -->
                        <button id="btn-geolocate" class="rounded-full w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors border border-transparent hover:border-blue-200" title="Guardar mi ubicación">
                            <span id="geo-btn-icon" class="text-lg">📍</span>
                        </button>

                        <!-- Notificaciones -->
                        <div class="relative">
                            <button id="notifications-toggle" class="rounded-full w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors border border-transparent hover:border-blue-200 relative" title="Notificaciones">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span id="notifications-badge" class="hidden absolute top-0 right-0 min-w-[18px] h-[18px] rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center shadow-sm border-2 border-white">0</span>
                            </button>
                            <div id="notifications-panel" class="hidden absolute right-0 z-50 mt-2 w-80 rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden">
                                <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Notificaciones
                                </div>
                                <div id="notifications-list" class="max-h-80 overflow-y-auto">
                                    <div class="px-4 py-6 text-sm text-center text-gray-500">Sin notificaciones por ahora.</div>
                                </div>
                            </div>
                        </div>

                    @else
                        <a href="{{ route('login', [], false) }}" class="rounded-full px-4 py-2 text-gray-600 hover:bg-gray-100 font-medium transition-colors">Iniciar Sesión</a>
                        <a href="{{ route('register', [], false) }}" class="rounded-full bg-gray-900 px-5 py-2 font-medium text-white shadow hover:bg-gray-800 transition-colors">Registrarse</a>
                    @endif
                </nav>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="mx-auto w-full max-w-7xl px-4 py-8 flex-1">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm">
                <div class="font-medium mb-1">Revisá los errores:</div>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
        </main>
    </div>{{-- /content wrapper --}}

    <!-- Global Image Modal -->
    <div id="global-image-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black bg-opacity-85 backdrop-blur-sm transition-all duration-300" style="display: none;">
        <button id="global-image-modal-close" class="absolute top-4 right-4 text-white text-5xl font-light hover:text-gray-300 focus:outline-none transition-colors select-none cursor-pointer" aria-label="Cerrar">&times;</button>
        <div class="relative max-w-4xl max-h-[90vh] p-4 flex items-center justify-center">
            <img id="global-image-modal-img" src="" alt="Vista ampliada" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl border border-gray-800 transform scale-95 transition-all duration-300 object-contain">
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('global-image-modal');
        const modalImg = document.getElementById('global-image-modal-img');
        const modalClose = document.getElementById('global-image-modal-close');

        if (modal && modalImg && modalClose) {
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.clickable-image');
                if (trigger) {
                    let src = '';
                    if (trigger.tagName === 'IMG') {
                        src = trigger.getAttribute('src');
                    } else if (trigger.tagName === 'A') {
                        e.preventDefault();
                        src = trigger.getAttribute('href');
                    }

                    if (src) {
                        modalImg.src = src;
                        modal.style.display = 'flex';
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modal.classList.remove('opacity-0');
                            modalImg.classList.remove('scale-95');
                            modalImg.classList.add('scale-100');
                        }, 10);
                    }
                }
            });

            const closeModal = function() {
                modalImg.classList.remove('scale-100');
                modalImg.classList.add('scale-95');
                modal.classList.add('opacity-0');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modalImg.src = '';
                }, 300);
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.closest('#global-image-modal-close')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.style.display === 'flex') {
                    closeModal();
                }
            });
        }
    });
    </script>

    @include('chat.widget')
</body>

<script>
// ─────────────────────────────────────────────────────────────
// Geolocalización global (layouts/app.blade.php)
// ─────────────────────────────────────────────────────────────
// La ubicación se pide UNA sola vez mediante el botón 📍 del nav.
// Se persiste en localStorage con las claves 'app_user_lat' y 'app_user_lng'
// para que cualquier módulo (Logística, Reportes, Mapas) la consuma
// sin volver a pedirla al usuario.
//
// API pública expuesta:
//   window.getUserLocation() → { lat, lng } | null
// ─────────────────────────────────────────────────────────────
(function () {
    const LAT_KEY = 'app_user_lat';
    const LNG_KEY = 'app_user_lng';

    // Lee la ubicación guardada; devuelve {lat, lng} o null si no existe
    window.getUserLocation = function () {
        const lat = parseFloat(localStorage.getItem(LAT_KEY));
        const lng = parseFloat(localStorage.getItem(LNG_KEY));
        if (isNaN(lat) || isNaN(lng)) return null;
        return { lat, lng };
    };

    const btn = document.getElementById('btn-geolocate');
    if (!btn) return; // no hay sesión activa

    function updateBtnState() {
        const loc = window.getUserLocation();
        if (loc) {
            btn.title    = 'Ubicación guardada ✓ — clic para actualizar';
            btn.querySelector('#geo-btn-icon').textContent = '✅';
        } else {
            btn.title    = 'Guardar mi ubicación para encontrar centros cercanos';
            btn.querySelector('#geo-btn-icon').textContent = '📍';
        }
    }

    updateBtnState(); // estado inicial al cargar la página

    btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Tu navegador no soporta geolocalización.');
            return;
        }
        btn.disabled = true;
        btn.querySelector('#geo-btn-icon').textContent = '⏳';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                localStorage.setItem(LAT_KEY, pos.coords.latitude);
                localStorage.setItem(LNG_KEY, pos.coords.longitude);
                btn.disabled = false;
                updateBtnState();
            },
            function (err) {
                alert('No se pudo obtener la ubicación: ' + err.message);
                btn.disabled = false;
                updateBtnState();
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
})();
</script>

</html>