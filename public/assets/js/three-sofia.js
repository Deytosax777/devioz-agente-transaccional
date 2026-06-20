/**
 * DEVIOZ — Orbe 3D animado de SofIA (Nosotros section)
 */
(function () {
    'use strict';

    function initSofiaOrb() {
        var canvas = document.getElementById('sofia-orb');
        if (!canvas || !window.THREE) return;

        var W = canvas.offsetWidth  || 280;
        var H = canvas.offsetHeight || 280;

        var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setSize(W, H);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        var scene  = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(48, W / H, 0.1, 50);
        camera.position.z = 2.9;

        var group = new THREE.Group();
        scene.add(group);

        /* ── Núcleo sólido translúcido ── */
        var coreGeo = new THREE.SphereGeometry(0.68, 48, 48);
        var coreMat = new THREE.MeshPhongMaterial({
            color: 0x1E3A8A, emissive: 0x172554,
            transparent: true, opacity: 0.38, shininess: 120
        });
        var core = new THREE.Mesh(coreGeo, coreMat);
        group.add(core);

        /* ── Capa wireframe ── */
        var wireGeo = new THREE.SphereGeometry(0.71, 22, 22);
        var wireMat = new THREE.MeshBasicMaterial({
            color: 0x3B82F6, wireframe: true, transparent: true, opacity: 0.22
        });
        var wire = new THREE.Mesh(wireGeo, wireMat);
        group.add(wire);

        /* ── Anillos orbitales ── */
        function makeRing(r, tube, color, opacity, rx, rz) {
            var geo = new THREE.TorusGeometry(r, tube, 8, 90);
            var mat = new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: opacity });
            var mesh = new THREE.Mesh(geo, mat);
            mesh.rotation.x = rx || 0;
            mesh.rotation.z = rz || 0;
            group.add(mesh);
            return mesh;
        }
        var ring1 = makeRing(1.04, 0.008, 0x2563EB, 0.60, Math.PI / 2, 0);
        var ring2 = makeRing(1.20, 0.006, 0x7C3AED, 0.45, Math.PI / 3, Math.PI / 6);
        var ring3 = makeRing(0.93, 0.005, 0x34D399, 0.40, Math.PI / 5, -Math.PI / 4);

        /* ── Partículas flotantes ── */
        var N   = 220;
        var pos = new Float32Array(N * 3);
        for (var i = 0; i < N; i++) {
            var phi   = Math.acos(2 * Math.random() - 1);
            var theta = Math.random() * Math.PI * 2;
            var r     = 1.28 + Math.random() * 0.42;
            pos[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
            pos[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
            pos[i * 3 + 2] = r * Math.cos(phi);
        }
        var ptGeo = new THREE.BufferGeometry();
        ptGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        var ptMat = new THREE.PointsMaterial({ color: 0x60A5FA, size: 0.026, transparent: true, opacity: 0.75 });
        var particles = new THREE.Points(ptGeo, ptMat);
        group.add(particles);

        /* ── Luces ── */
        scene.add(new THREE.AmbientLight(0x2563EB, 0.65));
        var l1 = new THREE.PointLight(0x7C3AED, 3.5, 8);
        l1.position.set(2, 2, 2);
        scene.add(l1);
        var l2 = new THREE.PointLight(0x34D399, 2.2, 6);
        l2.position.set(-2, -1.5, 1);
        scene.add(l2);
        var l3 = new THREE.PointLight(0x60A5FA, 1.5, 5);
        l3.position.set(0, 0, 3);
        scene.add(l3);

        /* ── Interacción con el ratón ── */
        var targetX = 0, targetY = 0, curX = 0, curY = 0;
        var wrap = canvas.closest('.dvz-glass') || canvas.parentElement;
        wrap.addEventListener('mousemove', function (e) {
            var rect = wrap.getBoundingClientRect();
            targetX = ((e.clientX - rect.left) / rect.width  - 0.5) * 1.2;
            targetY = ((e.clientY - rect.top)  / rect.height - 0.5) * 0.9;
        });
        wrap.addEventListener('mouseleave', function () { targetX = 0; targetY = 0; });

        /* ── Loop de animación ── */
        var clock = new THREE.Clock();
        (function animate() {
            requestAnimationFrame(animate);
            var t = clock.getElapsedTime();

            curX += (targetX - curX) * 0.06;
            curY += (targetY - curY) * 0.06;

            wire.rotation.y      = t * 0.45;
            ring1.rotation.z     = t * 0.65;
            ring2.rotation.x     = Math.PI / 3 - t * 0.38;
            ring3.rotation.y     = Math.PI / 5 + t * 0.52;
            particles.rotation.y = t * 0.14;
            particles.rotation.x = Math.sin(t * 0.3) * 0.12;

            var pulse = 1 + Math.sin(t * 1.9) * 0.04;
            core.scale.setScalar(pulse);

            group.rotation.y = curX;
            group.rotation.x = -curY;

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
        document.addEventListener('DOMContentLoaded', initSofiaOrb);
    } else {
        setTimeout(initSofiaOrb, 0);
    }
}());
