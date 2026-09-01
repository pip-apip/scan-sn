import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // host: '0.0.0.0',
        // port: 4000,
        // hmr: {
        //     host: '192.168.5.79', // ganti dengan IP komputer Anda
        //     port: 4000,
        // },
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
