import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'packages/Webkul/Sales/src/Resources/assets/css/app.css',
                'packages/Webkul/Sales/src/Resources/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
