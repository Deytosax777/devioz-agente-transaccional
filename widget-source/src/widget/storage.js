/**
 * Persistencia local del widget: sesión y historial de mensajes
 * sobreviven recargas de página usando localStorage.
 */

const SESSION_KEY = 'sofia_session_id';
const MESSAGES_KEY = 'sofia_messages';
const OPEN_KEY = 'sofia_open';
const MAX_STORED_MESSAGES = 60;

function uuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

export function getSessionId() {
    try {
        let id = localStorage.getItem(SESSION_KEY);
        if (!id) {
            id = uuid();
            localStorage.setItem(SESSION_KEY, id);
        }
        return id;
    } catch {
        return uuid();
    }
}

export function loadMessages() {
    try {
        const raw = localStorage.getItem(MESSAGES_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function saveMessages(messages) {
    try {
        const storable = messages
            .filter((m) => !m.streaming)
            .slice(-MAX_STORED_MESSAGES);
        localStorage.setItem(MESSAGES_KEY, JSON.stringify(storable));
    } catch {
        /* almacenamiento lleno o bloqueado: ignorar */
    }
}

export function loadOpenState() {
    try {
        return localStorage.getItem(OPEN_KEY) === '1';
    } catch {
        return false;
    }
}

export function saveOpenState(open) {
    try {
        localStorage.setItem(OPEN_KEY, open ? '1' : '0');
    } catch {
        /* ignorar */
    }
}

export function clearConversation() {
    try {
        localStorage.removeItem(MESSAGES_KEY);
    } catch {
        /* ignorar */
    }
}
