/**
 * DEVIOZ — Esfera 3D del stack tecnológico
 */
(function () {
    'use strict';

    function initTechSphere() {
        var canvas = document.getElementById('tech-sphere');
        if (!canvas || !window.THREE) return;

        var W = canvas.offsetWidth  || 480;
        var H = canvas.offsetHeight || 360;

        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setSize(W, H);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        var scene  = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(46, W / H, 0.1, 60);
        camera.position.z = 3.6;

        var group = new THREE.Group();
        scene.add(group);

        /* ── Esfera wireframe base ── */
        var baseGeo = new THREE.SphereGeometry(1.4, 18, 18);
        var baseMat = new THREE.MeshBasicMaterial({
            color: 0x2563EB, wireframe: true, transparent: true, opacity: 0.055
        });
        group.add(new THREE.Mesh(baseGeo, baseMat));

        /* ── Nodos de tecnología distribuidos en la superficie ── */
        var TECH_COLORS = [
            0x60A5FA, /* React / Next */
            0xA78BFA, /* PHP / Python */
            0x34D399, /* Node / APIs */
            0xFBBF24, /* AWS / Cloud */
            0xF472B6, /* Docker */
            0x38BDF8  /* MySQL / Redis */
        ];

        var techCount = 16;
        var golden    = (1 + Math.sqrt(5)) / 2;
        var nodes     = [];

        for (var i = 0; i < techCount; i++) {
            var theta = 2 * Math.PI * i / golden;
            var phi   = Math.acos(1 - 2 * (i + 0.5) / techCount);
            var R     = 1.4;
            var x     = R * Math.sin(phi) * Math.cos(theta);
            var y     = R * Math.sin(phi) * Math.sin(theta);
            var z     = R * Math.cos(phi);

            var col = TECH_COLORS[i % TECH_COLORS.length];

            /* Nodo principal */
            var dotGeo = new THREE.SphereGeometry(0.065, 10, 10);
            var dotMat = new THREE.MeshPhongMaterial({
                color: col, emissive: col, emissiveIntensity: 0.55, shininess: 80
            });
            var dot = new THREE.Mesh(dotGeo, dotMat);
            dot.position.set(x, y, z);
            group.add(dot);

            /* Halo glow */
            var haloGeo = new THREE.SphereGeometry(0.13, 8, 8);
            var haloMat = new THREE.MeshBasicMaterial({
                color: col, transparent: true, opacity: 0.14
            });
            var halo = new THREE.Mesh(haloGeo, haloMat);
            halo.position.copy(dot.position);
            group.add(halo);

            nodes.push({ dot: dot, halo: halo, phase: Math.random() * Math.PI * 2 });
        }

        /* ── Líneas de conexión entre nodos cercanos ── */
        var lineMat = new THREE.LineBasicMaterial({
            color: 0x2563EB, transparent: true, opacity: 0.10
        });
        for (var a = 0; a < techCount; a++) {
            for (var b = a + 1; b < techCount; b++) {
                var da = nodes[a].dot.position;
                var db = nodes[b].dot.position;
                if (da.distanceTo(db) < 1.15) {
                    var lineGeo = new THREE.BufferGeometry().setFromPoints([da, db]);
                    group.add(new THREE.Line(lineGeo, lineMat));
                }
            }
        }

        /* ── Partículas de fondo ── */
        var N   = 80;
        var pps = new Float32Array(N * 3);
        for (var k = 0; k < N; k++) {
            var r2     = 1.6 + Math.random() * 0.7;
            var th2    = Math.random() * Math.PI * 2;
            var ph2    = Math.acos(2 * Math.random() - 1);
            pps[k * 3]     = r2 * Math.sin(ph2) * Math.cos(th2);
            pps[k * 3 + 1] = r2 * Math.sin(ph2) * Math.sin(th2);
            pps[k * 3 + 2] = r2 * Math.cos(ph2);
        }
        var ppGeo = new THREE.BufferGeometry();
        ppGeo.setAttribute('position', new THREE.BufferAttribute(pps, 3));
        var ppMat = new THREE.PointsMaterial({ color: 0x3B82F6, size: 0.02, transparent: true, opacity: 0.5 });
        group.add(new THREE.Points(ppGeo, ppMat));

        /* ── Luces ── */
        scene.add(new THREE.AmbientLight(0x2563EB, 0.7));
        var l1 = new THREE.PointLight(0x7C3AED, 3, 10);
        l1.position.set(3, 3, 3);
        scene.add(l1);
        var l2 = new THREE.PointLight(0x34D399, 2, 8);
        l2.position.set(-3, -2, 2);
        scene.add(l2);

        /* ── Interacción ratón ── */
        var targetX = 0, targetY = 0, curX = 0, curY = 0;
        var isDragging = false, lastMX = 0, lastMY = 0;
        var velocityX = 0.003, velocityY = 0.001;

        canvas.addEventListener('mousedown', function (e) {
            isDragging = true;
            lastMX = e.clientX; lastMY = e.clientY;
        });
        window.addEventListener('mouseup', function () { isDragging = false; });
        canvas.addEventListener('mousemove', function (e) {
            if (isDragging) {
                velocityX = (e.clientX - lastMX) * 0.004;
                velocityY = (e.clientY - lastMY) * 0.004;
                lastMX = e.clientX; lastMY = e.clientY;
            } else {
                var rect = canvas.getBoundingClientRect();
                targetX = ((e.clientX - rect.left) / rect.width  - 0.5) * 0.5;
                targetY = ((e.clientY - rect.top)  / rect.height - 0.5) * 0.4;
            }
        });
        canvas.addEventListener('mouseleave', function () { targetX = 0; targetY = 0; });

        /* ── Loop ── */
        var clock = new THREE.Clock();
        var rotY  = 0, rotX = 0;

        (function animate() {
            requestAnimationFrame(animate);
            var t = clock.getElapsedTime();

            curX += (targetX - curX) * 0.04;
            curY += (targetY - curY) * 0.04;

            if (isDragging) {
                rotY += velocityX;
                rotX += velocityY;
                velocityX *= 0.92;
                velocityY *= 0.92;
            } else {
                rotY += 0.0025;
            }

            group.rotation.y = rotY + curX;
            group.rotation.x = rotX - curY * 0.4;

            /* Pulso de halos */
            nodes.forEach(function (n, idx) {
                var pulse = 0.13 + Math.sin(t * 1.6 + n.phase) * 0.06;
                n.halo.material.opacity = pulse;
            });

            renderer.render(scene, camera);
        }());

        /* ── Resize ── */
        window.addEventListener('resize', function () {
            var nw = canvas.offsetWidth, nh = canvas.offsetHeight;
            if (nw < 1 || nh < 1) return;
            camera.aspect = nw / nh;
            camera.updateProjectionMatrix();
            renderer.setSize(nw, nh);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTechSphere);
    } else {
        setTimeout(initTechSphere, 0);
    }
}());
