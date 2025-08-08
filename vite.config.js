import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0', // 👈 Accept connections from any IP
        port: 5173,      // 👈 Default Vite dev port
        hmr: {
            host: '192.168.20.69', // 👈 Your LAN IP here
            protocol: 'ws',        // 👈 WebSocket for HMR
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
