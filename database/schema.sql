-- ============================================================
-- DEVIOZ WEB + AGENTE SOFIA - Esquema MySQL 8.0
-- Moneda: Soles peruanos (PEN). Charset: utf8mb4.
-- Importar con:  mysql -u root -p < database/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS devioz_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE devioz_db;

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Limpieza (orden inverso por claves foraneas)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS webhook_events;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin_users;

-- ------------------------------------------------------------
-- Categorias de producto
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    icon        VARCHAR(60)  NULL COMMENT 'Clase de Bootstrap Icons para la web',
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Productos (price_offer NULL => producto "a cotizar")
-- ------------------------------------------------------------
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(160) NOT NULL,
    slug        VARCHAR(180) NOT NULL,
    description TEXT NULL,
    tier        ENUM('Básico', 'Pro', 'Premium', 'Enterprise') NOT NULL DEFAULT 'Básico',
    price_offer DECIMAL(10, 2) NULL COMMENT 'Precio en PEN. NULL = a cotizar',
    currency    CHAR(3) NOT NULL DEFAULT 'PEN',
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_active (active),
    KEY idx_products_price (price_offer),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Conversaciones del agente SofIA
-- ------------------------------------------------------------
CREATE TABLE conversations (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversations_session (session_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE messages (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    role            ENUM('user', 'assistant', 'system', 'tool') NOT NULL,
    content         TEXT NOT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_messages_conversation (conversation_id),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id)
        REFERENCES conversations (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Carritos por sesion del widget
-- ------------------------------------------------------------
CREATE TABLE carts (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    status     ENUM('open', 'checked_out', 'abandoned') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_carts_session_status (session_id, status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id    BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL COMMENT 'Precio congelado al agregar (PEN)',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_items (cart_id, product_id),
    KEY idx_cart_items_product (product_id),
    CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id)
        REFERENCES carts (id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Ordenes y pagos (transacciones ACID con Culqi)
-- ------------------------------------------------------------
CREATE TABLE orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20) NOT NULL COMMENT 'Codigo publico ej: DVZ-1A2B3C4D',
    session_id      VARCHAR(64) NOT NULL,
    customer_email  VARCHAR(190) NULL,
    customer_name   VARCHAR(190) NULL,
    total           DECIMAL(10, 2) NOT NULL,
    currency        CHAR(3) NOT NULL DEFAULT 'PEN',
    status          ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    culqi_charge_id VARCHAR(64) NULL,
    paid_at         TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_code (code),
    KEY idx_orders_status (status),
    KEY idx_orders_session (session_id),
    KEY idx_orders_charge (culqi_charge_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     BIGINT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL COMMENT 'Snapshot del nombre al comprar',
    unit_price   DECIMAL(10, 2) NOT NULL,
    quantity     INT UNSIGNED NOT NULL DEFAULT 1,
    KEY idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Eventos de webhooks (auditoria de Culqi)
-- ------------------------------------------------------------
CREATE TABLE webhook_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider        VARCHAR(30) NOT NULL DEFAULT 'culqi',
    event_type      VARCHAR(80) NULL,
    external_id     VARCHAR(80) NULL COMMENT 'ID del objeto en Culqi (chr_...)',
    payload         JSON NULL,
    signature_valid TINYINT(1) NOT NULL DEFAULT 0,
    processed       TINYINT(1) NOT NULL DEFAULT 0,
    notes           VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_webhook_external (external_id),
    KEY idx_webhook_type (event_type)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Usuarios del panel de administracion
-- ------------------------------------------------------------
CREATE TABLE admin_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_email (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Rate limiting persistente (ventanas de 60 segundos)
-- ------------------------------------------------------------
CREATE TABLE rate_limits (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rl_key       VARCHAR(140) NOT NULL COMMENT 'ip|grupo-de-ruta',
    window_start DATETIME NOT NULL,
    hits         INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_rate_limits_key (rl_key)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================
-- SEED: Categorias
-- ============================================================
INSERT INTO categories (id, name, slug, description, icon) VALUES
(1, 'Diseño Gráfico', 'diseno-grafico', 'Flyers y piezas gráficas profesionales listas para campañas.', 'bi-palette'),
(2, 'Spots Publicitarios', 'spots-publicitarios', 'Spots audiovisuales de alto impacto para tu marca.', 'bi-camera-reels'),
(3, 'Business Intelligence', 'business-intelligence', 'Dashboards interactivos para decisiones basadas en datos.', 'bi-bar-chart-line'),
(4, 'Inteligencia Artificial', 'inteligencia-artificial', 'Agentes y sistemas de automatización con IA generativa.', 'bi-robot'),
(5, 'Desarrollo Web', 'desarrollo-web', 'Plantillas web corporativas y plataformas listas para producción.', 'bi-window-stack');

-- ============================================================
-- SEED: Productos (precios exactos en Soles - PEN)
-- price_offer NULL = producto a cotizar
-- ============================================================

-- --- Diseño Gráfico ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(1, 'Flyer-Gym', 'flyer-gym', 'Flyer profesional para gimnasios: composición de alto impacto, tipografías deportivas y llamada a la acción optimizada para captar socios. Entrega en formatos digital e imprimible.', 'Pro', 2.99, 'PEN', 1),
(1, 'Flyer-Cinefotografia', 'flyer-cinefotografia', 'Flyer para estudios de cine y fotografía: estética cinematográfica, paleta sobria y estructura pensada para promocionar sesiones y servicios audiovisuales.', 'Básico', 2.99, 'PEN', 1),
(1, 'Flyer-Medicina', 'flyer-medicina', 'Flyer para clínicas y consultorios: diseño limpio y confiable, jerarquía clara de servicios médicos y datos de contacto destacados.', 'Pro', 3.99, 'PEN', 1),
(1, 'Flyer-Cantante', 'flyer-cantante', 'Flyer promocional para cantantes y artistas: diseño vibrante para conciertos, lanzamientos y eventos en vivo.', 'Básico', 3.99, 'PEN', 1),
(1, 'Flyer-DJ', 'flyer-dj', 'Flyer premium para DJs y eventos electrónicos: estilo neón, composición nocturna y espacio para line-up y venues.', 'Premium', 3.99, 'PEN', 1),
(1, 'Flyer-Restobar', 'flyer-restobar', 'Flyer premium para restobares: fotografía gastronómica protagonista, promociones destacadas y diseño orientado a reservas.', 'Premium', 4.99, 'PEN', 1);

-- --- Spots Publicitarios ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(2, 'Spots-Gimnasio', 'spots-gimnasio', 'Spot publicitario para gimnasios: edición dinámica, motion graphics y musicalización energética lista para redes sociales.', 'Pro', 5.99, 'PEN', 1),
(2, 'Spots-Cinetografia', 'spots-cinetografia', 'Spot para productoras de cine y fotografía: narrativa visual cinematográfica con corrección de color profesional.', 'Básico', 10.99, 'PEN', 1),
(2, 'Spots-Medicina', 'spots-medicina', 'Spot para servicios de salud: mensaje claro y empático, gráficos informativos y locución profesional.', 'Pro', 10.99, 'PEN', 1),
(2, 'Spots-DJ', 'spots-dj', 'Spot premium para DJs: sincronización beat-to-cut, efectos visuales de club y formato vertical y horizontal.', 'Premium', 12.99, 'PEN', 1),
(2, 'Spots-Restobar-Maicelo', 'spots-restobar-maicelo', 'Spot premium para el restobar Maicelo: producción audiovisual gastronómica de alto nivel con identidad de marca integrada.', 'Premium', 12.99, 'PEN', 1),
(2, 'Spots-Cantante', 'spots-cantante', 'Spot premium para cantantes: pieza audiovisual para lanzamientos musicales con estética de videoclip.', 'Premium', 15.99, 'PEN', 1);

-- --- Business Intelligence ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(3, 'Dashboard de Rendimiento del Cantante 2025', 'dashboard-rendimiento-cantante-2025', 'Dashboard enterprise que consolida streams, ingresos por plataforma, audiencia y engagement del artista en un solo panel interactivo 2025.', 'Enterprise', 14.99, 'PEN', 1),
(3, 'Dashboard de Producción Audiovisual 2025', 'dashboard-produccion-audiovisual-2025', 'Dashboard para productoras: control de proyectos, presupuestos, tiempos de rodaje y rentabilidad por cliente durante 2025.', 'Pro', 15.99, 'PEN', 1),
(3, 'Dashboard Anual para DJs con IA', 'dashboard-anual-djs-ia', 'Dashboard anual para DJs potenciado con IA: análisis de bookings, ingresos por evento y predicción de temporadas de alta demanda.', 'Pro', 15.99, 'PEN', 1);

-- --- Inteligencia Artificial ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(4, 'Agente Cantante', 'agente-cantante', 'Agente de IA para artistas musicales: responde fans, gestiona agenda de presentaciones y promociona lanzamientos de forma autónoma.', 'Pro', 6.99, 'PEN', 1),
(4, 'Agente de Cinematografia', 'agente-cinematografia', 'Agente de IA para productoras audiovisuales: cotiza proyectos, agenda sesiones y asesora a clientes 24/7.', 'Pro', 6.99, 'PEN', 1),
(4, 'Agencia Sofia', 'agencia-sofia', 'Agente conversacional Sofia para agencias: vendedor virtual con catálogo, carrito y cierre de ventas integrado al chat.', 'Pro', 6.99, 'PEN', 1),
(4, 'Sistema de Crecimiento y Automatización con IA', 'sistema-crecimiento-automatizacion-ia', 'Sistema enterprise de crecimiento: funnels automatizados, scoring de leads con IA y orquestación de campañas multicanal.', 'Enterprise', 18.99, 'PEN', 1),
(4, 'Sistema Inteligente de Automatización con IA', 'sistema-inteligente-automatizacion-ia', 'Sistema de automatización con IA para operaciones repetitivas: clasificación de correos, respuestas automáticas y flujos de aprobación.', 'Básico', 19.88, 'PEN', 1),
(4, 'Sistema Inteligente de Análisis y Automatización', 'sistema-inteligente-analisis-automatizacion', 'Plataforma enterprise que combina análisis de datos e IA para automatizar reportes, alertas y decisiones operativas.', 'Enterprise', 19.99, 'PEN', 1);

-- --- Desarrollo Web ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(5, 'Vision AI Platform', 'vision-ai-platform', 'Plataforma web con visión computarizada: detección de objetos e informes en tiempo real, lista para integrar a tu operación.', 'Básico', 16.00, 'PEN', 1),
(5, 'Plantilla Rent-a-Car Corporativa', 'plantilla-rent-a-car-corporativa', 'Plantilla corporativa para alquiler de autos: catálogo de flota, reservas en línea y diseño responsive profesional.', 'Pro', 16.00, 'PEN', 1),
(5, 'Plantillas-Eventos', 'plantillas-eventos', 'Plantilla web para productoras de eventos: agenda, venta de entradas, galería y countdown integrados.', 'Pro', 17.99, 'PEN', 1),
(5, 'Plantilla-Barbershop', 'plantilla-barbershop', 'Plantilla premium para barberías: reserva de citas, catálogo de servicios y estética urbana moderna.', 'Premium', 17.99, 'PEN', 1),
(5, 'Plantilla-Musica', 'plantilla-musica', 'Plantilla premium para músicos y bandas: discografía, reproductor integrado, fechas de gira y prensa.', 'Premium', 18.99, 'PEN', 1),
(5, 'Plantilla-DJ', 'plantilla-dj', 'Plantilla premium para DJs: mixes embebidos, booking directo, galería de eventos y diseño dark de club.', 'Premium', 18.99, 'PEN', 1),
(5, 'Mesa Criolla Premium', 'mesa-criolla-premium', 'Sitio web premium para restaurantes de comida criolla: carta digital, reservas y branding gastronómico peruano.', 'Premium', 18.99, 'PEN', 1),
(5, 'Plantilla-Fotografía', 'plantilla-fotografia', 'Plantilla para fotógrafos profesionales: portafolio fullscreen, galerías por categoría y formulario de cotización.', 'Pro', 18.99, 'PEN', 1),
(5, 'Academia Digital Pro', 'academia-digital-pro', 'Plataforma e-learning lista para academias: cursos, matrícula en línea, panel del alumno y pasarela de pagos.', 'Pro', 19.99, 'PEN', 1),
(5, 'Plantilla-Negocios Gastronómico', 'plantilla-negocios-gastronomico', 'Plantilla premium para negocios gastronómicos: carta interactiva, pedidos por WhatsApp y SEO local optimizado.', 'Premium', 19.99, 'PEN', 1);

-- --- Productos a cotizar (price_offer = NULL) ---
INSERT INTO products (category_id, name, slug, description, tier, price_offer, currency, active) VALUES
(5, 'Plantilla Gym && Sport', 'plantilla-gym-sport', 'Plantilla web para gimnasios y centros deportivos: planes de membresía, horarios de clases y perfiles de entrenadores. Proyecto personalizado: solicita tu cotización.', 'Básico', NULL, 'PEN', 1),
(5, 'MaiceloRestobar', 'maicelorestobar', 'Sitio web premium a medida para MaiceloRestobar: experiencia gastronómica digital completa con reservas e identidad de marca. Proyecto personalizado: solicita tu cotización.', 'Premium', NULL, 'PEN', 1);

-- ============================================================
-- SEED: Usuario administrador inicial
-- Credenciales: admin@devioz.pe / admin123
-- IMPORTANTE: cambiar la contraseña en produccion
-- (o crear otro usuario con: composer create-admin)
-- ============================================================
INSERT INTO admin_users (name, email, password_hash) VALUES
('Administrador Devioz', 'admin@devioz.pe', '$2a$10$JxFZ4nC47yQuFVL03S8sRuujIn3FYNNTV9PlH3qY6J0DtGBiI96ti');
