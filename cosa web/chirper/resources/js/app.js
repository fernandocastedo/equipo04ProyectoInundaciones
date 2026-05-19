import './bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Inicializar Laravel Echo con Reverb (solo si el usuario tiene sesión activa)
(function () {
    function getMeta(name, fallback = '') {
        return document.querySelector(`meta[name="${name}"]`)?.content ?? fallback;
    }

    const reverbKey = getMeta('reverb-app-key');
    if (!reverbKey) return; // No hay sesión, no inicializar

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: getMeta('reverb-host', '127.0.0.1'),
        wsPort: parseInt(getMeta('reverb-port', '8080'), 10),
        wssPort: parseInt(getMeta('reverb-port', '8080'), 10),
        forceTLS: getMeta('reverb-scheme', 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/chat/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getMeta('csrf-token'),
            },
        },
    });
})();

const POLL_INTERVAL_MS = 15000;

function getMetaContent(name) {
	const element = document.querySelector(`meta[name="${name}"]`);

	return element ? element.getAttribute('content') ?? '' : '';
}

function getStorageKey(role, carnet) {
	return `notifications:last_seen:${role}:${carnet || 'anon'}`;
}

function getStoredCursor(role, carnet) {
	const key = getStorageKey(role, carnet);
	const value = window.localStorage.getItem(key);
	const parsed = Number.parseInt(String(value), 10);
	return Number.isNaN(parsed) ? 0 : parsed;
}

function setStoredCursor(role, carnet, cursor) {
	window.localStorage.setItem(getStorageKey(role, carnet), String(cursor));
}

async function fetchNotifications(endpoint) {
	const response = await window.fetch(endpoint, {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
		credentials: 'same-origin',
	});

	if (!response.ok) {
		return [];
	}

	const payload = await response.json();
	const items = Array.isArray(payload?.data) ? payload.data : [];
	return items.map((item) => ({
		id: String(item?.id ?? ''),
		cursor: Number.parseInt(String(item?.cursor ?? 0), 10) || 0,
		title: String(item?.title ?? 'Notificacion'),
		message: String(item?.message ?? ''),
		createdAt: String(item?.created_at ?? ''),
		link: String(item?.link ?? ''),
	}));
}

function showDesktopNotification(item) {
	if (!('Notification' in window) || Notification.permission !== 'granted') {
		return;
	}

	new Notification(item.title, { body: item.message });
}

async function startNotifications(endpoint, role, carnet) {
	console.debug('startNotifications()', { endpoint, role, carnet });
	const toggleBtn = document.getElementById('notifications-toggle');
	const panel = document.getElementById('notifications-panel');
	const list = document.getElementById('notifications-list');
	const badge = document.getElementById('notifications-badge');

	if (!toggleBtn || !panel || !list || !badge) {
		return;
	}

	let latestCursor = getStoredCursor(role, carnet);
	let initialized = false;
	let isPanelOpen = false;

	function render(items) {
		if (items.length === 0) {
			list.innerHTML = '<div class="px-3 py-4 text-xs text-gray-500">Sin notificaciones por ahora.</div>';
			badge.classList.add('hidden');
			return;
		}

		list.innerHTML = items
			.map((item) => {
				const link = item.link || '/reports';
				return `
					<a href="${link}" class="block border-b border-gray-100 px-3 py-2 last:border-b-0 hover:bg-gray-50">
						<div class="text-xs font-semibold text-gray-800">${item.title}</div>
						<div class="mt-0.5 text-xs text-gray-600">${item.message}</div>
					</a>
				`;
			})
			.join('');

		const unseen = items.filter((item) => item.cursor > latestCursor).length;
		if (unseen > 0 && !isPanelOpen) {
			badge.textContent = unseen > 99 ? '99+' : String(unseen);
			badge.classList.remove('hidden');
		} else {
			badge.classList.add('hidden');
		}
	}

	async function poll(notifyDesktop) {
		try {
			const items = await fetchNotifications(endpoint);
			render(items);

			if (items.length > 0) {
				const maxCursor = Math.max(...items.map((item) => item.cursor));
				if (notifyDesktop && initialized && maxCursor > latestCursor) {
					const newest = items.find((item) => item.cursor === maxCursor);
					if (newest) {
						showDesktopNotification(newest);
					}
				}

				if (!initialized) {
					latestCursor = maxCursor;
					setStoredCursor(role, carnet, latestCursor);
				}
			}
		} catch {
			// Ignore transient errors to keep polling active.
		}

		initialized = true;
	}

	 toggleBtn.addEventListener('click', () => {
		console.debug('notifications-toggle clicked', { hidden: panel.classList.contains('hidden') });
		isPanelOpen = !panel.classList.contains('hidden');
		if (isPanelOpen) {
			panel.classList.add('hidden');
			console.debug('notifications-panel hidden');
			return;
		}

		panel.classList.remove('hidden');
		isPanelOpen = true;
		console.debug('notifications-panel shown');

		fetchNotifications(endpoint).then((items) => {
			render(items);
			if (items.length > 0) {
				latestCursor = Math.max(latestCursor, ...items.map((item) => item.cursor));
				setStoredCursor(role, carnet, latestCursor);
				badge.classList.add('hidden');
			}
		});
	});

	document.addEventListener('click', (event) => {
		console.debug('document click', { target: event.target, panelHidden: panel.classList.contains('hidden') });
		if (!panel.contains(event.target) && !toggleBtn.contains(event.target)) {
			panel.classList.add('hidden');
			isPanelOpen = false;
			console.debug('notifications-panel hidden by outside click');
		}
	});

	if ('Notification' in window && window.isSecureContext && Notification.permission === 'default') {
		Notification.requestPermission().catch(() => {});
	}

	await poll(false);
	window.setInterval(() => {
		poll(true);
	}, POLL_INTERVAL_MS);
}

document.addEventListener('DOMContentLoaded', () => {
	const role = getMetaContent('api-user-role').trim().toLowerCase();
	const carnet = getMetaContent('api-user-carnet').trim();
	const endpoint = getMetaContent('reports-notifications-endpoint');

	if (endpoint === '') {
		return;
	}

	startNotifications(endpoint, role, carnet);
});
