/**
 * Cliente API del widget: REST + Server-Sent Events sobre fetch streaming.
 */

function apiBase() {
    return (window.SOFIA_CONFIG && window.SOFIA_CONFIG.apiBase) || '';
}

export async function fetchConfig() {
    const res = await fetch(`${apiBase()}/api/config`);
    if (!res.ok) throw new Error('No se pudo cargar la configuración');
    return res.json();
}

export async function fetchCart(sessionId) {
    const res = await fetch(`${apiBase()}/api/cart/${encodeURIComponent(sessionId)}`);
    if (!res.ok) throw new Error('No se pudo cargar el carrito');
    return res.json();
}

export async function addCartItem(sessionId, productId, quantity = 1) {
    const res = await fetch(`${apiBase()}/api/cart/${encodeURIComponent(sessionId)}/items`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId, quantity }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'No se pudo agregar el producto');
    return data;
}

export async function removeCartItem(sessionId, productId) {
    const res = await fetch(
        `${apiBase()}/api/cart/${encodeURIComponent(sessionId)}/items/${productId}`,
        { method: 'DELETE' }
    );
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'No se pudo quitar el producto');
    return data;
}

export async function submitPayment({ orderCode, tokenId, email, customerName }) {
    const res = await fetch(`${apiBase()}/api/checkout`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_code: orderCode,
            token_id: tokenId,
            email,
            customer_name: customerName || '',
        }),
    });
    return res.json();
}

/**
 * Envía un mensaje al agente y consume la respuesta SSE en streaming.
 *
 * @param {object}   params
 * @param {string}   params.sessionId
 * @param {string}   params.message
 * @param {function} params.onEvent  fn(eventName, dataObject)
 */
export async function streamChat({ sessionId, message, onEvent }) {
    const res = await fetch(`${apiBase()}/api/chat/message`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'text/event-stream',
        },
        body: JSON.stringify({ session_id: sessionId, message }),
    });

    if (!res.ok || !res.body) {
        let detail = 'El servidor no está disponible en este momento.';
        try {
            const err = await res.json();
            if (err && err.message) detail = err.message;
        } catch {
            /* cuerpo no JSON */
        }
        throw new Error(detail);
    }

    const reader = res.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    const dispatchFrame = (frame) => {
        let eventName = 'message';
        const dataLines = [];

        frame.split('\n').forEach((line) => {
            if (line.startsWith('event:')) eventName = line.slice(6).trim();
            else if (line.startsWith('data:')) dataLines.push(line.slice(5).trim());
        });

        if (dataLines.length === 0) return;

        try {
            onEvent(eventName, JSON.parse(dataLines.join('\n')));
        } catch {
            /* frame malformado: ignorar */
        }
    };

    // eslint-disable-next-line no-constant-condition
    while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });

        let sep;
        while ((sep = buffer.indexOf('\n\n')) !== -1) {
            const frame = buffer.slice(0, sep);
            buffer = buffer.slice(sep + 2);
            if (frame.trim() !== '' && !frame.startsWith(':')) {
                dispatchFrame(frame);
            }
        }
    }
}
