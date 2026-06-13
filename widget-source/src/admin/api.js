/**
 * Cliente API del panel admin: fetch con Bearer token y manejo de 401.
 */

const TOKEN_KEY = 'devioz_admin_token';
const ADMIN_KEY = 'devioz_admin_user';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function getStoredAdmin() {
    try {
        return JSON.parse(localStorage.getItem(ADMIN_KEY) || 'null');
    } catch {
        return null;
    }
}

export function storeSession(token, admin) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(ADMIN_KEY, JSON.stringify(admin));
}

export function clearSession() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(ADMIN_KEY);
}

async function request(path, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };

    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;

    const res = await fetch(path, { ...options, headers });

    if (res.status === 401) {
        clearSession();
        window.dispatchEvent(new CustomEvent('devioz:logout'));
        throw new Error('Sesión expirada. Inicia sesión nuevamente.');
    }

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        throw new Error(data.message || `Error ${res.status}`);
    }

    return data;
}

export const api = {
    login: (email, password) =>
        request('/api/admin/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        }),
    stats: () => request('/api/admin/stats'),
    products: () => request('/api/admin/products'),
    categories: () => request('/api/admin/categories'),
    createProduct: (payload) =>
        request('/api/admin/products', { method: 'POST', body: JSON.stringify(payload) }),
    updateProduct: (id, payload) =>
        request(`/api/admin/products/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
    deleteProduct: (id) => request(`/api/admin/products/${id}`, { method: 'DELETE' }),
    orders: (status = '') =>
        request(`/api/admin/orders${status ? `?status=${encodeURIComponent(status)}` : ''}`),
};
