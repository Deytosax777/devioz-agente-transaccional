import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import { injectStyles } from './styles.js';

/**
 * Punto de entrada del widget SofIA.
 * Se monta solo: crea su contenedor, inyecta estilos y renderiza la app.
 */
function mount() {
    if (document.getElementById('sofia-widget-root')) return;

    injectStyles();

    const container = document.createElement('div');
    container.id = 'sofia-widget-root';
    document.body.appendChild(container);

    createRoot(container).render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
