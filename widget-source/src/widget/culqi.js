/**
 * Integración con Culqi Checkout v4 dentro del chat.
 * Carga el script bajo demanda y abre la pasarela (tarjetas + Yape, en PEN).
 */

const CULQI_SRC = 'https://checkout.culqi.com/js/v4';

let scriptPromise = null;

export function loadCulqiScript() {
    if (window.Culqi) return Promise.resolve();
    if (scriptPromise) return scriptPromise;

    scriptPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = CULQI_SRC;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => {
            scriptPromise = null;
            reject(new Error('No se pudo cargar la pasarela de pago Culqi.'));
        };
        document.head.appendChild(script);
    });

    return scriptPromise;
}

/**
 * Abre Culqi Checkout para una orden.
 *
 * @param {object}   params
 * @param {string}   params.publicKey    Llave pública pk_test_/pk_live_
 * @param {number}   params.amountCents  Monto en céntimos de Sol
 * @param {string}   params.description  Descripción del cobro
 * @param {function} params.onToken      fn({ tokenId, email }) al tokenizar
 * @param {function} params.onError      fn(message)
 */
export async function openCulqiCheckout({ publicKey, amountCents, description, onToken, onError }) {
    if (!publicKey) {
        onError('La pasarela de pago no está configurada (falta la llave pública de Culqi).');
        return;
    }

    try {
        await loadCulqiScript();
    } catch (e) {
        onError(e.message);
        return;
    }

    const Culqi = window.Culqi;

    Culqi.publicKey = publicKey;
    Culqi.settings({
        title: 'Devioz',
        currency: 'PEN',
        amount: amountCents,
        description,
    });
    Culqi.options({
        lang: 'auto',
        installments: false,
        paymentMethods: {
            tarjeta: true,
            yape: true,
            billetera: false,
            bancaMovil: false,
            agente: false,
            cuotealo: false,
        },
        style: {
            logo: window.location.origin + '/assets/images/logo.svg',
            bannerColor: '#0f1629',
            buttonBackground: '#3b82f6',
        },
    });

    // Callback global que Culqi invoca al tokenizar o fallar
    window.culqi = function culqiCallback() {
        if (Culqi.token) {
            const tokenId = Culqi.token.id;
            const email = Culqi.token.email || '';
            Culqi.close();
            onToken({ tokenId, email });
        } else if (Culqi.error) {
            const message =
                Culqi.error.user_message || Culqi.error.merchant_message || 'El pago no pudo procesarse.';
            onError(message);
        }
    };

    Culqi.open();
}
