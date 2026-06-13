<?php
/**
 * Pagina corporativa de Devioz (renderizada server-side para SEO).
 * Variables disponibles: $categories (Collection), $whatsapp (string), $appUrl (string)
 */
$e = fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO B2B -->
    <title>Devioz | Consultora Tecnológica Peruana - Software Factory, IA, Cloud y Ciberseguridad</title>
    <meta name="description" content="Devioz es la consultora tecnológica peruana para medianas y grandes empresas: Software Factory, IoT, Ciberseguridad, Cloud, RPA, Business Intelligence, Plantillas Web y Agentes de IA. Cotiza con SofIA, nuestra asesora con IA.">
    <meta name="keywords" content="consultora tecnológica Perú, software factory, agentes IA, ciberseguridad empresarial, cloud, RPA, business intelligence, plantillas web corporativas">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $e($appUrl) ?>/">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Devioz">
    <meta property="og:title" content="Devioz | Soluciones TI Empresariales con IA">
    <meta property="og:description" content="Software Factory, IoT, Ciberseguridad, Cloud, RPA y Agentes de IA para empresas. Conversa con SofIA y cotiza en minutos.">
    <meta property="og:url" content="<?= $e($appUrl) ?>/">
    <meta property="og:image" content="<?= $e($appUrl) ?>/assets/images/og-devioz.svg">
    <meta property="og:locale" content="es_PE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Devioz | Soluciones TI Empresariales con IA">
    <meta name="twitter:description" content="Consultora tecnológica peruana B2B. Conversa con SofIA, nuestra agente de ventas con IA.">

    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/style.css">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Devioz",
        "url": "<?= $e($appUrl) ?>",
        "description": "Consultora tecnológica peruana especializada en soluciones TI empresariales",
        "areaServed": "PE",
        "knowsAbout": ["Software Factory", "IoT", "Ciberseguridad", "Cloud", "RPA", "Inteligencia Artificial"]
    }
    </script>
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg navbar-dark dvz-navbar fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/assets/images/logo.svg" alt="Devioz" width="34" height="34">
            <span class="fw-bold">DEVIOZ</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-label="Menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
                <li class="nav-item"><a class="nav-link" href="#productos">Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
                <li class="nav-item ms-lg-2">
                    <button class="btn btn-primary dvz-btn-glow px-4" data-sofia-open>
                        <i class="bi bi-stars me-1"></i> Hablar con SofIA
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===================== HERO (oscuro + 3D interactivo) ===================== -->
<header class="dvz-hero" id="inicio">
    <canvas id="hero-canvas" aria-hidden="true"></canvas>
    <div class="dvz-hero-fallback" aria-hidden="true"></div>
    <div class="container position-relative dvz-hero-content">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="badge dvz-badge mb-3"><i class="bi bi-cpu me-1"></i> Consultora Tecnológica Peruana · B2B</span>
                <h1 class="display-3 fw-bold text-white mb-3">
                    Transformamos tu empresa con <span class="dvz-gradient-text">tecnología e IA</span>
                </h1>
                <p class="lead dvz-hero-sub mx-auto mb-4">
                    Software Factory, IoT, Ciberseguridad, Cloud, RPA, Business Intelligence y Agentes de IA
                    para medianas y grandes empresas e instituciones.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
                    <button class="btn btn-primary btn-lg dvz-btn-glow px-4" data-sofia-open>
                        <i class="bi bi-chat-dots me-2"></i>Cotizar con SofIA
                    </button>
                    <a href="#servicios" class="btn btn-outline-light btn-lg px-4">Ver servicios</a>
                </div>
                <div class="row g-3 justify-content-center dvz-hero-stats">
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong>+120</strong><span>Proyectos entregados</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong>9</strong><span>Líneas de servicio</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong>24/7</strong><span>Atención con IA</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong>100%</strong><span>Enfoque B2B</span></div></div>
                </div>
            </div>
        </div>
    </div>
    <a href="#servicios" class="dvz-scroll-hint" aria-label="Bajar a servicios"><i class="bi bi-chevron-double-down"></i></a>
</header>

<!-- ===================== SERVICIOS (claro) ===================== -->
<section class="dvz-section" id="servicios">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Nuestras especialidades</span>
            <h2 class="fw-bold display-6">Soluciones TI de extremo a extremo</h2>
            <p class="text-secondary mx-auto" style="max-width: 640px;">
                Un solo aliado tecnológico para todo el ciclo digital de tu organización.
            </p>
        </div>
        <div class="row g-4">
            <?php
            $services = [
                ['bi-code-square', 'Software Factory', 'Desarrollo de software a medida con metodologías ágiles, QA y soporte evolutivo.'],
                ['bi-router', 'IoT', 'Sensores, telemetría y plataformas de monitoreo en tiempo real para tu operación.'],
                ['bi-shield-lock', 'Ciberseguridad', 'Ethical hacking, hardening, SOC y cumplimiento normativo para proteger tu negocio.'],
                ['bi-cloud-arrow-up', 'Cloud', 'Migración, arquitectura e infraestructura administrada en AWS, Azure y GCP.'],
                ['bi-robot', 'RPA', 'Automatización robótica de procesos para reducir costos operativos y errores.'],
                ['bi-palette', 'Diseño UX/UI', 'Interfaces centradas en el usuario que convierten visitantes en clientes.'],
                ['bi-camera-reels', 'Multimedia', 'Spots publicitarios, producción audiovisual y contenido de alto impacto.'],
                ['bi-megaphone', 'Marketing Digital', 'Estrategias data-driven: SEO, performance y growth para empresas B2B.'],
                ['bi-stars', 'Plantillas Web & Agentes IA', 'Catálogo listo para producción: webs corporativas y agentes conversacionales.'],
            ];
            foreach ($services as [$icon, $title, $desc]): ?>
            <div class="col-md-6 col-lg-4">
                <div class="dvz-card h-100">
                    <div class="dvz-card-icon"><i class="bi <?= $e($icon) ?>"></i></div>
                    <h3 class="h5 fw-bold mt-3"><?= $e($title) ?></h3>
                    <p class="text-secondary mb-0"><?= $e($desc) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== PRODUCTOS (claro, catálogo desde BD) ===================== -->
<?php if ($categories->isNotEmpty()): ?>
<section class="dvz-section bg-body-tertiary" id="productos">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Catálogo digital</span>
            <h2 class="fw-bold display-6">Productos listos para tu empresa</h2>
            <p class="text-secondary mx-auto" style="max-width: 640px;">
                Precios en Soles (PEN). Compra directamente en el chat con SofIA: pago seguro con tarjeta o Yape vía Culqi.
            </p>
        </div>

        <ul class="nav nav-pills dvz-pills justify-content-center flex-wrap gap-2 mb-4" role="tablist">
            <?php foreach ($categories as $i => $category): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="pill"
                        data-bs-target="#cat-<?= (int) $category->id ?>" type="button" role="tab">
                    <i class="bi <?= $e($category->icon ?? 'bi-box') ?> me-1"></i><?= $e($category->name) ?>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php foreach ($categories as $i => $category): ?>
            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="cat-<?= (int) $category->id ?>" role="tabpanel">
                <div class="row g-4">
                    <?php foreach ($category->products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="dvz-product-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge dvz-tier dvz-tier-<?= $e(strtolower(str_replace(['á'], ['a'], $product->tier))) ?>"><?= $e($product->tier) ?></span>
                                <?php if ($product->isQuoteOnly()): ?>
                                    <span class="dvz-price text-warning-emphasis">A cotizar</span>
                                <?php else: ?>
                                    <span class="dvz-price">S/ <?= number_format((float) $product->price_offer, 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="h6 fw-bold mb-2"><?= $e($product->name) ?></h3>
                            <p class="text-secondary small flex-grow-1"><?= $e($product->description) ?></p>
                            <?php if ($product->isQuoteOnly()): ?>
                                <button class="btn btn-outline-primary w-100 mt-auto" data-sofia-open
                                        data-sofia-message="Hola, quiero cotizar el producto <?= $e($product->name) ?>">
                                    <i class="bi bi-whatsapp me-1"></i>Solicitar cotización
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary w-100 mt-auto" data-sofia-open
                                        data-sofia-message="Hola SofIA, quiero comprar <?= $e($product->name) ?>">
                                    <i class="bi bi-cart-plus me-1"></i>Comprar con SofIA
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===================== NOSOTROS (oscuro) ===================== -->
<section class="dvz-section dvz-section-dark" id="nosotros">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="dvz-eyebrow">¿Por qué Devioz?</span>
                <h2 class="fw-bold display-6 text-white mb-4">Tecnología peruana con estándares globales</h2>
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex gap-3">
                        <div class="dvz-card-icon flex-shrink-0"><i class="bi bi-diagram-3"></i></div>
                        <div>
                            <h3 class="h6 fw-bold text-white">Arquitectura de nivel empresarial</h3>
                            <p class="dvz-text-muted mb-0">Transacciones ACID, integraciones seguras y escalabilidad desde el día uno.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="dvz-card-icon flex-shrink-0"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <h3 class="h6 fw-bold text-white">Velocidad de entrega</h3>
                            <p class="dvz-text-muted mb-0">MVPs robustos en semanas, no meses, con calidad de producción.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="dvz-card-icon flex-shrink-0"><i class="bi bi-headset"></i></div>
                        <div>
                            <h3 class="h6 fw-bold text-white">Atención inteligente 24/7</h3>
                            <p class="dvz-text-muted mb-0">SofIA, nuestra agente con IA, cotiza, vende y da soporte en cualquier momento.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="dvz-glass p-4 p-lg-5">
                    <h3 class="h5 text-white fw-bold mb-4"><i class="bi bi-stars text-primary me-2"></i>Conoce a SofIA</h3>
                    <p class="dvz-text-muted">Nuestra asesora virtual transaccional impulsada por IA generativa:</p>
                    <ul class="dvz-check-list">
                        <li>Consulta el catálogo con precios reales en Soles</li>
                        <li>Arma tu carrito de compras conversando</li>
                        <li>Procesa pagos seguros con tarjeta o Yape (Culqi)</li>
                        <li>Te deriva a un asesor humano cuando lo necesitas</li>
                    </ul>
                    <button class="btn btn-primary dvz-btn-glow w-100 mt-3" data-sofia-open>
                        <i class="bi bi-chat-heart me-2"></i>Probar SofIA ahora
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CONTACTO / CTA (claro) ===================== -->
<section class="dvz-section" id="contacto">
    <div class="container">
        <div class="dvz-cta text-center p-5">
            <h2 class="fw-bold display-6 mb-3">¿Listo para digitalizar tu empresa?</h2>
            <p class="text-secondary mx-auto mb-4" style="max-width: 560px;">
                Conversa con SofIA para comprar productos del catálogo o escríbenos por WhatsApp
                para proyectos a medida y consultoría.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <button class="btn btn-primary btn-lg dvz-btn-glow px-4" data-sofia-open>
                    <i class="bi bi-chat-dots me-2"></i>Chatear con SofIA
                </button>
                <a class="btn btn-success btn-lg px-4" target="_blank" rel="noopener"
                   href="https://wa.me/<?= $e($whatsapp) ?>?text=<?= rawurlencode('Hola Devioz, quiero información sobre sus servicios.') ?>">
                    <i class="bi bi-whatsapp me-2"></i>WhatsApp directo
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="dvz-footer py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="/assets/images/logo.svg" alt="Devioz" width="30" height="30">
                    <span class="fw-bold text-white">DEVIOZ</span>
                </div>
                <p class="dvz-text-muted">Consultora tecnológica peruana especializada en soluciones TI para medianas y grandes empresas e instituciones.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h4 class="h6 text-white fw-bold mb-3">Servicios</h4>
                <ul class="dvz-footer-links">
                    <li><a href="#servicios">Software Factory</a></li>
                    <li><a href="#servicios">Ciberseguridad</a></li>
                    <li><a href="#servicios">Cloud & RPA</a></li>
                    <li><a href="#servicios">Agentes de IA</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h4 class="h6 text-white fw-bold mb-3">Empresa</h4>
                <ul class="dvz-footer-links">
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#productos">Catálogo</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h4 class="h6 text-white fw-bold mb-3">Contacto</h4>
                <ul class="dvz-footer-links">
                    <li><i class="bi bi-whatsapp me-2"></i><a href="https://wa.me/<?= $e($whatsapp) ?>" target="_blank" rel="noopener">+<?= $e($whatsapp) ?></a></li>
                    <li><i class="bi bi-geo-alt me-2"></i>Lima, Perú</li>
                    <li><i class="bi bi-clock me-2"></i>SofIA disponible 24/7</li>
                </ul>
            </div>
        </div>
        <hr class="dvz-divider my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="dvz-text-muted">© <?= date('Y') ?> Devioz. Todos los derechos reservados.</small>
            <small class="dvz-text-muted">Pagos seguros procesados por Culqi · Tarjetas y Yape en Soles (PEN)</small>
        </div>
    </div>
</footer>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.158.0/build/three.min.js" defer></script>
<script src="/assets/js/three-hero.js" defer></script>
<script src="/assets/js/main.js" defer></script>

<!-- Widget transaccional SofIA -->
<script>
    window.SOFIA_CONFIG = {
        apiBase: '',
        whatsapp: '<?= $e($whatsapp) ?>'
    };
</script>
<script src="/widget/sofia-widget.js" defer></script>

</body>
</html>
