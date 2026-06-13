import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Build del panel de administración (React SPA).
 * Salida: ../public/admin (servido en /admin/ por el backend).
 */
export default defineConfig({
    root: 'admin',
    base: '/admin/',
    plugins: [react()],
    build: {
        outDir: '../../public/admin',
        emptyOutDir: true,
    },
    server: {
        port: 5174,
        proxy: {
            '/api': 'http://localhost:8080',
        },
    },
});
