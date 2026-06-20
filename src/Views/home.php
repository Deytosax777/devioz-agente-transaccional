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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- #10 Google Analytics — reemplaza G-XXXXXXXXXX con tu Measurement ID -->
    <!-- <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script> -->

    <!-- Loader: CSS inline para que aparezca desde el primer byte -->
    <style>
    /* ── Contenedor principal ── */
    #dvz-loader {
        position: fixed; inset: 0; z-index: 99999;
        background: #080C18;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        overflow: hidden;
        transition: opacity .65s cubic-bezier(.4,0,.2,1),
                    visibility .65s cubic-bezier(.4,0,.2,1);
    }
    #dvz-loader.dvz-loader-out { opacity: 0; visibility: hidden; }

    /* ── Fondo: glows animados ── */
    #dvz-loader::before,
    #dvz-loader::after {
        content: '';
        position: absolute; border-radius: 50%;
        pointer-events: none; filter: blur(80px);
    }
    #dvz-loader::before {
        width: 600px; height: 600px;
        top: -200px; left: -150px;
        background: radial-gradient(circle, rgba(37,99,235,.18) 0%, transparent 70%);
        animation: dvz-glow-1 5s ease-in-out infinite alternate;
    }
    #dvz-loader::after {
        width: 500px; height: 500px;
        bottom: -180px; right: -120px;
        background: radial-gradient(circle, rgba(124,58,237,.16) 0%, transparent 70%);
        animation: dvz-glow-2 6s ease-in-out infinite alternate;
    }
    @keyframes dvz-glow-1 { to { transform: translate(60px, 80px) scale(1.15); } }
    @keyframes dvz-glow-2 { to { transform: translate(-60px,-80px) scale(1.2);  } }

    /* ── Barra de progreso superior (shooting light) ── */
    .dvz-ldr-bar {
        position: absolute; top: 0; left: 0; right: 0;
        height: 2px; overflow: hidden;
        background: rgba(255,255,255,.04);
    }
    .dvz-ldr-bar::after {
        content: '';
        position: absolute; top: 0; left: -60%;
        width: 60%; height: 100%;
        background: linear-gradient(90deg, transparent, #2563EB, #7C3AED, #34D399, transparent);
        animation: dvz-bar-slide 2s ease-in-out infinite;
    }
    @keyframes dvz-bar-slide {
        0%   { left: -60%; }
        100% { left: 110%; }
    }

    /* ── Wrapper del orbital ── */
    .dvz-ldr-orbit {
        position: relative;
        width: 180px; height: 180px;
        margin-bottom: 2rem;
        flex-shrink: 0;
    }

    /* ── SVG de arcos ── */
    .dvz-ldr-ring {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        overflow: visible;
    }

    /* Arco exterior: clockwise, azul → morado */
    .dvz-arc-outer {
        transform-origin: 90px 90px;
        animation: dvz-cw 1.8s linear infinite;
    }
    /* Arco medio: counter-clockwise, morado → teal */
    .dvz-arc-mid {
        transform-origin: 90px 90px;
        animation: dvz-ccw 2.6s linear infinite;
    }
    /* Arco interior: clockwise más lento, teal → azul */
    .dvz-arc-inner {
        transform-origin: 90px 90px;
        animation: dvz-cw 3.4s linear infinite;
    }

    @keyframes dvz-cw  { to { transform: rotate( 360deg); } }
    @keyframes dvz-ccw { to { transform: rotate(-360deg); } }

    /* ── Logo centrado dentro del orbital ── */
    .dvz-ldr-logo {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        z-index: 2;
    }
    .dvz-ldr-logo img {
        animation: dvz-logo-pulse 3s ease-in-out infinite;
    }
    @keyframes dvz-logo-pulse {
        0%,100% {
            filter: drop-shadow(0 0 12px rgba(37,99,235,.55))
                    drop-shadow(0 0  4px rgba(37,99,235,.3));
            transform: scale(1);
        }
        50% {
            filter: drop-shadow(0 0 24px rgba(124,58,237,.8))
                    drop-shadow(0 0  8px rgba(124,58,237,.4));
            transform: scale(1.07);
        }
    }

    /* ── Punto central brillante detrás del logo ── */
    .dvz-ldr-core {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        z-index: 1;
    }
    .dvz-ldr-core::before {
        content: '';
        width: 90px; height: 90px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(37,99,235,.18) 0%, transparent 70%);
        animation: dvz-core-pulse 3s ease-in-out infinite;
    }
    @keyframes dvz-core-pulse {
        0%,100% { transform: scale(1);   opacity: .7; }
        50%      { transform: scale(1.3); opacity: 1;  }
    }

    /* ── Wordmark ── */
    .dvz-ldr-name {
        font-family: 'Geist','Inter',system-ui,sans-serif;
        font-size: 1.65rem; font-weight: 900;
        letter-spacing: .22em;
        background: linear-gradient(135deg, #fff 0%, rgba(226,232,240,.75) 100%);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: dvz-text-in .8s ease both;
    }
    .dvz-ldr-sub {
        font-family: 'Inter',system-ui,sans-serif;
        font-size: .68rem; font-weight: 600;
        letter-spacing: .28em; text-transform: uppercase;
        color: rgba(226,232,240,.3);
        margin-top: .45rem;
        animation: dvz-text-in .8s .15s ease both;
    }
    @keyframes dvz-text-in {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0);   }
    }

    /* ── Puntos de carga ── */
    .dvz-ldr-dots {
        display: flex; gap: 7px;
        margin-top: 2.2rem;
        animation: dvz-text-in .8s .3s ease both;
    }
    .dvz-ldr-dots span {
        width: 5px; height: 5px; border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #7C3AED);
        animation: dvz-dot-bounce 1.4s infinite ease-in-out;
    }
    .dvz-ldr-dots span:nth-child(2) { animation-delay: .18s; }
    .dvz-ldr-dots span:nth-child(3) { animation-delay: .36s; }
    @keyframes dvz-dot-bounce {
        0%,80%,100% { transform: scale(.55); opacity: .3; }
        40%          { transform: scale(1.4); opacity: 1;  }
    }

    /* ── Partículas flotantes decorativas ── */
    .dvz-ldr-particles {
        position: absolute; inset: 0;
        pointer-events: none; overflow: hidden;
    }
    .dvz-ldr-p {
        position: absolute;
        width: 2px; height: 2px; border-radius: 50%;
        background: rgba(255,255,255,.55);
        animation: dvz-float linear infinite;
    }
    .dvz-ldr-p:nth-child(1)  { left:12%;  top:70%; animation-duration:6s;   animation-delay:0s;    width:3px; height:3px; }
    .dvz-ldr-p:nth-child(2)  { left:85%;  top:55%; animation-duration:8s;   animation-delay:-2s;   }
    .dvz-ldr-p:nth-child(3)  { left:22%;  top:25%; animation-duration:7s;   animation-delay:-1s;   width:3px; height:3px; opacity:.4; }
    .dvz-ldr-p:nth-child(4)  { left:72%;  top:80%; animation-duration:9s;   animation-delay:-3s;   }
    .dvz-ldr-p:nth-child(5)  { left:45%;  top:15%; animation-duration:5.5s; animation-delay:-.5s;  opacity:.3; }
    .dvz-ldr-p:nth-child(6)  { left:92%;  top:30%; animation-duration:7.5s; animation-delay:-4s;   width:3px; height:3px; }
    .dvz-ldr-p:nth-child(7)  { left:5%;   top:45%; animation-duration:10s;  animation-delay:-1.5s; opacity:.35; }
    .dvz-ldr-p:nth-child(8)  { left:60%;  top:88%; animation-duration:6.5s; animation-delay:-2.5s; }
    @keyframes dvz-float {
        0%   { transform: translateY(0)   scale(1);   opacity: .6; }
        50%  { transform: translateY(-30px) scale(1.3); opacity: .9; }
        100% { transform: translateY(0)   scale(1);   opacity: .6; }
    }
    </style>

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

<!-- ===================== PANTALLA DE CARGA ===================== -->
<div id="dvz-loader" role="status" aria-label="Cargando Devioz">

    <!-- Barra de progreso superior -->
    <div class="dvz-ldr-bar"></div>

    <!-- Partículas flotantes decorativas -->
    <div class="dvz-ldr-particles" aria-hidden="true">
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
        <span class="dvz-ldr-p"></span>
    </div>

    <!-- Orbital: tres arcos girando + logo centrado -->
    <div class="dvz-ldr-orbit" aria-hidden="true">
        <svg class="dvz-ldr-ring" viewBox="0 0 180 180" fill="none">
            <defs>
                <linearGradient id="dvzG1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%"   stop-color="#2563EB"/>
                    <stop offset="100%" stop-color="#7C3AED"/>
                </linearGradient>
                <linearGradient id="dvzG2" x1="100%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%"   stop-color="#7C3AED"/>
                    <stop offset="100%" stop-color="#34D399"/>
                </linearGradient>
                <linearGradient id="dvzG3" x1="0%" y1="100%" x2="100%" y2="0%">
                    <stop offset="0%"   stop-color="#34D399"/>
                    <stop offset="100%" stop-color="#2563EB"/>
                </linearGradient>
            </defs>
            <!-- Track faint -->
            <circle cx="90" cy="90" r="82" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
            <!-- Arco exterior: clockwise, azul → morado -->
            <circle class="dvz-arc-outer" cx="90" cy="90" r="82"
                    stroke="url(#dvzG1)" stroke-width="2.5" stroke-linecap="round"
                    stroke-dasharray="100 416"/>
            <!-- Track faint medio -->
            <circle cx="90" cy="90" r="66" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
            <!-- Arco medio: counter-clockwise, morado → teal -->
            <circle class="dvz-arc-mid" cx="90" cy="90" r="66"
                    stroke="url(#dvzG2)" stroke-width="2" stroke-linecap="round"
                    stroke-dasharray="70 345"/>
            <!-- Track faint interior -->
            <circle cx="90" cy="90" r="50" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
            <!-- Arco interior: clockwise, teal → azul -->
            <circle class="dvz-arc-inner" cx="90" cy="90" r="50"
                    stroke="url(#dvzG3)" stroke-width="1.5" stroke-linecap="round"
                    stroke-dasharray="50 264"/>
        </svg>
        <!-- Glow detrás del logo -->
        <div class="dvz-ldr-core"></div>
        <!-- Logo centrado -->
        <div class="dvz-ldr-logo">
            <img src="/assets/images/logo.svg" alt="" width="62" height="62">
        </div>
    </div>

    <!-- Wordmark -->
    <div class="dvz-ldr-name">DEVIOZ</div>
    <div class="dvz-ldr-sub">Consultora Tecnológica</div>

    <!-- Indicador de carga -->
    <div class="dvz-ldr-dots" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>

</div>

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
                <li class="nav-item"><a class="nav-link" href="#casos">Casos</a></li>
                <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="#equipo">Equipo</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
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
                    Transformamos tu empresa con <span class="dvz-gradient-text dvz-typewriter"
                        data-words='["tecnología e IA","Software Factory","Ciberseguridad","Cloud &amp; DevOps","RPA","Agentes IA"]'>tecnología e IA</span>
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
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong data-count="120" data-prefix="+">+120</strong><span>Proyectos entregados</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong data-count="9">9</strong><span>Líneas de servicio</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong>24/7</strong><span>Atención con IA</span></div></div>
                    <div class="col-6 col-md-3"><div class="dvz-stat"><strong data-count="100" data-suffix="%">100%</strong><span>Enfoque B2B</span></div></div>
                </div>
            </div>
        </div>
    </div>
    <a href="#servicios" class="dvz-scroll-hint" aria-label="Bajar a servicios"><i class="bi bi-chevron-double-down"></i></a>
</header>

<!-- ===================== TICKER DE ESTADÍSTICAS ===================== -->
<?php
$tickerItems = [
    ['+120',    'Proyectos entregados'],
    ['9',       'Líneas de servicio'],
    ['24/7',    'Atención con IA'],
    ['100%',    'Enfoque B2B'],
    ['+5 años', 'Experiencia TI'],
    ['50+',     'Empresas atendidas'],
];
?>
<div class="dvz-ticker-wrap" aria-hidden="true">
    <div class="dvz-ticker-track">
        <?php foreach (array_merge($tickerItems, $tickerItems) as [$num, $label]): ?>
        <div class="dvz-ticker-item">
            <span class="dvz-ticker-num"><?= $e($num) ?></span>
            <span class="dvz-ticker-label"><?= $e($label) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===================== LOGOS DE CLIENTES ===================== -->
<?php
$clientLogos = [
    ['bi-bank',          'Banca y Finanzas'],
    ['bi-bag',           'Retail & E-commerce'],
    ['bi-building',      'Gobierno Digital'],
    ['bi-truck',         'Logística & Supply'],
    ['bi-heart-pulse',   'Salud & Clínicas'],
    ['bi-diagram-3',     'Manufactura'],
    ['bi-mortarboard',   'Educación'],
    ['bi-broadcast',     'Telecomunicaciones'],
    ['bi-gem',           'Minería & Energía'],
    ['bi-graph-up-arrow','Startups & VC'],
];
?>
<section class="dvz-logos-section">
    <div class="container">
        <p class="dvz-logos-label">Sectores que confían en Devioz</p>
    </div>
    <div class="dvz-logos-wrap" aria-hidden="true">
        <div class="dvz-logos-track">
            <?php foreach (array_merge($clientLogos, $clientLogos) as [$icon, $name]): ?>
            <span class="dvz-logo-item">
                <i class="bi <?= $e($icon) ?>"></i><?= $e($name) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
                <div class="dvz-flip-wrap h-100">
                    <div class="dvz-flip-inner">
                        <div class="dvz-card dvz-card-front h-100">
                            <div class="dvz-card-icon"><i class="bi <?= $e($icon) ?>"></i></div>
                            <h3 class="h5 fw-bold mt-3 mb-0"><?= $e($title) ?></h3>
                            <span class="dvz-flip-hint">Pasa el cursor para ver más</span>
                        </div>
                        <div class="dvz-card dvz-card-back h-100">
                            <p class="mb-4"><?= $e($desc) ?></p>
                            <button class="btn btn-primary btn-sm" data-sofia-open
                                    data-sofia-message="Hola SofIA, quiero cotizar el servicio de <?= $e($title) ?>">
                                <i class="bi bi-chat-dots me-1"></i>Cotizar con SofIA
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== #1 STACK TECNOLÓGICO ===================== -->
<section class="dvz-section bg-body-tertiary" id="tecnologias">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Tecnologías</span>
            <h2 class="fw-bold">Herramientas que dominamos</h2>
            <p class="text-secondary mx-auto mt-3" style="max-width:560px;">
                Trabajamos con las plataformas líderes de la industria para garantizar soluciones robustas y escalables.
            </p>
        </div>
        <?php
        $techStack = [
            ['bi-cloud',              'AWS'],
            ['bi-microsoft',          'Azure'],
            ['bi-google',             'Google Cloud'],
            ['bi-filetype-py',        'Python'],
            ['bi-filetype-js',        'Node.js'],
            ['bi-filetype-php',       'PHP'],
            ['bi-code-slash',          'React / Next'],
            ['bi-database',           'PostgreSQL'],
            ['bi-server',             'Docker'],
            ['bi-cpu',                'Kubernetes'],
            ['bi-robot',              'OpenAI API'],
            ['bi-braces',             'Groq / Llama'],
            ['bi-shield-check',       'ISO 27001'],
            ['bi-graph-up',           'Power BI'],
            ['bi-phone',              'React Native'],
            ['bi-shop',               'WordPress'],
        ];
        ?>
        <div class="dvz-tech-grid">
            <?php foreach ($techStack as [$icon, $name]): ?>
            <div class="dvz-tech-item">
                <i class="bi <?= $e($icon) ?>"></i>
                <span><?= $e($name) ?></span>
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

<!-- ===================== #3 CASOS DE ÉXITO ===================== -->
<section class="dvz-section dvz-section-dark" id="casos">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Resultados reales</span>
            <h2 class="fw-bold">Lo que hemos construido</h2>
            <p class="dvz-text-muted mx-auto mt-3" style="max-width:560px;">
                Proyectos entregados con impacto medible para empresas peruanas e instituciones.
            </p>
        </div>
        <div class="dvz-case-grid">

            <div class="dvz-case-card">
                <img src="/assets/images/case-finanzas.svg" alt="" class="dvz-case-img" aria-hidden="true">
                <div class="dvz-case-header">
                    <span class="dvz-case-tag">Finanzas</span>
                    <div class="dvz-case-icon"><i class="bi bi-bank2"></i></div>
                </div>
                <div class="dvz-case-body">
                    <h3>Migración core bancario a la nube</h3>
                    <p>Migramos la infraestructura crítica de una entidad financiera de Lima a AWS con arquitectura de alta disponibilidad y cumplimiento SBS.</p>
                    <div class="dvz-case-metrics">
                        <div class="dvz-case-metric">
                            <strong>40%</strong>
                            <span>Reducción de costos TI</span>
                        </div>
                        <div class="dvz-case-metric">
                            <strong>99.9%</strong>
                            <span>Uptime garantizado</span>
                        </div>
                    </div>
                    <div class="dvz-case-techs">
                        <span class="dvz-case-tech">AWS</span>
                        <span class="dvz-case-tech">Terraform</span>
                        <span class="dvz-case-tech">Docker</span>
                        <span class="dvz-case-tech">ISO 27001</span>
                    </div>
                </div>
            </div>

            <div class="dvz-case-card">
                <img src="/assets/images/case-retail.svg" alt="" class="dvz-case-img" aria-hidden="true">
                <div class="dvz-case-header">
                    <span class="dvz-case-tag">Retail</span>
                    <div class="dvz-case-icon"><i class="bi bi-bag-check"></i></div>
                </div>
                <div class="dvz-case-body">
                    <h3>Agente IA de atención al cliente</h3>
                    <p>Implementamos un agente conversacional transaccional para una cadena de retail nacional con +80 tiendas, integrado a su ERP y catálogo.</p>
                    <div class="dvz-case-metrics">
                        <div class="dvz-case-metric">
                            <strong>60%</strong>
                            <span>Consultas resueltas por IA</span>
                        </div>
                        <div class="dvz-case-metric">
                            <strong>3×</strong>
                            <span>Velocidad de respuesta</span>
                        </div>
                    </div>
                    <div class="dvz-case-techs">
                        <span class="dvz-case-tech">Python</span>
                        <span class="dvz-case-tech">Groq</span>
                        <span class="dvz-case-tech">REST API</span>
                        <span class="dvz-case-tech">WhatsApp</span>
                    </div>
                </div>
            </div>

            <div class="dvz-case-card">
                <img src="/assets/images/case-manufactura.svg" alt="" class="dvz-case-img" aria-hidden="true">
                <div class="dvz-case-header">
                    <span class="dvz-case-tag">Manufactura</span>
                    <div class="dvz-case-icon"><i class="bi bi-graph-up-arrow"></i></div>
                </div>
                <div class="dvz-case-body">
                    <h3>Plataforma de Business Intelligence</h3>
                    <p>Desarrollamos dashboards en tiempo real para una empresa industrial con +500 empleados, consolidando datos de 8 sistemas diferentes.</p>
                    <div class="dvz-case-metrics">
                        <div class="dvz-case-metric">
                            <strong>6 sem</strong>
                            <span>Tiempo de entrega</span>
                        </div>
                        <div class="dvz-case-metric">
                            <strong>8 fuentes</strong>
                            <span>Datos integrados</span>
                        </div>
                    </div>
                    <div class="dvz-case-techs">
                        <span class="dvz-case-tech">Power BI</span>
                        <span class="dvz-case-tech">Azure</span>
                        <span class="dvz-case-tech">SQL Server</span>
                        <span class="dvz-case-tech">ETL</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===================== CÓMO FUNCIONA SOFIA ===================== -->
<section class="dvz-section dvz-section-dark" id="como-funciona">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Proceso</span>
            <h2 class="fw-bold">Compra en minutos, con IA</h2>
            <p class="dvz-text-muted mx-auto mt-3" style="max-width:580px;">
                SofIA es tu asesora 24/7 — cotiza, asesora y procesa el pago en una sola conversación. Sin formularios, sin esperas.
            </p>
        </div>
        <div class="dvz-steps" id="dvz-steps">
            <svg class="dvz-step-connector" viewBox="0 0 1000 20" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="step-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#2563EB" stop-opacity="0.1"/>
                        <stop offset="30%" stop-color="#2563EB"/>
                        <stop offset="70%" stop-color="#7C3AED"/>
                        <stop offset="100%" stop-color="#7C3AED" stop-opacity="0.1"/>
                    </linearGradient>
                </defs>
                <line id="dvz-step-line" x1="80" y1="10" x2="920" y2="10"
                      stroke="url(#step-grad)" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div class="dvz-step">
                <div class="dvz-step-num">1</div>
                <div class="dvz-step-icon"><i class="bi bi-chat-dots-fill"></i></div>
                <h3>Conversa con SofIA</h3>
                <p>Cuéntale tu necesidad en lenguaje natural. SofIA entiende contexto y hace las preguntas correctas.</p>
            </div>
            <div class="dvz-step">
                <div class="dvz-step-num">2</div>
                <div class="dvz-step-icon"><i class="bi bi-grid-1x2-fill"></i></div>
                <h3>Elige tu solución</h3>
                <p>SofIA consulta el catálogo real con precios en Soles y te muestra las opciones que mejor se ajustan.</p>
            </div>
            <div class="dvz-step">
                <div class="dvz-step-num">3</div>
                <div class="dvz-step-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                <h3>Paga de forma segura</h3>
                <p>Culqi procesa tu pago con tarjeta o Yape — 100% cifrado, sin salir del chat, en Soles (PEN).</p>
            </div>
            <div class="dvz-step">
                <div class="dvz-step-num">4</div>
                <div class="dvz-step-icon"><i class="bi bi-check-circle-fill"></i></div>
                <h3>Recibe tus entregables</h3>
                <p>Confirmación inmediata por email. Tu proyecto arranca en las próximas 24 horas hábiles.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===================== #5 VIDEO DEMO SOFIA ===================== -->
<section class="dvz-section" id="demo">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Demo en vivo</span>
            <h2 class="fw-bold">Mira a SofIA en acción</h2>
            <p class="text-secondary mx-auto mt-3" style="max-width:560px;">
                Observa cómo SofIA asesora, cotiza y procesa pagos en una sola conversación — sin formularios, sin esperas.
            </p>
        </div>
        <div class="dvz-video-outer">
            <div class="dvz-video-wrap">
                <!-- Para activar: añade data-video-id="TU_ID_YOUTUBE" y quita el data-sofia-open -->
                <div class="dvz-video-placeholder" id="dvz-video-ph" data-sofia-open>
                    <img src="/assets/images/demo-thumbnail.svg" alt="Demo SofIA" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.88;">
                    <div class="dvz-video-play" style="position:relative;z-index:2"><i class="bi bi-play-fill"></i></div>
                    <span class="dvz-video-label" style="position:relative;z-index:2">Habla con SofIA ahora · Demo en vivo</span>
                </div>
            </div>
            <div class="dvz-video-features">
                <span class="dvz-video-feat"><i class="bi bi-check-circle-fill"></i> Asesoría 24/7 sin esperas</span>
                <span class="dvz-video-feat"><i class="bi bi-check-circle-fill"></i> Catálogo con precios en Soles</span>
                <span class="dvz-video-feat"><i class="bi bi-check-circle-fill"></i> Pago seguro con Culqi</span>
                <span class="dvz-video-feat"><i class="bi bi-check-circle-fill"></i> Handoff a asesor humano</span>
            </div>
        </div>
    </div>
</section>

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
                    <h3 class="h5 text-white fw-bold mb-4"><i class="bi bi-stars text-primary me-2"></i>Conoce a <span class="dvz-glitch" data-text="SofIA">SofIA</span></h3>
                    <p class="dvz-text-muted">Nuestra asesora virtual transaccional impulsada por IA generativa:</p>
                    <ul class="dvz-check-list">
                        <li>Consulta el catálogo con precios reales en Soles</li>
                        <li>Arma tu carrito de compras conversando</li>
                        <li>Procesa pagos seguros con tarjeta o Yape (Culqi)</li>
                        <li>Te deriva a un asesor humano cuando lo necesitas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== #6 EQUIPO ===================== -->
<section class="dvz-section" id="equipo">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Nuestro equipo</span>
            <h2 class="fw-bold">Las personas detrás de Devioz</h2>
            <p class="text-secondary mx-auto mt-3" style="max-width:540px;">
                Ingenieros, diseñadores y expertos en IA comprometidos con la transformación digital empresarial.
            </p>
        </div>
        <div class="dvz-team-grid">
            <div class="dvz-team-card">
                <img src="/assets/images/avatar-ceo.svg" alt="Cleisson Amir" class="dvz-team-avatar-img">
                <div class="dvz-team-name">Cleisson Amir</div>
                <div class="dvz-team-role">CEO & Fundador</div>
                <div class="dvz-team-bio">Arquitecto de soluciones TI con visión empresarial. Lidera la estrategia y los proyectos de mayor impacto.</div>
            </div>
            <div class="dvz-team-card">
                <img src="/assets/images/avatar-tech.svg" alt="Director de Ingeniería" class="dvz-team-avatar-img">
                <div class="dvz-team-name">Tech Lead</div>
                <div class="dvz-team-role">Director de Ingeniería</div>
                <div class="dvz-team-bio">Especialista en arquitectura cloud y sistemas distribuidos. Más de 8 años entregando software de producción.</div>
            </div>
            <div class="dvz-team-card">
                <img src="/assets/images/avatar-ai.svg" alt="Líder de IA" class="dvz-team-avatar-img">
                <div class="dvz-team-name">AI Research</div>
                <div class="dvz-team-role">Líder de IA</div>
                <div class="dvz-team-bio">Experto en LLMs, agentes conversacionales y automatización inteligente de procesos empresariales.</div>
            </div>
            <div class="dvz-team-card">
                <img src="/assets/images/avatar-ux.svg" alt="Diseño & Producto" class="dvz-team-avatar-img">
                <div class="dvz-team-name">UX Design</div>
                <div class="dvz-team-role">Diseño & Producto</div>
                <div class="dvz-team-bio">Diseñadora centrada en el usuario. Transforma requerimientos complejos en interfaces limpias y efectivas.</div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== TESTIMONIOS ===================== -->
<section class="dvz-section bg-body-tertiary" id="testimonios">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">Testimonios</span>
            <h2 class="fw-bold">Lo que dicen nuestros clientes</h2>
            <p class="text-secondary mx-auto mt-3" style="max-width:560px;">
                Empresas peruanas que ya transformaron su tecnología con Devioz.
            </p>
        </div>
        <div class="dvz-testimonials">

            <div class="dvz-tcard">
                <div class="dvz-tcard-top">
                    <div class="dvz-tcard-quote">"</div>
                    <div class="dvz-tcard-stars">★★★★★</div>
                </div>
                <p class="dvz-tcard-text">Devioz migró todo nuestro core bancario a la nube en tiempo récord. La calidad técnica del equipo y la comunicación durante el proyecto fueron sobresalientes. Un aliado estratégico de verdad.</p>
                <div class="dvz-tcard-author">
                    <div class="dvz-tcard-avatar">CM</div>
                    <div>
                        <div class="dvz-tcard-name">Carlos Mendoza</div>
                        <div class="dvz-tcard-role">CTO · Empresa Financiera · Lima</div>
                    </div>
                </div>
            </div>

            <div class="dvz-tcard">
                <div class="dvz-tcard-top">
                    <div class="dvz-tcard-quote">"</div>
                    <div class="dvz-tcard-stars">★★★★★</div>
                </div>
                <p class="dvz-tcard-text">SofIA redujo nuestro tiempo de atención en un 60%. Nuestro equipo ahora se enfoca en casos complejos mientras la IA maneja las consultas rutinarias de forma impecable.</p>
                <div class="dvz-tcard-author">
                    <div class="dvz-tcard-avatar">AL</div>
                    <div>
                        <div class="dvz-tcard-name">Ana Lucía Torres</div>
                        <div class="dvz-tcard-role">Gerente de Innovación · Retail Nacional</div>
                    </div>
                </div>
            </div>

            <div class="dvz-tcard">
                <div class="dvz-tcard-top">
                    <div class="dvz-tcard-quote">"</div>
                    <div class="dvz-tcard-stars">★★★★★</div>
                </div>
                <p class="dvz-tcard-text">Implementaron nuestra plataforma de Business Intelligence en 6 semanas. Los dashboards en tiempo real transformaron la forma en que tomamos decisiones estratégicas.</p>
                <div class="dvz-tcard-author">
                    <div class="dvz-tcard-avatar">RL</div>
                    <div>
                        <div class="dvz-tcard-name">Roberto Lira</div>
                        <div class="dvz-tcard-role">Director de Operaciones · Grupo Industrial</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===================== #2 CONTACTO (claro) ===================== -->
<section class="dvz-section" id="contacto">
    <div class="container">
        <div class="dvz-contact-grid">

            <!-- Info lado izquierdo -->
            <div class="dvz-contact-info">
                <span class="dvz-eyebrow">Contacto</span>
                <h3>¿Listo para digitalizar tu empresa?</h3>
                <p>Cuéntanos tu proyecto. Te respondemos en menos de 24 horas con una propuesta personalizada. O habla directamente con SofIA.</p>

                <div class="dvz-contact-items">
                    <div class="dvz-contact-item">
                        <div class="dvz-contact-icon"><i class="bi bi-whatsapp"></i></div>
                        <div class="dvz-contact-item-text">
                            <strong>WhatsApp</strong>
                            <a href="https://wa.me/<?= $e($whatsapp) ?>" target="_blank" rel="noopener">+<?= $e($whatsapp) ?></a>
                        </div>
                    </div>
                    <div class="dvz-contact-item">
                        <div class="dvz-contact-icon"><i class="bi bi-envelope"></i></div>
                        <div class="dvz-contact-item-text">
                            <strong>Email</strong>
                            <span>contacto@devioz.com</span>
                        </div>
                    </div>
                    <div class="dvz-contact-item">
                        <div class="dvz-contact-icon"><i class="bi bi-geo-alt"></i></div>
                        <div class="dvz-contact-item-text">
                            <strong>Ubicación</strong>
                            <span>Lima, Perú · Atención LATAM</span>
                        </div>
                    </div>
                    <div class="dvz-contact-item">
                        <div class="dvz-contact-icon"><i class="bi bi-clock"></i></div>
                        <div class="dvz-contact-item-text">
                            <strong>Disponibilidad</strong>
                            <span>SofIA disponible 24/7 · Equipo Lun–Vie 9am–6pm</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button class="btn btn-primary dvz-btn-glow px-4" data-sofia-open>
                        <i class="bi bi-chat-dots me-2"></i>Chatear con SofIA
                    </button>
                    <a class="btn btn-success px-4" target="_blank" rel="noopener"
                       href="https://wa.me/<?= $e($whatsapp) ?>?text=<?= rawurlencode('Hola Devioz, quiero información sobre sus servicios.') ?>">
                        <i class="bi bi-whatsapp me-2"></i>WhatsApp
                    </a>
                </div>
            </div>

            <!-- Formulario lado derecho -->
            <form id="dvz-contact-form" class="dvz-form" novalidate>
                <div class="dvz-form-row">
                    <div class="dvz-form-group">
                        <label for="cf-nombre">Nombre *</label>
                        <input type="text" id="cf-nombre" name="nombre" placeholder="Tu nombre" required>
                    </div>
                    <div class="dvz-form-group">
                        <label for="cf-empresa">Empresa</label>
                        <input type="text" id="cf-empresa" name="empresa" placeholder="Nombre de tu empresa">
                    </div>
                </div>
                <div class="dvz-form-row">
                    <div class="dvz-form-group">
                        <label for="cf-email">Email *</label>
                        <input type="email" id="cf-email" name="email" placeholder="correo@empresa.com" required>
                    </div>
                    <div class="dvz-form-group">
                        <label for="cf-telefono">Teléfono</label>
                        <input type="tel" id="cf-telefono" name="telefono" placeholder="+51 999 999 999">
                    </div>
                </div>
                <div class="dvz-form-group">
                    <label for="cf-servicio">Servicio de interés</label>
                    <select id="cf-servicio" name="servicio">
                        <option value="">Selecciona un servicio…</option>
                        <option>Software Factory</option>
                        <option>IoT</option>
                        <option>Ciberseguridad</option>
                        <option>Cloud</option>
                        <option>RPA</option>
                        <option>Business Intelligence</option>
                        <option>Agente de IA</option>
                        <option>Plantilla Web</option>
                        <option>Otro</option>
                    </select>
                </div>
                <div class="dvz-form-group">
                    <label for="cf-mensaje">Mensaje *</label>
                    <textarea id="cf-mensaje" name="mensaje" placeholder="Cuéntanos tu proyecto o necesidad…" required></textarea>
                </div>
                <div class="dvz-form-msg" role="alert"></div>
                <button type="submit" class="dvz-form-submit">
                    <i class="bi bi-send me-2"></i>Enviar mensaje
                </button>
            </form>

        </div>
    </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="dvz-section bg-body-tertiary" id="faq">
    <div class="container">
        <div class="text-center mb-5">
            <span class="dvz-eyebrow">FAQ</span>
            <h2 class="fw-bold">Todo lo que necesitas saber</h2>
            <p class="text-secondary mx-auto mt-3" style="max-width: 560px;">
                Resolvemos tus dudas más comunes sobre nuestros servicios, SofIA y el proceso de compra.
            </p>
        </div>
        <div class="dvz-faq-list">
            <?php
            $faqs = [
                [
                    '¿Qué servicios ofrece Devioz?',
                    'Ofrecemos 9 líneas de servicio: Software Factory, IoT, Ciberseguridad, Cloud, RPA, Diseño UX/UI, Multimedia, Marketing Digital y Plantillas Web + Agentes de IA. Todo desde un único aliado tecnológico para medianas y grandes empresas.',
                ],
                [
                    '¿Cómo puedo cotizar un proyecto?',
                    'La forma más rápida es conversar con SofIA, nuestra agente con IA disponible 24/7. Cuéntale tu necesidad y ella te guiará por el catálogo de productos o te conectará con un asesor humano para proyectos a medida.',
                ],
                [
                    '¿Qué es SofIA y cómo funciona?',
                    'SofIA es nuestro agente transaccional impulsado por IA generativa. Puede consultar el catálogo con precios reales en Soles, armar tu carrito de compras, procesar pagos seguros con tarjeta o Yape vía Culqi, y derivarte a un asesor humano cuando lo necesitas.',
                ],
                [
                    '¿Cómo funciona el proceso de compra?',
                    'El proceso es 100% digital: (1) Conversa con SofIA y elige tu producto, (2) SofIA genera tu orden con código único, (3) Paga de forma segura con tarjeta de crédito/débito o Yape en Soles mediante Culqi, (4) Recibes confirmación por email y el equipo Devioz entrega los entregables en 24 horas hábiles.',
                ],
                [
                    '¿Trabajan solo con empresas peruanas?',
                    'Nuestro equipo está basado en Lima, Perú, pero atendemos proyectos en toda Latinoamérica. Nos especializamos en medianas y grandes empresas e instituciones que buscan un aliado tecnológico de confianza con estándares internacionales.',
                ],
                [
                    '¿Tienen soporte post-entrega?',
                    'Sí. Todos nuestros productos y proyectos incluyen un periodo de garantía y soporte. Además, SofIA está disponible 24/7 para consultas rápidas, y puedes contactarnos directamente por WhatsApp para seguimiento de tu proyecto.',
                ],
            ];
            foreach ($faqs as $i => [$q, $a]): ?>
            <div class="dvz-faq-item">
                <button class="dvz-faq-btn" type="button" aria-expanded="false" aria-controls="faq-body-<?= $i ?>">
                    <span><?= $e($q) ?></span>
                    <span class="dvz-faq-icon"><i class="bi bi-plus-lg"></i></span>
                </button>
                <div class="dvz-faq-body" id="faq-body-<?= $i ?>">
                    <?= $e($a) ?>
                </div>
            </div>
            <?php endforeach; ?>
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
                    <li><a href="#equipo">Equipo</a></li>
                    <li><a href="#casos">Casos de éxito</a></li>
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

<!-- Loader: oculta la pantalla de carga cuando todo está listo -->
<script>
(function () {
    var t0 = Date.now();
    window.addEventListener('load', function () {
        var delay = Math.max(0, 600 - (Date.now() - t0)); // mínimo 600ms visible
        setTimeout(function () {
            var el = document.getElementById('dvz-loader');
            if (el) {
                el.classList.add('dvz-loader-out');
                setTimeout(function () { el.remove(); }, 600);
            }
        }, delay);
    });
}());
</script>

<script>
(function () {
    document.querySelectorAll('.dvz-faq-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.dvz-faq-item');
            var isOpen = item.classList.contains('open');
            // Close all
            document.querySelectorAll('.dvz-faq-item.open').forEach(function (el) {
                el.classList.remove('open');
                el.querySelector('.dvz-faq-btn').setAttribute('aria-expanded', 'false');
            });
            // Toggle clicked
            if (!isOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
}());
</script>

<!-- ── #8 Volver arriba ────────────────────────────────────────── -->
<canvas id="dvz-particles" aria-hidden="true"></canvas>
<button id="dvz-back-top" aria-label="Volver al inicio">
    <i class="bi bi-chevron-up"></i>
</button>

<!-- ── #7 Cookie consent ──────────────────────────────────────── -->
<div id="dvz-cookie" role="dialog" aria-label="Aviso de cookies">
    <p>Usamos cookies para mejorar tu experiencia y analizar el tráfico. Al continuar navegando, aceptas nuestra <a href="#" onclick="return false;">política de privacidad</a>.</p>
    <div class="dvz-cookie-btns">
        <button class="dvz-cookie-btn dvz-cookie-accept">Aceptar</button>
        <button class="dvz-cookie-btn dvz-cookie-decline">Rechazar</button>
    </div>
</div>

</body>
</html>
