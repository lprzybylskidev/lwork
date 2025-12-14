import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    root: path.resolve(__dirname, 'resources'),
    publicDir: false,
    build: {
        outDir: path.resolve(__dirname, 'public/assets'),
        emptyOutDir: true,
        assetsDir: '',
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'resources/js/app.js'),
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
    },
});
