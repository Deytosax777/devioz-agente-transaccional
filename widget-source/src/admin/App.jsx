import { useCallback, useEffect, useState } from 'react';
import { api, clearSession, getStoredAdmin, getToken, storeSession } from './api.js';

const TIERS = ['Básico', 'Pro', 'Premium', 'Enterprise'];

const EMPTY_FORM = {
    name: '',
    category_id: 1,
    tier: 'Básico',
    price_offer: '',
    quote_only: false,
    description: '',
    active: true,
};

// ============================== LOGIN ==============================

function Login({ onLogin }) {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            const { token, admin } = await api.login(email, password);
            storeSession(token, admin);
            onLogin(admin);
        } catch (err) {
            setError(err.message);
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="login-wrap">
            <form className="login-card" onSubmit={submit}>
                <h1>Devioz · Admin</h1>
                <p>Panel de gestión de productos y transacciones</p>
                {error ? <div className="error-box">{error}</div> : null}
                <div className="field">
                    <label>Email</label>
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="admin@devioz.pe"
                        required
                    />
                </div>
                <div className="field">
                    <label>Contraseña</label>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="••••••••"
                        required
                    />
                </div>
                <button className="btn btn-primary btn-block" disabled={busy}>
                    {busy ? 'Ingresando…' : 'Iniciar sesión'}
                </button>
            </form>
        </div>
    );
}

// ============================== DASHBOARD ==============================

function Dashboard() {
    const [stats, setStats] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        api.stats().then(setStats).catch((e) => setError(e.message));
    }, []);

    if (error) return <div className="error-box">{error}</div>;
    if (!stats) return <div className="empty">Cargando métricas…</div>;

    return (
        <>
            <h2>Dashboard</h2>
            <div className="stats-grid">
                <div className="stat-card">
                    <div className="label">Ingresos (pagado)</div>
                    <div className="value green">S/ {Number(stats.revenue).toFixed(2)}</div>
                </div>
                <div className="stat-card">
                    <div className="label">Órdenes pagadas</div>
                    <div className="value">{stats.orders_paid}</div>
                </div>
                <div className="stat-card">
                    <div className="label">Órdenes pendientes</div>
                    <div className="value">{stats.orders_pending}</div>
                </div>
                <div className="stat-card">
                    <div className="label">Conversaciones SofIA</div>
                    <div className="value">{stats.conversations}</div>
                </div>
                <div className="stat-card">
                    <div className="label">Productos activos</div>
                    <div className="value">{stats.products_active}</div>
                </div>
            </div>

            <div className="card">
                <div className="card-head"><h3>Top productos vendidos</h3></div>
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr>
                        </thead>
                        <tbody>
                            {stats.top_products.length === 0 ? (
                                <tr><td colSpan={3} className="empty">Aún no hay ventas registradas.</td></tr>
                            ) : (
                                stats.top_products.map((p, i) => (
                                    <tr key={i}>
                                        <td>{p.name}</td>
                                        <td>{p.sold}</td>
                                        <td className="price-cell">S/ {Number(p.revenue).toFixed(2)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

// ============================== PRODUCTOS ==============================

function ProductModal({ initial, categories, onSave, onClose }) {
    const [form, setForm] = useState(initial);
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    const set = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    const submit = async (e) => {
        e.preventDefault();
        setBusy(true);
        setError(null);
        try {
            await onSave({
                ...form,
                category_id: Number(form.category_id),
                price_offer: form.quote_only || form.price_offer === '' ? null : Number(form.price_offer),
            });
            onClose();
        } catch (err) {
            setError(err.message);
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && onClose()}>
            <form className="modal" onSubmit={submit}>
                <h3>{form.id ? 'Editar producto' : 'Nuevo producto'}</h3>
                {error ? <div className="error-box">{error}</div> : null}

                <div className="field">
                    <label>Nombre</label>
                    <input value={form.name} onChange={(e) => set('name', e.target.value)} required maxLength={160} />
                </div>
                <div className="field">
                    <label>Categoría</label>
                    <select value={form.category_id} onChange={(e) => set('category_id', e.target.value)}>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                    </select>
                </div>
                <div className="field">
                    <label>Tier</label>
                    <select value={form.tier} onChange={(e) => set('tier', e.target.value)}>
                        {TIERS.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                </div>
                <div className="field field-check">
                    <input
                        type="checkbox"
                        id="quoteOnly"
                        checked={form.quote_only}
                        onChange={(e) => set('quote_only', e.target.checked)}
                    />
                    <label htmlFor="quoteOnly" style={{ margin: 0 }}>Producto a cotizar (sin precio fijo)</label>
                </div>
                {!form.quote_only ? (
                    <div className="field">
                        <label>Precio (S/ PEN)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            value={form.price_offer}
                            onChange={(e) => set('price_offer', e.target.value)}
                            placeholder="19.99"
                        />
                    </div>
                ) : null}
                <div className="field">
                    <label>Descripción</label>
                    <textarea
                        rows={3}
                        value={form.description || ''}
                        onChange={(e) => set('description', e.target.value)}
                    />
                </div>
                <div className="field field-check">
                    <input
                        type="checkbox"
                        id="activeChk"
                        checked={form.active}
                        onChange={(e) => set('active', e.target.checked)}
                    />
                    <label htmlFor="activeChk" style={{ margin: 0 }}>Producto activo (visible en catálogo)</label>
                </div>

                <div className="modal-actions">
                    <button type="button" className="btn btn-ghost" onClick={onClose}>Cancelar</button>
                    <button className="btn btn-primary" disabled={busy}>
                        {busy ? 'Guardando…' : 'Guardar'}
                    </button>
                </div>
            </form>
        </div>
    );
}

function Products() {
    const [products, setProducts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [editing, setEditing] = useState(null);
    const [error, setError] = useState(null);

    const reload = useCallback(() => {
        api.products().then((d) => setProducts(d.products)).catch((e) => setError(e.message));
        api.categories().then((d) => setCategories(d.categories)).catch(() => {});
    }, []);

    useEffect(reload, [reload]);

    const save = async (form) => {
        if (form.id) {
            await api.updateProduct(form.id, form);
        } else {
            await api.createProduct(form);
        }
        reload();
    };

    const deactivate = async (product) => {
        if (!window.confirm(`¿Desactivar "${product.name}"? Dejará de mostrarse en el catálogo.`)) return;
        try {
            await api.deleteProduct(product.id);
            reload();
        } catch (e) {
            setError(e.message);
        }
    };

    return (
        <>
            <h2>Productos</h2>
            {error ? <div className="error-box">{error}</div> : null}

            <div className="card">
                <div className="card-head">
                    <h3>Catálogo ({products.length})</h3>
                    <button
                        className="btn btn-primary btn-sm"
                        onClick={() => setEditing({ ...EMPTY_FORM, category_id: categories[0]?.id || 1 })}
                    >
                        + Nuevo producto
                    </button>
                </div>
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Tier</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map((p) => (
                                <tr key={p.id}>
                                    <td><strong>{p.name}</strong></td>
                                    <td className="muted">{p.category}</td>
                                    <td><span className="badge badge-tier">{p.tier}</span></td>
                                    <td className="price-cell">{p.price_label}</td>
                                    <td>
                                        <span className={`badge ${p.active ? 'badge-on' : 'badge-off'}`}>
                                            {p.active ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </td>
                                    <td style={{ whiteSpace: 'nowrap' }}>
                                        <button
                                            className="btn btn-ghost btn-sm"
                                            onClick={() =>
                                                setEditing({
                                                    id: p.id,
                                                    name: p.name,
                                                    category_id: p.category_id,
                                                    tier: p.tier,
                                                    price_offer: p.price_offer ?? '',
                                                    quote_only: p.price_offer === null,
                                                    description: p.description || '',
                                                    active: p.active,
                                                })
                                            }
                                        >
                                            Editar
                                        </button>{' '}
                                        {p.active ? (
                                            <button className="btn btn-danger btn-sm" onClick={() => deactivate(p)}>
                                                Desactivar
                                            </button>
                                        ) : null}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {editing ? (
                <ProductModal
                    initial={editing}
                    categories={categories}
                    onSave={save}
                    onClose={() => setEditing(null)}
                />
            ) : null}
        </>
    );
}

// ============================== ÓRDENES ==============================

const STATUS_LABELS = {
    paid: 'Pagada',
    pending: 'Pendiente',
    failed: 'Fallida',
    refunded: 'Reembolsada',
};

function Orders() {
    const [orders, setOrders] = useState([]);
    const [filter, setFilter] = useState('');
    const [error, setError] = useState(null);

    useEffect(() => {
        api.orders(filter).then((d) => setOrders(d.orders)).catch((e) => setError(e.message));
    }, [filter]);

    return (
        <>
            <h2>Órdenes y transacciones</h2>
            {error ? <div className="error-box">{error}</div> : null}

            <div className="card">
                <div className="card-head">
                    <h3>Transacciones ({orders.length})</h3>
                    <select
                        value={filter}
                        onChange={(e) => setFilter(e.target.value)}
                        style={{ border: '1px solid var(--border)', borderRadius: 8, padding: '7px 10px' }}
                    >
                        <option value="">Todas</option>
                        <option value="paid">Pagadas</option>
                        <option value="pending">Pendientes</option>
                        <option value="failed">Fallidas</option>
                        <option value="refunded">Reembolsadas</option>
                    </select>
                </div>
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Detalle</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Culqi</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.length === 0 ? (
                                <tr><td colSpan={7} className="empty">No hay órdenes con este filtro.</td></tr>
                            ) : (
                                orders.map((o) => (
                                    <tr key={o.id}>
                                        <td><strong>{o.code}</strong></td>
                                        <td className="muted">{o.customer_email || '—'}</td>
                                        <td className="order-items">
                                            {o.items.map((i, k) => (
                                                <div key={k}>{i.name} ×{i.quantity}</div>
                                            ))}
                                        </td>
                                        <td className="price-cell">S/ {Number(o.total).toFixed(2)}</td>
                                        <td>
                                            <span className={`badge badge-${o.status}`}>
                                                {STATUS_LABELS[o.status] || o.status}
                                            </span>
                                        </td>
                                        <td className="muted" style={{ fontSize: 11.5 }}>
                                            {o.culqi_charge_id || '—'}
                                        </td>
                                        <td className="muted">{o.paid_at || o.created_at}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

// ============================== APP ==============================

export default function App() {
    const [admin, setAdmin] = useState(() => (getToken() ? getStoredAdmin() : null));
    const [view, setView] = useState('dashboard');

    useEffect(() => {
        const onLogout = () => setAdmin(null);
        window.addEventListener('devioz:logout', onLogout);
        return () => window.removeEventListener('devioz:logout', onLogout);
    }, []);

    if (!admin) {
        return <Login onLogin={setAdmin} />;
    }

    const logout = () => {
        clearSession();
        setAdmin(null);
    };

    return (
        <div className="layout">
            <aside className="sidebar">
                <div className="brand">DEVIOZ <span>ADMIN</span></div>
                <button
                    className={`nav-btn ${view === 'dashboard' ? 'active' : ''}`}
                    onClick={() => setView('dashboard')}
                >
                    📊 Dashboard
                </button>
                <button
                    className={`nav-btn ${view === 'products' ? 'active' : ''}`}
                    onClick={() => setView('products')}
                >
                    📦 Productos
                </button>
                <button
                    className={`nav-btn ${view === 'orders' ? 'active' : ''}`}
                    onClick={() => setView('orders')}
                >
                    💳 Órdenes
                </button>
                <div className="spacer" />
                <div className="user">{admin.email}</div>
                <button className="nav-btn" onClick={logout}>🚪 Cerrar sesión</button>
            </aside>

            <main className="main">
                {view === 'dashboard' ? <Dashboard /> : null}
                {view === 'products' ? <Products /> : null}
                {view === 'orders' ? <Orders /> : null}
            </main>
        </div>
    );
}
