import { useCallback, useEffect, useRef, useState } from 'react';
import { addCartItem, fetchConfig, removeCartItem, streamChat, submitPayment } from './api.js';
import { openCulqiCheckout } from './culqi.js';
import {
    clearConversation,
    getSessionId,
    loadMessages,
    loadOpenState,
    saveMessages,
    saveOpenState,
} from './storage.js';
import {
    CartCard,
    CheckoutCard,
    HandoffCard,
    PaymentResult,
    ProductCards,
    RichText,
    TypingIndicator,
} from './components.jsx';

const WELCOME = {
    id: 'welcome',
    role: 'assistant',
    type: 'text',
    content:
        '¡Hola! 👋 Soy **SofIA**, tu asesora virtual de Devioz.\n\nPuedo mostrarte nuestro catálogo de plantillas web, agentes de IA, dashboards y más — y procesar tu compra aquí mismo con tarjeta o Yape. ¿En qué te ayudo hoy?',
};

const QUICK_REPLIES = [
    '🛍️ Ver catálogo completo',
    '🌐 Plantillas web',
    '🤖 Agentes de IA',
    '🧑‍💼 Hablar con un asesor',
];

const TOOL_LABELS = {
    get_catalog: 'Consultando el catálogo…',
    add_to_cart: 'Agregando al carrito…',
    remove_from_cart: 'Actualizando el carrito…',
    get_cart: 'Revisando tu carrito…',
    generate_checkout: 'Generando tu orden de pago…',
    human_handoff: 'Contactando a un asesor…',
};

let idCounter = 0;
const nextId = () => `m-${Date.now()}-${idCounter++}`;

export default function App() {
    const [open, setOpen] = useState(loadOpenState);
    const [messages, setMessages] = useState(() => {
        const stored = loadMessages();
        return stored.length > 0 ? stored : [WELCOME];
    });
    const [input, setInput] = useState('');
    const [busy, setBusy] = useState(false);
    const [typing, setTyping] = useState(false);
    const [toolNote, setToolNote] = useState(null);
    const [config, setConfig] = useState(null);

    const sessionId = useRef(getSessionId()).current;
    const bodyRef = useRef(null);
    const streamingIdRef = useRef(null);

    // ---------- Persistencia ----------
    useEffect(() => {
        saveMessages(messages);
    }, [messages]);

    useEffect(() => {
        saveOpenState(open);
    }, [open]);

    // ---------- Configuración pública (llave Culqi, WhatsApp) ----------
    useEffect(() => {
        fetchConfig()
            .then(setConfig)
            .catch(() => setConfig(null));
    }, []);

    // ---------- Autoscroll ----------
    useEffect(() => {
        const el = bodyRef.current;
        if (el) el.scrollTop = el.scrollHeight;
    }, [messages, typing, toolNote, open]);

    const pushMessage = useCallback((msg) => {
        setMessages((prev) => [...prev, { id: nextId(), ...msg }]);
    }, []);

    /** Cierra la burbuja en streaming actual (los próximos tokens abren otra). */
    const finalizeStream = useCallback(() => {
        streamingIdRef.current = null;
    }, []);

    const appendToken = useCallback((token) => {
        setTyping(false);
        setMessages((prev) => {
            const id = streamingIdRef.current;
            if (id) {
                return prev.map((m) =>
                    m.id === id ? { ...m, content: m.content + token } : m
                );
            }
            const newId = nextId();
            streamingIdRef.current = newId;
            return [
                ...prev,
                { id: newId, role: 'assistant', type: 'text', content: token, streaming: true },
            ];
        });
    }, []);

    // ---------- Envío de mensajes (SSE) ----------
    const sendMessage = useCallback(
        async (text) => {
            const clean = String(text || '').trim();
            if (clean === '' || busy) return;

            pushMessage({ role: 'user', type: 'text', content: clean });
            setInput('');
            setBusy(true);
            setTyping(true);
            setToolNote(null);
            streamingIdRef.current = null;

            try {
                await streamChat({
                    sessionId,
                    message: clean,
                    onEvent: (event, data) => {
                        switch (event) {
                            case 'token':
                                setToolNote(null);
                                appendToken(data.t || '');
                                break;
                            case 'tool':
                                finalizeStream();
                                setTyping(true);
                                setToolNote(TOOL_LABELS[data.name] || 'Procesando…');
                                break;
                            case 'catalog':
                                finalizeStream();
                                setTyping(true);
                                pushMessage({ role: 'assistant', type: 'catalog', data: data.products });
                                break;
                            case 'cart':
                                finalizeStream();
                                setTyping(true);
                                pushMessage({ role: 'assistant', type: 'cart', data: data.cart });
                                break;
                            case 'checkout':
                                finalizeStream();
                                setTyping(true);
                                pushMessage({ role: 'assistant', type: 'checkout', data });
                                break;
                            case 'handoff':
                                finalizeStream();
                                setTyping(true);
                                pushMessage({ role: 'assistant', type: 'handoff', data });
                                break;
                            case 'error':
                                finalizeStream();
                                pushMessage({ role: 'assistant', type: 'error', content: data.message });
                                break;
                            case 'done':
                            default:
                                break;
                        }
                    },
                });
            } catch (e) {
                pushMessage({
                    role: 'assistant',
                    type: 'error',
                    content: e.message || 'No pude conectarme con el servidor. Intenta de nuevo.',
                });
            } finally {
                // Marcar la burbuja final como completa
                setMessages((prev) => prev.map((m) => (m.streaming ? { ...m, streaming: false } : m)));
                streamingIdRef.current = null;
                setBusy(false);
                setTyping(false);
                setToolNote(null);
            }
        },
        [busy, sessionId, pushMessage, appendToken, finalizeStream]
    );

    // ---------- API pública del widget (botones de la web) ----------
    const sendRef = useRef(sendMessage);
    sendRef.current = sendMessage;

    useEffect(() => {
        window.SofiaWidget = {
            open: (message) => {
                setOpen(true);
                if (message) {
                    setTimeout(() => sendRef.current(message), 350);
                }
            },
            close: () => setOpen(false),
        };
        return () => {
            delete window.SofiaWidget;
        };
    }, []);

    // ---------- Acciones del carrito desde la UI ----------
    const handleAddProduct = useCallback(
        async (product) => {
            try {
                const { cart } = await addCartItem(sessionId, product.id, 1);
                pushMessage({ role: 'assistant', type: 'cart', data: cart });
            } catch (e) {
                pushMessage({ role: 'assistant', type: 'error', content: e.message });
            }
        },
        [sessionId, pushMessage]
    );

    const handleRemoveProduct = useCallback(
        async (productId) => {
            try {
                const { cart } = await removeCartItem(sessionId, productId);
                pushMessage({ role: 'assistant', type: 'cart', data: cart });
            } catch (e) {
                pushMessage({ role: 'assistant', type: 'error', content: e.message });
            }
        },
        [sessionId, pushMessage]
    );

    const handleQuote = useCallback(
        (product) => {
            sendMessage(`Hola, quiero cotizar el producto "${product.name}". ¿Me ayudas?`);
        },
        [sendMessage]
    );

    const handlePayCart = useCallback(() => {
        sendMessage('Quiero pagar mi carrito, genera el checkout por favor.');
    }, [sendMessage]);

    // ---------- Pago con Culqi ----------
    const handleOpenPayment = useCallback(
        (checkout) => {
            openCulqiCheckout({
                publicKey: config ? config.culqi_public_key : '',
                amountCents: checkout.amount_cents,
                description: checkout.description,
                onToken: async ({ tokenId, email }) => {
                    pushMessage({
                        role: 'assistant',
                        type: 'text',
                        content: '⏳ Procesando tu pago de forma segura…',
                    });
                    try {
                        const result = await submitPayment({
                            orderCode: checkout.order.code,
                            tokenId,
                            email,
                        });
                        if (result.success) {
                            pushMessage({ role: 'assistant', type: 'payment', data: result });
                        } else {
                            pushMessage({
                                role: 'assistant',
                                type: 'error',
                                content: result.message || 'El pago no pudo completarse. Intenta nuevamente.',
                            });
                        }
                    } catch {
                        pushMessage({
                            role: 'assistant',
                            type: 'error',
                            content:
                                'No pudimos confirmar el estado del pago. Si el cobro se realizó, contáctanos por WhatsApp con el código ' +
                                checkout.order.code +
                                '.',
                        });
                    }
                },
                onError: (message) => {
                    pushMessage({ role: 'assistant', type: 'error', content: message });
                },
            });
        },
        [config, pushMessage]
    );

    const handleClear = useCallback(() => {
        clearConversation();
        setMessages([WELCOME]);
    }, []);

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(input);
        }
    };

    const whatsappUrl = config && config.whatsapp_number
        ? `https://wa.me/${config.whatsapp_number}?text=${encodeURIComponent('Hola Devioz, vengo del chat con SofIA.')}`
        : null;

    const showQuickReplies = !busy && messages.filter((m) => m.role === 'user').length === 0;

    // ---------- Render ----------
    return (
        <div className="sofia-root">
            {open ? (
                <div className="sofia-window" role="dialog" aria-label="Chat con SofIA">
                    <div className="sofia-header">
                        <div className="sofia-avatar">S</div>
                        <div className="sofia-header-info">
                            <h4>SofIA</h4>
                            <div className="sofia-status">En línea · Asesora Devioz</div>
                        </div>
                        <div className="sofia-header-actions">
                            {whatsappUrl ? (
                                <a
                                    href={whatsappUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="sofia-icon-btn"
                                    title="WhatsApp"
                                    style={{ textDecoration: 'none', display: 'grid', placeItems: 'center' }}
                                >
                                    ✆
                                </a>
                            ) : null}
                            <button className="sofia-icon-btn" title="Nueva conversación" onClick={handleClear}>
                                ⟳
                            </button>
                            <button className="sofia-icon-btn" title="Cerrar" onClick={() => setOpen(false)}>
                                ✕
                            </button>
                        </div>
                    </div>

                    <div className="sofia-body" ref={bodyRef}>
                        {messages.map((msg) => {
                            if (msg.type === 'catalog') {
                                return (
                                    <ProductCards
                                        key={msg.id}
                                        products={msg.data || []}
                                        busy={busy}
                                        onAdd={handleAddProduct}
                                        onQuote={handleQuote}
                                    />
                                );
                            }
                            if (msg.type === 'cart') {
                                return (
                                    <CartCard
                                        key={msg.id}
                                        cart={msg.data}
                                        busy={busy}
                                        onRemove={handleRemoveProduct}
                                        onPay={handlePayCart}
                                    />
                                );
                            }
                            if (msg.type === 'checkout') {
                                return (
                                    <CheckoutCard
                                        key={msg.id}
                                        checkout={msg.data}
                                        busy={busy}
                                        onOpenPayment={() => handleOpenPayment(msg.data)}
                                    />
                                );
                            }
                            if (msg.type === 'handoff') {
                                return <HandoffCard key={msg.id} url={msg.data.url} />;
                            }
                            if (msg.type === 'payment') {
                                return <PaymentResult key={msg.id} data={msg.data} />;
                            }

                            const isUser = msg.role === 'user';
                            return (
                                <div
                                    key={msg.id}
                                    className={`sofia-msg ${isUser ? 'sofia-msg-user' : 'sofia-msg-assistant'}`}
                                >
                                    <div
                                        className={`sofia-bubble ${msg.type === 'error' ? 'sofia-error-bubble' : ''}`}
                                    >
                                        <RichText text={msg.content} />
                                    </div>
                                </div>
                            );
                        })}

                        {typing ? <TypingIndicator label={toolNote} /> : null}
                    </div>

                    {showQuickReplies ? (
                        <div className="sofia-quick">
                            {QUICK_REPLIES.map((reply) => (
                                <button key={reply} className="sofia-chip" onClick={() => sendMessage(reply)}>
                                    {reply}
                                </button>
                            ))}
                        </div>
                    ) : null}

                    <div className="sofia-footer">
                        <textarea
                            className="sofia-input"
                            rows={1}
                            placeholder="Escribe tu mensaje…"
                            value={input}
                            disabled={busy}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={handleKeyDown}
                        />
                        <button
                            className="sofia-send"
                            disabled={busy || input.trim() === ''}
                            onClick={() => sendMessage(input)}
                            title="Enviar"
                        >
                            ➤
                        </button>
                    </div>
                    <div className="sofia-powered">Devioz · Pagos seguros con Culqi · IA por Groq</div>
                </div>
            ) : (
                <button
                    className="sofia-launcher"
                    onClick={() => setOpen(true)}
                    aria-label="Abrir chat con SofIA"
                >
                    <span className="sofia-pulse" />
                    💬
                </button>
            )}
        </div>
    );
}
