/**
 * Estilos del widget SofIA (Clean UI - Light Mode).
 * Se inyectan como <style> para que el bundle sea un único archivo JS.
 */
export const WIDGET_CSS = `
.sofia-root, .sofia-root * { box-sizing: border-box; margin: 0; padding: 0; }

.sofia-root {
    --sf-blue: #3b82f6;
    --sf-blue-dark: #2563eb;
    --sf-dark: #0f1629;
    --sf-bg: #f7f9fc;
    --sf-border: #e5eaf2;
    --sf-text: #1e293b;
    --sf-muted: #64748b;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    position: fixed;
    bottom: 22px;
    right: 22px;
    z-index: 2147483000;
    font-size: 14px;
    color: var(--sf-text);
}

/* ---------- Botón flotante ---------- */
.sofia-launcher {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    background: linear-gradient(135deg, var(--sf-blue), #22d3ee);
    color: #fff;
    font-size: 26px;
    display: grid;
    place-items: center;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.45);
    transition: transform 0.2s ease;
    position: relative;
}
.sofia-launcher:hover { transform: scale(1.07); }
.sofia-launcher .sofia-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid rgba(59, 130, 246, 0.6);
    animation: sofia-ping 2.2s ease-out infinite;
}
@keyframes sofia-ping {
    0%   { transform: scale(1);   opacity: 0.8; }
    100% { transform: scale(1.6); opacity: 0; }
}

/* ---------- Ventana ---------- */
.sofia-window {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 32px);
    height: 600px;
    max-height: calc(100vh - 110px);
    background: #fff;
    border-radius: 20px;
    border: 1px solid var(--sf-border);
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: sofia-rise 0.25s ease;
}
@keyframes sofia-rise {
    from { opacity: 0; transform: translateY(16px) scale(0.97); }
    to   { opacity: 1; transform: none; }
}

/* ---------- Header ---------- */
.sofia-header {
    background: var(--sf-dark);
    color: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sofia-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--sf-blue), #22d3ee);
    display: grid;
    place-items: center;
    font-weight: 800;
    font-size: 15px;
    flex-shrink: 0;
}
.sofia-header-info { flex: 1; min-width: 0; }
.sofia-header-info h4 { font-size: 15px; font-weight: 700; }
.sofia-status { font-size: 11.5px; color: #86efac; display: flex; align-items: center; gap: 5px; }
.sofia-status::before {
    content: '';
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #4ade80;
    display: inline-block;
}
.sofia-header-actions { display: flex; gap: 6px; }
.sofia-icon-btn {
    background: rgba(255,255,255,0.08);
    border: none;
    color: #e2e8f0;
    width: 32px; height: 32px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 15px;
    display: grid;
    place-items: center;
    transition: background 0.15s ease;
}
.sofia-icon-btn:hover { background: rgba(255,255,255,0.18); }

/* ---------- Mensajes ---------- */
.sofia-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    background: var(--sf-bg);
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
}
.sofia-msg { display: flex; max-width: 88%; }
.sofia-msg-user { align-self: flex-end; }
.sofia-msg-assistant { align-self: flex-start; }
.sofia-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    line-height: 1.5;
    word-wrap: break-word;
    white-space: pre-wrap;
}
.sofia-msg-user .sofia-bubble {
    background: var(--sf-blue);
    color: #fff;
    border-bottom-right-radius: 5px;
}
.sofia-msg-assistant .sofia-bubble {
    background: #fff;
    border: 1px solid var(--sf-border);
    border-bottom-left-radius: 5px;
}
.sofia-bubble b, .sofia-bubble strong { font-weight: 700; }

.sofia-typing {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #fff;
    border: 1px solid var(--sf-border);
    border-radius: 18px;
    border-bottom-left-radius: 5px;
    align-self: flex-start;
    box-shadow: 0 2px 10px rgba(15,23,42,0.06);
    max-width: 85%;
}
.sofia-typing-dots {
    display: flex;
    gap: 4px;
    align-items: center;
    flex-shrink: 0;
}
.sofia-typing span {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0B6F60, #16A08A);
    animation: sofia-blink 1.3s infinite ease-in-out;
}
.sofia-typing span:nth-child(2) { animation-delay: 0.18s; }
.sofia-typing span:nth-child(3) { animation-delay: 0.36s; }
@keyframes sofia-blink {
    0%, 80%, 100% { opacity: 0.25; transform: translateY(0) scale(0.85); }
    40%           { opacity: 1;    transform: translateY(-3px) scale(1.1); }
}
.sofia-typing-label {
    font-size: 11.5px;
    color: var(--sf-muted);
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sofia-typing-label-default {
    font-size: 11.5px;
    color: var(--sf-muted);
}

/* ---------- Tarjetas de producto ---------- */
.sofia-cards {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
}
.sofia-card {
    background: #fff;
    border: 1px solid var(--sf-border);
    border-radius: 14px;
    padding: 12px;
}
.sofia-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 4px;
}
.sofia-card-name { font-weight: 700; font-size: 13.5px; }
.sofia-card-price { font-weight: 800; color: var(--sf-blue-dark); white-space: nowrap; }
.sofia-card-quote { font-weight: 700; color: #b45309; white-space: nowrap; font-size: 12.5px; }
.sofia-card-meta { font-size: 11.5px; color: var(--sf-muted); margin-bottom: 6px; }
.sofia-card-desc {
    font-size: 12px;
    color: var(--sf-muted);
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sofia-btn {
    border: none;
    border-radius: 10px;
    padding: 9px 14px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: filter 0.15s ease;
    width: 100%;
}
.sofia-btn:hover { filter: brightness(1.08); }
.sofia-btn:disabled { opacity: 0.55; cursor: default; }
.sofia-btn-primary { background: var(--sf-blue); color: #fff; }
.sofia-btn-dark { background: var(--sf-dark); color: #fff; }
.sofia-btn-success { background: #16a34a; color: #fff; }
.sofia-btn-outline {
    background: #fff;
    color: var(--sf-blue-dark);
    border: 1px solid #bfdbfe;
}

/* ---------- Carrito ---------- */
.sofia-cart { background: #fff; border: 1px solid var(--sf-border); border-radius: 14px; padding: 12px; width: 100%; }
.sofia-cart-title { font-weight: 700; font-size: 13px; margin-bottom: 8px; }
.sofia-cart-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 12.5px;
    border-bottom: 1px dashed var(--sf-border);
}
.sofia-cart-row:last-of-type { border-bottom: none; }
.sofia-cart-remove {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 14px;
    padding: 2px 4px;
}
.sofia-cart-total {
    display: flex;
    justify-content: space-between;
    font-weight: 800;
    padding-top: 8px;
    margin-top: 4px;
    border-top: 1px solid var(--sf-border);
}

/* ---------- Quick replies ---------- */
.sofia-quick {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 8px 14px 0;
    background: var(--sf-bg);
}
.sofia-chip {
    background: #fff;
    border: 1px solid #bfdbfe;
    color: var(--sf-blue-dark);
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    cursor: pointer;
    transition: background 0.15s ease;
}
.sofia-chip:hover { background: #eff6ff; }

/* ---------- Input ---------- */
.sofia-footer {
    border-top: 1px solid var(--sf-border);
    background: #fff;
    padding: 10px 12px;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.sofia-input {
    flex: 1;
    border: 1px solid var(--sf-border);
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 13.5px;
    font-family: inherit;
    resize: none;
    max-height: 92px;
    outline: none;
    background: var(--sf-bg);
}
.sofia-input:focus { border-color: var(--sf-blue); background: #fff; }
.sofia-send {
    background: var(--sf-blue);
    color: #fff;
    border: none;
    width: 40px; height: 40px;
    border-radius: 12px;
    cursor: pointer;
    font-size: 16px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}
.sofia-send:disabled { opacity: 0.5; cursor: default; }
.sofia-powered {
    text-align: center;
    font-size: 10.5px;
    color: #94a3b8;
    padding: 0 0 8px;
    background: #fff;
}

/* ---------- Estados especiales ---------- */
.sofia-error-bubble {
    background: #fef2f2 !important;
    border-color: #fecaca !important;
    color: #b91c1c;
}
.sofia-payment-ok {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 12px;
    width: 100%;
}
.sofia-payment-ok h5 { color: #15803d; font-size: 13.5px; margin-bottom: 6px; }
.sofia-payment-ok p { font-size: 12.5px; color: #166534; }

@media (max-width: 480px) {
    .sofia-root { bottom: 12px; right: 12px; }
    .sofia-window {
        width: calc(100vw - 24px);
        height: calc(100vh - 90px);
        border-radius: 16px;
    }
}
`;

export function injectStyles() {
    if (document.getElementById('sofia-widget-styles')) return;
    const style = document.createElement('style');
    style.id = 'sofia-widget-styles';
    style.textContent = WIDGET_CSS;
    document.head.appendChild(style);
}
