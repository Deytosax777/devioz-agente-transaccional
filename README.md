# Devioz — Web Corporativa + Agente Transaccional SofIA

Sistema completo de presencia digital B2B para **Devioz**, consultora tecnológica peruana. Combina una web corporativa completa con un agente conversacional de IA (**SofIA**) capaz de mostrar el catálogo, gestionar un carrito de compras y cobrar en Soles directamente desde el chat.

---

## Índice

1. [Descripción general](#descripción-general)
2. [Stack tecnológico](#stack-tecnológico)
3. [Arquitectura del sistema](#arquitectura-del-sistema)
4. [Secciones de la web](#secciones-de-la-web)
5. [Efectos visuales y animaciones](#efectos-visuales-y-animaciones)
6. [Cómo funciona SofIA](#cómo-funciona-sofia)
7. [Instalación local](#instalación-local)
8. [Variables de entorno](#variables-de-entorno)
9. [Endpoints de la API](#endpoints-de-la-api)
10. [Panel de administración](#panel-de-administración)
11. [Seguridad](#seguridad)
12. [Despliegue en producción](#despliegue-en-producción)
13. [Estructura del proyecto](#estructura-del-proyecto)

---

## Descripción general

| Módulo | Descripción |
| --- | --- |
| **Web corporativa** | Landing page server-side con hero 3D interactivo, 11 secciones, animaciones CSS/JS, SEO on-page y Open Graph |
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
| 3D / Animaciones | Three.js + Canvas API vanilla | 0.158.0 |
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
│  │HomeController│  │ChatController│  │ProductController       │  │
│  │(render HTML) │  │(SSE stream) │  │PaymentController       │  │
│  │ContactCtrl   │  └──────┬──────┘  │WebhookController       │  │
│  │(formulario)  │         │         │AdminController          │  │
│  └──────────────┘  ┌──────▼──────┐  └────────────────────────┘  │
│                    │  GroqService │                               │
│                    │ ToolExecutor │                               │
│                    └──────┬──────┘                               │
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

## Secciones de la web

La web corporativa tiene 11 secciones en una sola página:

| # | Sección | ID | Descripción |
| --- | --- | --- | --- |
| — | **Hero** | `#inicio` | Esfera 3D Three.js, typewriter en H1, stats animadas, parallax al scroll |
| — | **Ticker** | — | Banda de estadísticas con scroll horizontal automático |
| 1 | **Servicios** | `#servicios` | 9 cards con efecto flip 3D: frente muestra ícono+nombre, reverso muestra descripción + botón cotizar |
| 2 | **Stack tecnológico** | `#tecnologias` | Grid de 16 tecnologías con hover lift |
| 3 | **Productos** | `#productos` | Catálogo dinámico desde BD con tabs por categoría (visible si hay productos activos) |
| 4 | **Casos de éxito** | `#casos` | 3 case studies (Finanzas, Retail, Manufactura) con ilustraciones SVG y métricas de impacto |
| 5 | **Cómo funciona** | `#como-funciona` | 4 pasos con conector SVG animado draw-on-scroll |
| 6 | **Video Demo** | `#demo` | Placeholder con thumbnail SVG que abre SofIA al hacer clic |
| 7 | **Nosotros** | `#nosotros` | Texto con glitch en "SofIA" + card de características |
| 8 | **Equipo** | `#equipo` | 4 cards con avatares SVG y tilt 3D |
| 9 | **FAQ** | `#faq` | Acordeón con preguntas frecuentes |
| 10 | **Contacto** | `#contacto` | Layout 2 columnas: info de contacto + formulario AJAX con validación |

---

## Efectos visuales y animaciones

### Three.js

- **Esfera hero**: partículas con líneas de conexión interactivas, rotación continua, reacciona al mouse

### CSS + Canvas

- **Typewriter** en H1 — rota entre 6 frases de servicio con cursor parpadeante
- **Glitch** en "SofIA" — efecto cyberpunk con desplazamiento cromático cada 4.5 segundos
- **Flip 3D** en cards de servicios — hover voltea 180° con perspectiva CSS; reverso muestra descripción y CTA
- **Tilt 3D** en cards de casos/equipo/productos — perspectiva dinámica siguiendo el cursor
- **Cursor magnético** en botones CTA — los botones atraen al cursor con spring suave
- **Parallax** en hero — el contenido sube al 30% de la velocidad de scroll
- **Draw-on-scroll** — línea SVG con gradiente azul→violeta que se dibuja al llegar a "Cómo funciona"
- **Partículas de fondo** — 85 puntos conectados en canvas fijo, se repelen del mouse (opacidad 35%)
- **Contadores animados** — números suben con easing cúbico al entrar en viewport

### UI/UX

- **Cookie consent** — banner fijo con blur que persiste en `localStorage`
- **Volver arriba** — botón flotante que aparece tras 400px de scroll
- **Scroll suave** — todas las anclas internas con `scrollIntoView`
- **Navbar scroll** — fondo sólido al bajar 40px
- **IntersectionObserver** — card reveal staggered en todas las secciones

### Imágenes SVG generadas

- `avatar-ceo.svg`, `avatar-tech.svg`, `avatar-ai.svg`, `avatar-ux.svg` — avatares del equipo (300×300)
- `case-finanzas.svg`, `case-retail.svg`, `case-manufactura.svg` — ilustraciones de casos (480×220)
- `demo-thumbnail.svg` — thumbnail del demo con mockup de chat SofIA (1280×720)
- `og-devioz.svg` — imagen Open Graph (1200×630)

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
| `MAIL_FROM` | Remitente de los emails del formulario | `no-reply@devioz.com` |
| `MAIL_REPLY_TO` | Destino de los mensajes del formulario | `contacto@devioz.com` |
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
POST   /api/contact           Formulario de contacto (máx. 10/min por IP)
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

#### Payload del formulario de contacto

```json
POST /api/contact
{
  "nombre":   "Juan Pérez",
  "empresa":  "Empresa SAC",       // opcional
  "email":    "juan@empresa.pe",
  "telefono": "999999999",         // opcional
  "servicio": "Software Factory",  // opcional
  "mensaje":  "Necesito..."
}
```

Respuestas: `{"success": true}` o `{"success": false, "message": "..."}`.

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

### Formulario de contacto

- Validación server-side: nombre, email (`filter_var FILTER_VALIDATE_EMAIL`), mensaje requeridos.
- Rate limiting independiente: 10 envíos/min por IP (grupo `contact` en tabla `rate_limits`).
- Usa `php mail()` configurable vía `MAIL_FROM` y `MAIL_REPLY_TO`.

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

8. Activar Google Analytics: descomentar el snippet en `<head>` de `home.php` y reemplazar `G-XXXXXXXXXX`.

9. Configurar el servidor para no bufferizar el endpoint SSE.

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
│   │   ├── css/style.css          Estilos de la web (Bootstrap 5 + custom tokens)
│   │   ├── js/
│   │   │   ├── three-hero.js      Esfera 3D interactiva del hero (Three.js)
│   │   │   └── main.js            Todas las animaciones: typewriter, flip, partículas,
│   │   │                          parallax, cursor magnético, draw-on-scroll, contadores,
│   │   │                          cookie consent, back-to-top, formulario AJAX
│   │   └── images/
│   │       ├── avatar-*.svg       Avatares SVG del equipo (ceo, tech, ai, ux)
│   │       ├── case-*.svg         Ilustraciones SVG de casos de éxito
│   │       ├── demo-thumbnail.svg Thumbnail del demo (1280×720)
│   │       ├── og-devioz.svg      Imagen Open Graph (1200×630)
│   │       ├── logo.svg           Logo Devioz
│   │       └── favicon.svg        Favicon
│   ├── widget/
│   │   └── sofia-widget.js        Bundle IIFE del widget SofIA (React 18)
│   └── admin/                     Bundle SPA del panel de administración
│
├── src/
│   ├── Controllers/
│   │   ├── HomeController.php     Renderiza la web corporativa
│   │   ├── ChatController.php     Agente SofIA (SSE + bucle de herramientas)
│   │   ├── ContactController.php  Formulario de contacto (valida + envía email)
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
│   │   ├── EmailService.php       Envío de emails vía php mail()
│   │   ├── CulqiPaymentService.php  createCharge(), getCharge(), verifyWebhookSignature()
│   │   └── TokenService.php       Tokens HMAC para el panel admin (TTL 8h)
│   │
│   ├── Middleware/
│   │   ├── CorsMiddleware.php          Preflight OPTIONS + cabeceras CORS
│   │   ├── RateLimitingMiddleware.php  Ventana de 60s por IP en MySQL
│   │   └── AdminAuthMiddleware.php     Valida Bearer token del panel
│   │
│   ├── Views/home.php             Web corporativa completa (11 secciones)
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
