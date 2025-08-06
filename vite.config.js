import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/main-styles.scss',
                'resources/js/main-scripts.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // outDir: 'public/build',
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name].js',
                chunkFileNames: 'assets/[name].[ext]',
                assetFileNames: 'assets/[name].[ext]'
            }
        }
    }
});
