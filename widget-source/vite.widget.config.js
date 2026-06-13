import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Build del widget SofIA como bundle IIFE autocontenido (React incluido).
 * Salida: ../public/widget/sofia-widget.js
 */
export default defineConfig({
    plugins: [react()],
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    build: {
        outDir: '../public/widget',
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: 'src/widget/main.jsx',
            name: 'SofiaWidgetBundle',
            formats: ['iife'],
            fileName: () => 'sofia-widget.js',
        },
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
    },
});
