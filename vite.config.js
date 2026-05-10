import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: 'localhost',
        port: 5173,
        origin: 'http://localhost:5173'
    },
    build: {
        assetsUrl: 'http://localhost:2244',
        rollupOptions: {
            output: {
                assetFileNames: 'assets/[name]-[hash].[ext]'
            }
        }
    }
});
