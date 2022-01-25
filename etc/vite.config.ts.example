import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import legacy from '@vitejs/plugin-legacy';

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        vue(),
        legacy({
            targets: ['defaults', 'not IE 11'],
        }),
    ],
    build: {
        emptyOutDir: false,
        outDir: './webroot/',
        assetsDir: 'build',
        manifest: true,
        rollupOptions: {
            input: './webroot_src/main.ts',
        },
    },
    server: {
        hmr: {
            protocol: 'ws',
            host: 'localhost',
            port: 3000,
        },
    },
});
