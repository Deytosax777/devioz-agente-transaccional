/**
 * Componentes de presentación del widget SofIA.
 */

/** Render minimalista de markdown: **negritas** y saltos de línea. */
export function RichText({ text }) {
    const parts = String(text).split(/(\*\*[^*]+\*\*)/g);

    return (
        <>
            {parts.map((part, i) =>
                part.startsWith('**') && part.endsWith('**') ? (
                    <strong key={i}>{part.slice(2, -2)}</strong>
                ) : (
                    <span key={i}>{part}</span>
                )
            )}
        </>
    );
}

export function TypingIndicator() {
    return (
        <div className="sofia-typing" aria-label="SofIA está escribiendo">
            <span /><span /><span />
        </div>
    );
}

export function ProductCards({ products, onAdd, onQuote, busy }) {
    return (
        <div className="sofia-cards">
            {products.map((p) => (
                <div className="sofia-card" key={p.id}>
                    <div className="sofia-card-head">
                        <span className="sofia-card-name">{p.name}</span>
                        {p.quote_only ? (
                            <span className="sofia-card-quote">A cotizar</span>
                        ) : (
                            <span className="sofia-card-price">{p.price_label}</span>
                        )}
                    </div>
                    <div className="sofia-card-meta">
                        {p.category} · {p.tier}
                    </div>
                    {p.description ? <div className="sofia-card-desc">{p.description}</div> : null}
                    {p.quote_only ? (
                        <button
                            className="sofia-btn sofia-btn-outline"
                            disabled={busy}
                            onClick={() => onQuote(p)}
                        >
                            💬 Solicitar cotización
                        </button>
                    ) : (
                        <button
                            className="sofia-btn sofia-btn-primary"
                            disabled={busy}
                            onClick={() => onAdd(p)}
                        >
                            🛒 Agregar al carrito
                        </button>
                    )}
                </div>
            ))}
        </div>
    );
}

export function CartCard({ cart, onRemove, onPay, busy }) {
    const empty = !cart || !cart.items || cart.items.length === 0;

    return (
        <div className="sofia-cart">
            <div className="sofia-cart-title">🛒 Tu carrito</div>
            {empty ? (
                <div style={{ fontSize: 12.5, color: '#64748b' }}>
                    Tu carrito está vacío. Pídeme ver el catálogo para empezar.
                </div>
            ) : (
                <>
                    {cart.items.map((item) => (
                        <div className="sofia-cart-row" key={item.product_id}>
                            <span style={{ flex: 1 }}>
                                {item.name} <small>×{item.quantity}</small>
                            </span>
                            <span>S/ {item.subtotal.toFixed(2)}</span>
                            <button
                                className="sofia-cart-remove"
                                title="Quitar"
                                disabled={busy}
                                onClick={() => onRemove(item.product_id)}
                            >
                                ✕
                            </button>
                        </div>
                    ))}
                    <div className="sofia-cart-total">
                        <span>Total</span>
                        <span>S/ {cart.total.toFixed(2)}</span>
                    </div>
                    <button
                        className="sofia-btn sofia-btn-success"
                        style={{ marginTop: 10 }}
                        disabled={busy}
                        onClick={onPay}
                    >
                        💳 Pagar ahora
                    </button>
                </>
            )}
        </div>
    );
}

export function CheckoutCard({ checkout, onOpenPayment, busy }) {
    const { order } = checkout;

    return (
        <div className="sofia-cart">
            <div className="sofia-cart-title">🧾 Orden {order.code}</div>
            {order.items.map((item, i) => (
                <div className="sofia-cart-row" key={i}>
                    <span style={{ flex: 1 }}>
                        {item.name} <small>×{item.quantity}</small>
                    </span>
                    <span>S/ {(item.unit_price * item.quantity).toFixed(2)}</span>
                </div>
            ))}
            <div className="sofia-cart-total">
                <span>Total a pagar</span>
                <span>S/ {order.total.toFixed(2)}</span>
            </div>
            <button
                className="sofia-btn sofia-btn-dark"
                style={{ marginTop: 10 }}
                disabled={busy || order.status === 'paid'}
                onClick={onOpenPayment}
            >
                {order.status === 'paid' ? '✅ Pagado' : '🔒 Pagar con tarjeta o Yape'}
            </button>
            <div style={{ fontSize: 10.5, color: '#94a3b8', textAlign: 'center', marginTop: 6 }}>
                Pago seguro en Soles (PEN) procesado por Culqi
            </div>
        </div>
    );
}

export function HandoffCard({ url }) {
    return (
        <div className="sofia-cart">
            <div className="sofia-cart-title">🧑‍💼 Asesor humano</div>
            <div style={{ fontSize: 12.5, color: '#64748b', marginBottom: 10 }}>
                Te conecto con nuestro equipo comercial por WhatsApp para continuar.
            </div>
            <a href={url} target="_blank" rel="noopener noreferrer" style={{ textDecoration: 'none' }}>
                <button className="sofia-btn sofia-btn-success">📱 Abrir WhatsApp</button>
            </a>
        </div>
    );
}

export function PaymentResult({ data }) {
    return (
        <div className="sofia-payment-ok">
            <h5>✅ ¡Pago confirmado!</h5>
            <p>
                Orden <strong>{data.order.code}</strong> pagada por{' '}
                <strong>S/ {data.order.total.toFixed(2)}</strong>.
            </p>
            <p>Gracias por confiar en Devioz. Te contactaremos con los entregables.</p>
        </div>
    );
}
