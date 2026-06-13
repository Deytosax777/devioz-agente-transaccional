/**
 * DEVIOZ - Fondo 3D interactivo del hero (Three.js)
 * Esfera abstracta de partículas con red de nodos azules + efecto paralaje.
 *
 * Performance:
 *  - Detección de soporte WebGL con fallback CSS (clase .no-webgl).
 *  - El render se pausa (cancelAnimationFrame) cuando el canvas no es
 *    visible (IntersectionObserver) o la pestaña está oculta.
 */
(function () {
    'use strict';

    var canvas = document.getElementById('hero-canvas');
    var hero = document.querySelector('.dvz-hero');
    if (!canvas || !hero) return;

    // ---------- Detección de WebGL ----------
    function supportsWebGL() {
        try {
            var test = document.createElement('canvas');
            return !!(window.WebGLRenderingContext &&
                (test.getContext('webgl') || test.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    if (typeof THREE === 'undefined' || !supportsWebGL()) {
        hero.classList.add('no-webgl');
        return;
    }

    // ---------- Escena ----------
    var scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x0a0e1a, 0.18);

    var camera = new THREE.PerspectiveCamera(60, hero.clientWidth / hero.clientHeight, 0.1, 100);
    camera.position.z = 5.2;

    var renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance'
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(hero.clientWidth, hero.clientHeight);

    // ---------- Esfera de partículas (distribución de Fibonacci) ----------
    var PARTICLES = 900;
    var RADIUS = 2.4;
    var positions = new Float32Array(PARTICLES * 3);
    var points3 = [];

    var golden = Math.PI * (3 - Math.sqrt(5));
    for (var i = 0; i < PARTICLES; i++) {
        var y = 1 - (i / (PARTICLES - 1)) * 2;
        var r = Math.sqrt(1 - y * y);
        var theta = golden * i;

        // Jitter sutil para una apariencia orgánica
        var jitter = 1 + (Math.random() - 0.5) * 0.08;
        var x = Math.cos(theta) * r * RADIUS * jitter;
        var py = y * RADIUS * jitter;
        var z = Math.sin(theta) * r * RADIUS * jitter;

        positions[i * 3] = x;
        positions[i * 3 + 1] = py;
        positions[i * 3 + 2] = z;
        points3.push(new THREE.Vector3(x, py, z));
    }

    var pointsGeo = new THREE.BufferGeometry();
    pointsGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    var pointsMat = new THREE.PointsMaterial({
        color: 0x60a5fa,
        size: 0.028,
        transparent: true,
        opacity: 0.9,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });

    var sphere = new THREE.Points(pointsGeo, pointsMat);
    scene.add(sphere);

    // ---------- Red de nodos: conectar vecinos cercanos ----------
    var MAX_DIST = 0.55;
    var MAX_LINKS = 1600;
    var linePositions = [];

    outer:
    for (var a = 0; a < PARTICLES; a += 2) {
        for (var b = a + 1; b < Math.min(a + 30, PARTICLES); b++) {
            if (points3[a].distanceTo(points3[b]) < MAX_DIST) {
                linePositions.push(
                    points3[a].x, points3[a].y, points3[a].z,
                    points3[b].x, points3[b].y, points3[b].z
                );
                if (linePositions.length / 6 >= MAX_LINKS) break outer;
            }
        }
    }

    var linesGeo = new THREE.BufferGeometry();
    linesGeo.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));

    var linesMat = new THREE.LineBasicMaterial({
        color: 0x3b82f6,
        transparent: true,
        opacity: 0.16,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    });

    var network = new THREE.LineSegments(linesGeo, linesMat);
    scene.add(network);

    // Partículas exteriores flotantes (profundidad)
    var DUST = 220;
    var dustPos = new Float32Array(DUST * 3);
    for (var d = 0; d < DUST; d++) {
        dustPos[d * 3] = (Math.random() - 0.5) * 14;
        dustPos[d * 3 + 1] = (Math.random() - 0.5) * 9;
        dustPos[d * 3 + 2] = (Math.random() - 0.5) * 6 - 1;
    }
    var dustGeo = new THREE.BufferGeometry();
    dustGeo.setAttribute('position', new THREE.BufferAttribute(dustPos, 3));
    var dust = new THREE.Points(dustGeo, new THREE.PointsMaterial({
        color: 0x22d3ee,
        size: 0.02,
        transparent: true,
        opacity: 0.5,
        blending: THREE.AdditiveBlending,
        depthWrite: false
    }));
    scene.add(dust);

    // ---------- Paralaje con el mouse ----------
    var mouseX = 0, mouseY = 0, targetX = 0, targetY = 0;

    window.addEventListener('mousemove', function (e) {
        mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
        mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    window.addEventListener('deviceorientation', function (e) {
        if (e.gamma !== null) {
            mouseX = Math.max(-1, Math.min(1, e.gamma / 30));
            mouseY = Math.max(-1, Math.min(1, (e.beta - 45) / 30));
        }
    }, { passive: true });

    // ---------- Loop con lazy rendering ----------
    var rafId = null;
    var isVisible = true;
    var clock = new THREE.Clock();

    function animate() {
        rafId = requestAnimationFrame(animate);

        var t = clock.getElapsedTime();

        // Suavizado del paralaje
        targetX += (mouseX - targetX) * 0.05;
        targetY += (mouseY - targetY) * 0.05;

        sphere.rotation.y = t * 0.08 + targetX * 0.35;
        sphere.rotation.x = Math.sin(t * 0.05) * 0.1 + targetY * 0.25;
        network.rotation.copy(sphere.rotation);

        dust.rotation.y = t * 0.02;

        // Pulso sutil de la esfera
        var scale = 1 + Math.sin(t * 0.8) * 0.015;
        sphere.scale.setScalar(scale);
        network.scale.setScalar(scale);

        camera.position.x = targetX * 0.3;
        camera.position.y = -targetY * 0.2;
        camera.lookAt(scene.position);

        renderer.render(scene, camera);
    }

    function start() {
        if (rafId === null && isVisible && !document.hidden) {
            clock.start();
            animate();
        }
    }

    function stop() {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
            clock.stop();
        }
    }

    // Pausar cuando el hero sale del viewport
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            isVisible = entries[0].isIntersecting;
            isVisible ? start() : stop();
        }, { threshold: 0.05 }).observe(canvas);
    }

    // Pausar cuando la pestaña está oculta
    document.addEventListener('visibilitychange', function () {
        document.hidden ? stop() : start();
    });

    // ---------- Resize ----------
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            camera.aspect = hero.clientWidth / hero.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(hero.clientWidth, hero.clientHeight);
        }, 150);
    }, { passive: true });

    start();
})();
