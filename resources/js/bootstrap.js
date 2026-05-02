import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Minimal Echo + Pusher bootstrap for realtime workflow events.
if (import.meta.env.VITE_PUSHER_APP_KEY) {
	window.Pusher = Pusher;

	window.Echo = new Echo({
		broadcaster: 'pusher',
		key: import.meta.env.VITE_PUSHER_APP_KEY,
		cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
		wsHost: import.meta.env.VITE_PUSHER_HOST ?? undefined,
		wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
		wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
		forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
		enabledTransports: ['ws', 'wss'],
	});
}
