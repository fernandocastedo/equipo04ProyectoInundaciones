@php
    $apiUser = (array) session('api_user', []);
    $apiRole = (string) ($apiUser['role'] ?? '');
    $myCarnet = (string) ($apiUser['carnet'] ?? '');
    $myName = (string) ($apiUser['name'] ?? '');
@endphp

@if ($apiRole === 'authority' && $myCarnet !== '')
<!-- ══════════════════════════════════════════════════════════════════
     CHAT WIDGET FLOTANTE — solo autoridades
     ══════════════════════════════════════════════════════════════════ -->
<div id="chat-widget" style="
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9000;
    font-family: 'Instrument Sans', system-ui, sans-serif;
">
    <!-- Botón flotante -->
    <button id="chat-fab" title="Chat entre autoridades" style="
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(59,130,246,0.5);
        display: flex; align-items: center; justify-content: center;
        transition: transform 0.2s, box-shadow 0.2s; position: relative;
    ">
        <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span id="chat-unread-badge" style="
            display: none; position: absolute; top: -4px; right: -4px;
            background: #ef4444; color: white; border-radius: 50%;
            width: 20px; height: 20px; font-size: 11px; font-weight: 700;
            align-items: center; justify-content: center;
            border: 2px solid white;
        ">0</span>
    </button>

    <!-- Panel principal -->
    <div id="chat-panel" style="
        display: none;
        width: 360px; height: 520px;
        background: rgba(15, 23, 42, 0.97);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 16px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(99,102,241,0.1);
        overflow: hidden;
        flex-direction: column;
        position: absolute; bottom: 68px; right: 0;
        animation: chatSlideUp 0.25s ease;
    ">
        <!-- Header -->
        <div style="
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            padding: 14px 16px; display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(99,102,241,0.3); flex-shrink: 0;
        ">
            <div style="
                width: 36px; height: 36px; border-radius: 50%;
                background: rgba(255,255,255,0.15);
                display: flex; align-items: center; justify-content: center;
            ">
                <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div style="flex:1">
                <div style="color:white; font-weight:700; font-size:14px;">Chat Autoridades</div>
                <div id="chat-header-subtitle" style="color:rgba(255,255,255,0.6); font-size:11px;">Seleccioná una autoridad</div>
            </div>
            <button id="chat-close" style="
                background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.6);
                padding:4px; border-radius:6px; line-height:1;
                transition: color 0.15s;
            " title="Cerrar">&times;</button>
        </div>

        <!-- Layout: lista + conversación -->
        <div style="display:flex; flex:1; overflow:hidden;">

            <!-- Lista de autoridades (columna izquierda) -->
            <div id="chat-contacts" style="
                width: 120px; flex-shrink:0;
                border-right: 1px solid rgba(255,255,255,0.08);
                overflow-y: auto; padding: 8px 4px;
                background: rgba(0,0,0,0.2);
            ">
                <div style="color:rgba(255,255,255,0.4); font-size:10px; text-transform:uppercase; letter-spacing:0.08em; padding:4px 8px 8px;">Contactos</div>
                <div id="chat-contacts-list" style="display:flex; flex-direction:column; gap:4px;">
                    <div style="color:rgba(255,255,255,0.3); font-size:11px; text-align:center; padding:16px 4px;">Cargando...</div>
                </div>
            </div>

            <!-- Panel de mensajes (columna derecha) -->
            <div id="chat-conversation" style="flex:1; display:flex; flex-direction:column; overflow:hidden;">

                <!-- Estado vacío -->
                <div id="chat-empty-state" style="
                    flex:1; display:flex; flex-direction:column;
                    align-items:center; justify-content:center; gap:12px;
                    color:rgba(255,255,255,0.3); text-align:center; padding:20px;
                ">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:0.4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span style="font-size:12px;">Seleccioná una autoridad para chatear</span>
                </div>

                <!-- Mensajes -->
                <div id="chat-messages" style="
                    flex:1; overflow-y:auto; padding:12px; display:none;
                    flex-direction:column; gap:8px; scroll-behavior:smooth;
                "></div>

                <!-- Input -->
                <div id="chat-input-area" style="
                    display:none; padding:10px; border-top:1px solid rgba(255,255,255,0.08);
                    background:rgba(0,0,0,0.2); flex-shrink:0;
                ">
                    <div style="display:flex; gap:8px; align-items:flex-end;">
                        <textarea id="chat-input" rows="1" placeholder="Escribí un mensaje..." style="
                            flex:1; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15);
                            border-radius:10px; color:white; font-size:13px; padding:8px 12px;
                            resize:none; outline:none; font-family:inherit; line-height:1.4;
                            transition: border-color 0.15s;
                        "></textarea>
                        <button id="chat-send" style="
                            width:36px; height:36px; border-radius:10px; flex-shrink:0;
                            background:linear-gradient(135deg,#1e40af,#3b82f6); border:none;
                            cursor:pointer; display:flex; align-items:center; justify-content:center;
                            transition: opacity 0.15s, transform 0.15s;
                        ">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes chatSlideUp {
    from { opacity:0; transform: translateY(12px) scale(0.97); }
    to   { opacity:1; transform: translateY(0)   scale(1);    }
}
#chat-fab:hover { transform: scale(1.08); box-shadow: 0 6px 28px rgba(59,130,246,0.7); }
#chat-contacts-list .contact-btn {
    width:100%; background:none; border:none; cursor:pointer;
    border-radius:8px; padding:6px; display:flex; flex-direction:column;
    align-items:center; gap:4px; transition:background 0.15s; color:white;
}
#chat-contacts-list .contact-btn:hover { background:rgba(255,255,255,0.08); }
#chat-contacts-list .contact-btn.active { background:rgba(59,130,246,0.25); }
#chat-messages::-webkit-scrollbar { width:4px; }
#chat-messages::-webkit-scrollbar-track { background:transparent; }
#chat-messages::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:4px; }
#chat-contacts::-webkit-scrollbar { width:3px; }
#chat-contacts::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:3px; }
#chat-input:focus { border-color:rgba(59,130,246,0.6); background:rgba(255,255,255,0.12); }
</style>

<script>
(function () {
    'use strict';

    const MY_CARNET  = @json($myCarnet);
    const MY_NAME    = @json($myName);
    const AUTH_URL   = @json(route('chat.broadcast.auth', [], false));
    const HIST_BASE  = @json(url('/chat/history'));
    const STORE_URL  = @json(route('chat.store', [], false));
    const LIST_URL   = @json(route('chat.authorities', [], false));

    function getMeta(name, fallback = '') {
        return document.querySelector(`meta[name="${name}"]`)?.content ?? fallback;
    }
    const REVERB_KEY    = getMeta('reverb-app-key', 'chirper-reverb-key');
    const REVERB_HOST   = getMeta('reverb-host', '127.0.0.1');
    const REVERB_PORT   = parseInt(getMeta('reverb-port', '8080'), 10);
    const REVERB_SCHEME = getMeta('reverb-scheme', 'http');
    const CSRF_TOKEN    = getMeta('csrf-token');


    let echoInstance   = null;
    let activeCarnet   = null;
    let activeChannel  = null;
    let unreadCount    = 0;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fab          = document.getElementById('chat-fab');
    const panel        = document.getElementById('chat-panel');
    const closeBtn     = document.getElementById('chat-close');
    const contactsList = document.getElementById('chat-contacts-list');
    const messagesEl   = document.getElementById('chat-messages');
    const emptyState   = document.getElementById('chat-empty-state');
    const inputArea    = document.getElementById('chat-input-area');
    const inputEl      = document.getElementById('chat-input');
    const sendBtn      = document.getElementById('chat-send');
    const subtitle     = document.getElementById('chat-header-subtitle');
    const badge        = document.getElementById('chat-unread-badge');

    // ── Utilidades ────────────────────────────────────────────────────────────
    function channelName(a, b) {
        const sorted = [a, b].sort();
        return 'chat.' + sorted[0] + '.' + sorted[1];
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function fmtTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
    }

    function csrfHeaders() {
        return { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' };
    }

    // ── Badge de no leídos ────────────────────────────────────────────────────
    function setUnread(n) {
        unreadCount = n;
        if (n > 0 && panel.style.display === 'none') {
            badge.textContent = n > 99 ? '99+' : n;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // ── Renderizar un mensaje ─────────────────────────────────────────────────
    function renderMessage(msg) {
        const isMine = msg.sender_carnet === MY_CARNET;
        const div = document.createElement('div');
        div.style.cssText = `
            display:flex; flex-direction:column;
            align-items:${isMine ? 'flex-end' : 'flex-start'};
            gap:2px; animation: chatMsgIn 0.18s ease;
        `;
        div.innerHTML = `
            ${!isMine ? `<span style="color:rgba(255,255,255,0.45);font-size:10px;margin-left:4px;">${escHtml(msg.sender_name)}</span>` : ''}
            <div style="
                max-width:85%; padding:8px 12px; border-radius:${isMine ? '14px 14px 4px 14px' : '14px 14px 14px 4px'};
                background:${isMine ? 'linear-gradient(135deg,#1e40af,#3b82f6)' : 'rgba(255,255,255,0.1)'};
                color:white; font-size:13px; line-height:1.45; word-break:break-word;
                border:1px solid ${isMine ? 'transparent' : 'rgba(255,255,255,0.08)'};
            ">${escHtml(msg.message)}</div>
            <span style="color:rgba(255,255,255,0.3);font-size:10px;margin:0 4px;">${fmtTime(msg.created_at)}</span>
        `;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ── Cargar historial ──────────────────────────────────────────────────────
    async function loadHistory(carnet) {
        messagesEl.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);font-size:12px;padding:20px;">Cargando...</div>';
        messagesEl.style.display = 'flex';
        emptyState.style.display = 'none';
        inputArea.style.display  = 'block';

        try {
            const res = await fetch(`${HIST_BASE}/${encodeURIComponent(carnet)}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const msgs = await res.json();
            messagesEl.innerHTML = '';
            if (msgs.length === 0) {
                messagesEl.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,0.3);font-size:12px;padding:20px;">Sin mensajes aún. ¡Empezá la conversación!</div>';
            } else {
                msgs.forEach(renderMessage);
            }
        } catch {
            messagesEl.innerHTML = '<div style="text-align:center;color:#f87171;font-size:12px;padding:20px;">Error al cargar mensajes.</div>';
        }
    }

    // ── Suscribir al canal Reverb ─────────────────────────────────────────────
    function subscribeToChannel(targetCarnet) {
        if (!echoInstance) return;
        if (activeChannel) {
            echoInstance.leave('private-' + channelName(MY_CARNET, activeCarnet));
        }
        activeCarnet  = targetCarnet;
        activeChannel = echoInstance
            .private(channelName(MY_CARNET, targetCarnet))
            .listen('.message.sent', (data) => {
                // Si el panel está visible y es de este chat, renderizar directo
                if (panel.style.display !== 'none' && data.sender_carnet === targetCarnet) {
                    renderMessage(data);
                } else if (data.sender_carnet === targetCarnet) {
                    setUnread(unreadCount + 1);
                }
            });
    }

    // ── Inicializar Echo con Reverb ───────────────────────────────────────────
    function initEcho() {
        // window.Echo es inicializado por app.js; esperamos brevemente por si aún no cargó
        if (typeof window.Echo !== 'undefined') {
            echoInstance = window.Echo;
            return;
        }
        // Retry progresivo para evitar carrera de carga entre scripts
        let tries = 0;
        const maxTries = 8;
        const timer = setInterval(() => {
            if (typeof window.Echo !== 'undefined') {
                echoInstance = window.Echo;
                clearInterval(timer);
                return;
            }

            tries += 1;
            if (tries >= maxTries) {
                clearInterval(timer);
                console.warn('Chat: Laravel Echo no disponible. El panel seguira funcionando sin tiempo real.');
            }
        }, 350);
    }

    // ── Cargar lista de contactos ─────────────────────────────────────────────
    async function loadContacts() {
        try {
            const res = await fetch(LIST_URL, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) throw new Error('contacts_failed');
            const authorities = await res.json();
            contactsList.innerHTML = '';

            if (authorities.length === 0) {
                contactsList.innerHTML = '<div style="color:rgba(255,255,255,0.3);font-size:11px;text-align:center;padding:12px 4px;">Sin otras autoridades</div>';
                return;
            }

            authorities.forEach(auth => {
                const btn = document.createElement('button');
                btn.className = 'contact-btn';
                btn.dataset.carnet = auth.carnet;
                btn.title = auth.name;
                btn.innerHTML = `
                    <div style="
                        width:36px;height:36px;border-radius:50%;
                        background:linear-gradient(135deg,#1e40af,#6366f1);
                        display:flex;align-items:center;justify-content:center;
                        font-weight:700;font-size:14px;color:white;flex-shrink:0;
                    ">${escHtml(auth.initials)}</div>
                    <span style="font-size:9px;color:rgba(255,255,255,0.7);text-align:center;word-break:break-word;line-height:1.2;">${escHtml(auth.name)}</span>
                `;
                btn.addEventListener('click', () => selectContact(auth, btn));
                contactsList.appendChild(btn);
            });
        } catch (e) {
            contactsList.innerHTML = '<div style="color:#f87171;font-size:11px;text-align:center;padding:8px;">Error</div>';
        }
    }

    // ── Seleccionar contacto ──────────────────────────────────────────────────
    function selectContact(auth, btn) {
        document.querySelectorAll('.contact-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        subtitle.textContent = auth.name;
        setUnread(0);
        loadHistory(auth.carnet);
        subscribeToChannel(auth.carnet);
        inputEl.focus();
    }

    // ── Enviar mensaje ────────────────────────────────────────────────────────
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text || !activeCarnet) return;

        inputEl.value = '';
        inputEl.style.height = 'auto';
        sendBtn.style.opacity = '0.5';

        // Renderizar optimista
        renderMessage({
            sender_carnet: MY_CARNET,
            sender_name: MY_NAME,
            receiver_carnet: activeCarnet,
            message: text,
            created_at: new Date().toISOString(),
        });

        try {
            const res = await fetch(STORE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: csrfHeaders(),
                body: JSON.stringify({ receiver_carnet: activeCarnet, message: text }),
            });
            if (!res.ok) {
                throw new Error('store_failed');
            }
        } catch (e) {
            console.error('Error enviando mensaje:', e);
        } finally {
            sendBtn.style.opacity = '1';
        }
    }

    // ── Eventos UI ────────────────────────────────────────────────────────────
    fab.addEventListener('click', () => {
        const isOpen = panel.style.display === 'flex';
        panel.style.display = isOpen ? 'none' : 'flex';
        if (!isOpen) {
            setUnread(0);
            if (contactsList.children.length <= 1) loadContacts();
        }
    });

    closeBtn.addEventListener('click', () => { panel.style.display = 'none'; });

    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    inputEl.addEventListener('input', () => {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 96) + 'px';
    });

    // ── Arrancar ──────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initEcho();
        loadContacts();
    });

    // Inyectar keyframe de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes chatMsgIn {
            from { opacity:0; transform:translateY(6px); }
            to   { opacity:1; transform:translateY(0); }
        }
    `;
    document.head.appendChild(style);

    // Exponer globalmente para que app.js pueda pasar Echo
    window._chatInit = initEcho;
})();
</script>
@endif
