# Devioz — Web Corporativa + Agente Transaccional SofIA

Sistema completo de presencia digital B2B para **Devioz**, consultora tecnológica peruana. Combina una web corporativa con posicionamiento SEO y un agente conversacional de IA (**SofIA**) capaz de mostrar el catálogo, gestionar un carrito de compras y cobrar en Soles directamente desde el chat.

---

## Índice

1. [Descripción general](#descripción-general)
2. [Stack tecnológico](#stack-tecnológico)
3. [Arquitectura del sistema](#arquitectura-del-sistema)
4. [Cómo funciona SofIA](#cómo-funciona-sofia)
5. [Instalación local](#instalación-local)
6. [Variables de entorno](#variables-de-entorno)
7. [Endpoints de la API](#endpoints-de-la-api)
8. [Panel de administración](#panel-de-administración)
9. [Seguridad](#seguridad)
10. [Despliegue en producción](#despliegue-en-producción)
11. [Estructura del proyecto](#estructura-del-proyecto)

---

## Descripción general

| Módulo | Descripción |
| --- | --- |
| **Web corporativa** | Página pública server-side con hero 3D interactivo, catálogo de servicios y productos, SEO on-page y Open Graph |
| **Agente SofIA** | Widget React embebido que conecta con un agente de IA (Groq + Llama 3 70B) vía Server-Sent Events; gestiona carrito y procesa pagos |
| **API REST** | Backend PHP 8.2 + Slim 4 con autenticación, rate limiting y lógica de negocio desacoplada |
| **Panel Admin** | SPA React para gestionar productos, ver órdenes y monitorear métricas en tiempo real |

---

## Stack tecnológico

| Capa | Tecnología | Versión |
| --- | --- | --- |
| Backend | PHP + Slim Framework | 8.2 / 4.x |
| ORM | Eloquent (illuminate/database) | 10.x |
| Base de datos | MySQL (InnoDB, utf8mb4) | 8.0 |
| IA generativa | Groq API — Llama 3 70B | llama-3.3-70b-versatile |
| Streaming | Server-Sent Events (SSE) | — |
| Pagos | Culqi v4 (tarjetas + Yape, PEN) | — |
| Widget chat | React 18 + Vite (bundle IIFE) | 18 / 5.x |
| Panel admin | React 18 SPA + Vite | 18 / 5.x |
| Web pública | PHP server-side + Bootstrap 5 + Three.js | — |
| HTTP client | Guzzle | 7.x |

---

## Arquitectura del sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                        NAVEGADOR                                 │
│                                                                  │
│  ┌──────────────────┐   ┌────────────┐   ┌───────────────────┐  │
│  │  Web corporativa  │   │Widget SofIA│   │   Panel Admin     │  │
│  │  (PHP server-side)│   │(React IIFE)│   │   (React SPA)     │  │
│  └────────┬─────────┘   └─────┬──────┘   └────────┬──────────┘  │
└───────────┼─────────────────  │  ───────────────  │  ───────────┘
            │  HTTP             │  SSE / REST        │  REST + JWT
┌───────────▼───────────────────▼────────────────────▼────────────┐
│                    PHP 8.2 + Slim Framework 4                    │
│                                                                  │
│  CorsMiddleware → RateLimitMiddleware → AdminAuthMiddleware       │
│                                                                  │
│  ┌──────────────┐  ┌─────────────┐  ┌────────────────────────┐  │
│  │ HomeController│  │ChatController│  │ProductController       │  │
│  │ (render HTML) │  │(SSE stream) │  │PaymentController       │  │
│  └──────────────┘  └──────┬──────┘  │WebhookController       │  │
│                            │         │AdminController          │  │
│                    ┌───────▼──────┐  └────────────────────────┘  │
│                    │  GroqService │                               │
│                    │ ToolExecutor │                               │
│                    └───────┬──────┘                              │
└───────────────────────────┼──────────────────────────────────────┘
                             │
          ┌──────────────────┼──────────────────┐
          ▼                  ▼                   ▼
   ┌─────────────┐   ┌──────────────┐   ┌──────────────┐
   │  MySQL 8.0   │   │  Groq API    │   │  Culqi API   │
   │  (Eloquent)  │   │ (Llama 3 70B)│   │  (pagos PEN) │
   └─────────────┘   └──────────────┘   └──────────────┘
```

---

## Cómo funciona SofIA

SofIA es un agente de IA transaccional: no solo responde preguntas, sino que ejecuta acciones reales (consultar BD, modificar carritos, generar órdenes de pago).

### Flujo de una conversación

```
Usuario escribe → POST /api/chat/message
                       │
                       ▼
              ChatController abre SSE
                       │
                       ▼
         ┌─────────────────────────────┐
         │      Bucle agente (max 4)    │
         │                             │
         │  1. Envía mensajes a Groq    │
         │     (streaming tokens →     │
         │      emite event: token)    │
         │                             │
         │  2. ¿Groq pidió usar una    │
         │     herramienta?            │
         │     ├─ SÍ: ejecuta tool     │
         │     │    emite event: UI    │
         │     │    inyecta resultado  │
         │     │    vuelve al paso 1   │
         │     └─ NO: fin del bucle   │
         └─────────────────────────────┘
                       │
                       ▼
              event: done → cierra SSE
```

### Herramientas disponibles (Function Calling)

| Herramienta | Qué hace | Evento SSE emitido |
| --- | --- | --- |
| `get_catalog` | Consulta productos reales de la BD (nunca inventa precios) | `catalog` → muestra tarjetas |
| `add_to_cart` | Agrega un producto al carrito de la sesión | `cart` → actualiza carrito |
| `remove_from_cart` | Elimina un producto del carrito | `cart` → actualiza carrito |
| `get_cart` | Devuelve el estado actual del carrito | `cart` → muestra resumen |
| `generate_checkout` | Crea la orden en BD (transacción ACID) y abre Culqi | `checkout` → abre pago |
| `human_handoff` | Genera link de WhatsApp con contexto para asesor humano | `handoff` → muestra botón |

### Flujo de pago

```
generate_checkout (tool)
        │
        ▼
  Orden creada en BD              ← monto fijado aquí, nunca desde el cliente
        │
        ▼
  event: checkout → widget        ← Culqi Checkout v4 se abre en el chat
        │
        ▼
  Usuario paga (tarjeta/Yape)
        │
        ▼
  Culqi tokeniza → token_id llega al servidor
        │
        ▼
  POST /api/checkout
  PaymentController carga monto de la orden en BD
  → CulqiPaymentService::createCharge()
        │
        ▼
  Webhook POST /api/webhooks/culqi
  ├─ Valida firma HMAC-SHA256
  └─ Re-consulta cargo en Culqi (server-to-server)
     → actualiza orden a "paid"
```

---

## Instalación local

### Requisitos

- PHP **8.2+** con extensiones: `pdo_mysql`, `curl`, `mbstring`, `json`, `zip`
- MySQL **8.0+**
- Composer **2.x**
- Node.js **18+** (solo si se recompila el frontend)

La forma más rápida en Windows es instalar **XAMPP** (incluye PHP, MySQL y Apache).

### Pasos

#### 1. Importar la base de datos

Abre phpMyAdmin (`http://localhost/phpmyadmin`) → pestaña **Importar** → selecciona `database/schema.sql`.

Esto crea `devioz_db` con todas las tablas, las 5 categorías, los **33 productos** del catálogo y el usuario admin inicial.

#### 2. Instalar dependencias PHP

```bash
composer install
```

Si falla por la extensión zip, habilitarla en `php.ini`:

```ini
extension=zip   ; quitar el punto y coma al inicio
```

#### 3. Configurar variables de entorno

```bash
copy .env.example .env    # Windows
cp .env.example .env      # Linux/Mac
```

Editar `.env` con las credenciales reales (ver sección siguiente).

#### 4. Levantar el servidor

```bash
php -S localhost:8080 -t public
```

#### 5. Acceder

| URL | Descripción |
| --- | --- |
| `http://localhost:8080` | Web corporativa + widget SofIA |
| `http://localhost:8080/admin/` | Panel de administración |
| `http://localhost:8080/api/health` | Healthcheck del API |

Credenciales iniciales del panel: `admin@devioz.pe` / `admin123`

Cambiar la contraseña antes de pasar a producción:

```bash
php scripts/create-admin.php admin@devioz.pe "NuevaContraseña" "Tu Nombre"
```

### Opción alternativa: Docker

Si tienes Docker Desktop instalado, un solo comando levanta PHP + MySQL con el schema ya importado:

```bash
cp .env.docker .env   # completar GROQ_API_KEY y llaves Culqi en .env
docker compose up --build
```

---

## Variables de entorno

| Variable | Descripción | Ejemplo |
| --- | --- | --- |
| `APP_KEY` | Clave secreta para firmar tokens del panel admin | cadena aleatoria ≥ 32 chars |
| `DB_HOST` | Host de MySQL | `127.0.0.1` |
| `DB_DATABASE` | Nombre de la base de datos | `devioz_db` |
| `DB_USERNAME` | Usuario MySQL | `root` |
| `DB_PASSWORD` | Contraseña MySQL | *(vacío en XAMPP por defecto)* |
| `GROQ_API_KEY` | API key de Groq | `gsk_...` → [console.groq.com](https://console.groq.com/keys) |
| `GROQ_MODEL` | Modelo Llama 3 activo en Groq | `llama-3.3-70b-versatile` |
| `CULQI_PUBLIC_KEY` | Llave pública Culqi | `pk_test_...` |
| `CULQI_SECRET_KEY` | Llave secreta Culqi | `sk_test_...` |
| `CULQI_WEBHOOK_SECRET` | Secreto para validar webhooks | configurar en panel Culqi |
| `WHATSAPP_NUMBER` | Número para handoff humano | `51999999999` |
| `CORS_ALLOWED_ORIGINS` | Orígenes CORS permitidos | `*` o `https://tudominio.pe` |
| `RATE_LIMIT_PER_MIN` | Máx. requests/min al chat por IP | `20` |

> **Nota sobre modelos Groq:** `llama3-70b-8192` fue retirado. Usar `llama-3.3-70b-versatile`.

---

## Endpoints de la API

### Públicos

```
GET    /                                   Web corporativa (HTML server-side)
GET    /api/health                         Estado del servicio
GET    /api/config                         Llave pública Culqi y número WhatsApp
GET    /api/products?category=&search=     Catálogo de productos
GET    /api/categories                     Categorías disponibles
GET    /api/cart/{sessionId}               Ver carrito de una sesión
POST   /api/cart/{sessionId}/items         Agregar producto al carrito
DELETE /api/cart/{sessionId}/items/{id}    Eliminar producto del carrito
```

### Con rate limiting

```
POST   /api/chat/message      Agente SofIA — responde por SSE (texto + eventos UI)
POST   /api/checkout          Procesar pago con token Culqi
```

### Webhook

```
POST   /api/webhooks/culqi    Confirmación de pago firmada por Culqi (HMAC-SHA256)
```

### Panel admin (requiere `Authorization: Bearer <token>`)

```
POST   /api/admin/login                    Iniciar sesión → devuelve token (8h)
GET    /api/admin/stats                    Métricas del dashboard
GET    /api/admin/products                 Listar todos los productos
POST   /api/admin/products                 Crear producto
PUT    /api/admin/products/{id}            Editar producto
DELETE /api/admin/products/{id}            Desactivar producto
GET    /api/admin/orders?status=           Listar órdenes con filtro opcional
```

---

## Panel de administración

Acceso en `http://localhost:8080/admin/`

| Sección | Funcionalidad |
| --- | --- |
| **Dashboard** | Ingresos totales, órdenes pagadas/pendientes, conversaciones con SofIA, productos activos, top 10 más vendidos |
| **Productos** | CRUD completo: crear, editar, desactivar. Soporte para precio fijo (PEN) y productos "a cotizar" (precio NULL) |
| **Órdenes** | Historial de transacciones filtrable por estado: pagada / pendiente / fallida / reembolsada |

---

## Seguridad

### Pagos

- El monto a cobrar se toma **siempre de la orden en base de datos**, nunca del cliente.
- Culqi exige un cargo mínimo de **S/ 3.00**.
- La llave secreta de Culqi nunca llega al navegador.

### Webhooks — doble verificación

1. **Firma HMAC-SHA256** del payload (si `CULQI_WEBHOOK_SECRET` está configurado).
2. **Re-consulta server-to-server** a la API de Culqi — nunca se confía ciegamente en el payload recibido.
3. Todos los eventos se guardan en `webhook_events` para auditoría completa.

### Autenticación admin

- Tokens firmados con HMAC-SHA256 y TTL de 8 horas.
- Contraseñas almacenadas con bcrypt (cost 10).
- Rate limiting de 10 intentos/min en el endpoint de login.

### Rate limiting

Persistido en MySQL sin Redis: ventana de 60 segundos por IP y grupo de ruta. Usa `SELECT ... FOR UPDATE` para evitar condiciones de carrera.

### CORS

Configurable por entorno. En producción restringir a `https://tudominio.pe`.

---

## Despliegue en producción

1. Subir el proyecto y apuntar el vhost al directorio `public/`.

2. Instalar dependencias optimizadas:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Importar `database/schema.sql` en el servidor MySQL de producción.

4. Configurar `.env` con llaves **live** de Culqi (`pk_live_` / `sk_live_`).

5. Cambiar la contraseña del admin:

   ```bash
   php scripts/create-admin.php admin@devioz.pe "ContraseñaSegura" "Nombre"
   ```

6. Registrar el webhook en el panel de Culqi: `https://tudominio.pe/api/webhooks/culqi`

7. Restringir `CORS_ALLOWED_ORIGINS` al dominio y usar **HTTPS** (requerido por Culqi).

8. Configurar el servidor para no bufferizar el endpoint SSE.

   **Nginx:**

   ```nginx
   location /api/chat/ {
       proxy_buffering    off;
       proxy_cache        off;
       proxy_read_timeout 120s;
   }
   ```

   **Apache:** el `.htaccess` incluido ya desactiva `mod_deflate` para `/api/chat/`.

---

## Estructura del proyecto

```
devioz-web-completa/
│
├── public/                        ← DocumentRoot del servidor web
│   ├── index.php                  Front controller de Slim 4
│   ├── .htaccess                  Rewrite para Apache
│   ├── assets/
│   │   ├── css/style.css          Estilos de la web corporativa
│   │   └── js/
│   │       ├── three-hero.js      Hero 3D con Three.js (esfera de partículas)
│   │       └── main.js            Scripts generales de la web
│   ├── widget/
│   │   └── sofia-widget.js        Bundle IIFE del widget SofIA (React 18)
│   └── admin/                     Bundle SPA del panel de administración
│
├── src/
│   ├── Controllers/
│   │   ├── HomeController.php     Renderiza la web corporativa
│   │   ├── ChatController.php     Agente SofIA (SSE + bucle de herramientas)
│   │   ├── PaymentController.php  Cobra con Culqi (monto desde BD)
│   │   ├── WebhookController.php  Valida y concilia pagos de Culqi
│   │   ├── ProductController.php  Catálogo público
│   │   ├── CartController.php     Gestión de carritos
│   │   ├── AuthController.php     Login del panel admin
│   │   └── AdminController.php    CRUD de productos y órdenes (protegido)
│   │
│   ├── Models/                    Modelos Eloquent
│   │   ├── Product.php            isQuoteOnly(), displayPrice(), toAgentArray()
│   │   ├── Cart.php               openForSession(), total(), toSummary()
│   │   ├── Order.php              amountInCents(), generateCode(), toSummary()
│   │   ├── Conversation.php       findOrCreateBySession()
│   │   └── ...
│   │
│   ├── Services/
│   │   ├── GroqService.php        Streaming desde la API de Groq con Guzzle
│   │   ├── ToolExecutor.php       Definiciones JSON Schema + ejecución de tools
│   │   ├── CulqiPaymentService.php  createCharge(), getCharge(), verifyWebhookSignature()
│   │   └── TokenService.php       Tokens HMAC para el panel admin (TTL 8h)
│   │
│   ├── Middleware/
│   │   ├── CorsMiddleware.php          Preflight OPTIONS + cabeceras CORS
│   │   ├── RateLimitingMiddleware.php  Ventana de 60s por IP en MySQL
│   │   └── AdminAuthMiddleware.php     Valida Bearer token del panel
│   │
│   ├── Views/home.php             Web corporativa completa (Bootstrap 5 + SEO)
│   ├── bootstrap.php              Carga .env + configura Eloquent Capsule
│   └── routes.php                 Definición de todas las rutas
│
├── widget-source/                 Código fuente React (para recompilar)
│   ├── src/widget/                Fuente del widget SofIA
│   │   ├── App.jsx                Componente principal + lógica SSE
│   │   ├── api.js                 Cliente SSE y REST
│   │   ├── culqi.js               Carga lazy de Culqi Checkout v4
│   │   ├── storage.js             Persistencia en localStorage
│   │   ├── components.jsx         ProductCards, CartCard, CheckoutCard, etc.
│   │   └── styles.js              Estilos inyectados dinámicamente
│   └── src/admin/                 Fuente del panel admin
│       ├── App.jsx                Login + Dashboard + Productos + Órdenes
│       ├── api.js                 Cliente REST con manejo de token
│       └── admin.css              Estilos del panel
│
├── database/schema.sql            Esquema + seed (33 productos, 5 categorías)
├── scripts/create-admin.php       CLI para crear/actualizar usuarios admin
├── Dockerfile                     Imagen PHP 8.2 + Apache
├── docker-compose.yml             Stack completo (app + MySQL)
├── .env.example                   Plantilla de variables de entorno
└── composer.json                  Dependencias PHP
```

---

## Licencia

Proyecto propietario de **Devioz**. Todos los derechos reservados.
