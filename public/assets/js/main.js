/**
 * DEVIOZ - Interacciones generales de la web corporativa.
 */
(function () {
    'use strict';

    // Navbar: fondo sólido al hacer scroll
    var nav = document.getElementById('mainNav');
    if (nav) {
        var onScroll = function () {
            nav.classList.toggle('scrolled', window.scrollY > 40);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Scroll suave para anclas internas
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Botones "Hablar con SofIA": abren el widget (con mensaje opcional)
    document.querySelectorAll('[data-sofia-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var message = btn.getAttribute('data-sofia-message') || null;
            if (window.SofiaWidget && typeof window.SofiaWidget.open === 'function') {
                window.SofiaWidget.open(message);
            } else {
                var tries = 0;
                var timer = setInterval(function () {
                    tries++;
                    if (window.SofiaWidget && typeof window.SofiaWidget.open === 'function') {
                        clearInterval(timer);
                        window.SofiaWidget.open(message);
                    } else if (tries > 20) {
                        clearInterval(timer);
                    }
                }, 250);
            }
        });
    });

    // Revelado sutil de tarjetas al hacer scroll
    if ('IntersectionObserver' in window) {
        var revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'none';
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.dvz-card, .dvz-product-card').forEach(function (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(18px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            revealObserver.observe(card);
        });
    }

    // ── #4 Contadores animados al entrar en viewport ──────────
    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var target = parseInt(el.dataset.count, 10);
                var prefix = el.dataset.prefix || '';
                var suffix = el.dataset.suffix || '';
                var duration = 1600;
                var start = performance.now();
                counterObserver.unobserve(el);
                (function tick(now) {
                    var elapsed = now - start;
                    var p = Math.min(elapsed / duration, 1);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = prefix + Math.round(eased * target) + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                }(start));
            });
        }, { threshold: 0.6 });

        document.querySelectorAll('[data-count]').forEach(function (el) {
            counterObserver.observe(el);
        });
    }

    // ── #7 Cookie consent ────────────────────────────────────
    (function () {
        var banner = document.getElementById('dvz-cookie');
        if (!banner) return;
        if (localStorage.getItem('dvz_cookies')) { banner.remove(); return; }
        banner.querySelector('.dvz-cookie-accept').addEventListener('click', function () {
            localStorage.setItem('dvz_cookies', 'accepted');
            banner.classList.add('hidden');
        });
        banner.querySelector('.dvz-cookie-decline').addEventListener('click', function () {
            localStorage.setItem('dvz_cookies', 'declined');
            banner.classList.add('hidden');
        });
    }());

    // ── #8 Botón volver arriba ───────────────────────────────
    (function () {
        var btn = document.getElementById('dvz-back-top');
        if (!btn) return;
        window.addEventListener('scroll', function () {
            btn.classList.toggle('visible', window.scrollY > 400);
        }, { passive: true });
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }());

    // ── #2 Formulario de contacto ────────────────────────────
    (function () {
        var form = document.getElementById('dvz-contact-form');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var submitBtn = form.querySelector('.dvz-form-submit');
            var msg = form.querySelector('.dvz-form-msg');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando…';
            msg.className = 'dvz-form-msg';
            var data = {};
            new FormData(form).forEach(function (v, k) { data[k] = v; });
            fetch('/api/contact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }).then(function (res) {
                return res.json();
            }).then(function (json) {
                if (json.success) {
                    msg.className = 'dvz-form-msg success';
                    msg.textContent = '✅ Mensaje enviado. Te contactamos en menos de 24 horas.';
                    form.reset();
                } else {
                    throw new Error(json.message || 'Error al enviar');
                }
            }).catch(function (err) {
                msg.className = 'dvz-form-msg error';
                msg.textContent = '❌ ' + (err.message || 'No se pudo enviar. Usa WhatsApp o escríbenos directamente.');
            }).finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar mensaje';
            });
        });
    }());

    /* ── Tilt 3D (excluye flip cards) ───────────────────────────── */
    (function () {
        var SELECTORS = '.dvz-tcard, .dvz-case-card, .dvz-team-card, .dvz-product-card';
        var MAX_TILT  = 10;

        document.querySelectorAll(SELECTORS).forEach(function (card) {
            card.style.willChange = 'transform';
            card.style.transition = 'transform 0.12s ease, box-shadow 0.12s ease';
            card.style.transformStyle = 'preserve-3d';

            card.addEventListener('mousemove', function (e) {
                var rect  = card.getBoundingClientRect();
                var cx    = (e.clientX - rect.left) / rect.width  - 0.5;
                var cy    = (e.clientY - rect.top)  / rect.height - 0.5;
                var rotX  = -cy * MAX_TILT;
                var rotY  =  cx * MAX_TILT;
                var lift  = Math.sqrt(cx * cx + cy * cy) * 6;
                card.style.transform    = 'perspective(800px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) scale(1.025)';
                card.style.boxShadow    = '0 ' + (18 + lift) + 'px ' + (36 + lift * 3) + 'px rgba(37,99,235,0.20)';
            });

            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
                card.style.boxShadow = '';
            });
        });
    }());

    /* ── Typewriter en hero h1 ───────────────────────────────────── */
    (function () {
        var el = document.querySelector('.dvz-typewriter');
        if (!el) return;
        var words;
        try { words = JSON.parse(el.dataset.words); } catch (e) { return; }
        if (!words.length) return;
        var idx = 0, charIdx = words[0].length, isDeleting = false;

        function tick() {
            var word  = words[idx % words.length];
            el.textContent = isDeleting ? word.slice(0, --charIdx) : word.slice(0, ++charIdx);

            var delay = isDeleting ? 55 : 95;
            if (!isDeleting && charIdx === word.length) { delay = 2200; isDeleting = true; }
            else if (isDeleting && charIdx === 0)       { isDeleting = false; idx++; delay = 350; }
            setTimeout(tick, delay);
        }
        setTimeout(tick, 2500);
    }());

    /* ── Cursor magnético en botones CTA ────────────────────────── */
    (function () {
        var btns = document.querySelectorAll('.dvz-btn-glow, .dvz-link-wa');
        btns.forEach(function (btn) {
            btn.classList.add('dvz-magnetic');
            btn.addEventListener('mouseenter', function () {
                btn.style.transition = 'transform 0.12s ease';
            });
            btn.addEventListener('mousemove', function (e) {
                var r  = btn.getBoundingClientRect();
                var dx = (e.clientX - (r.left + r.width  / 2)) * 0.40;
                var dy = (e.clientY - (r.top  + r.height / 2)) * 0.40;
                btn.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(1.05)';
            });
            btn.addEventListener('mouseleave', function () {
                btn.style.transition = 'transform 0.45s cubic-bezier(.23,1,.32,1)';
                btn.style.transform  = '';
            });
        });
    }());

    /* ── Parallax en hero al hacer scroll ───────────────────────── */
    (function () {
        var content = document.querySelector('.dvz-hero-content');
        var hint    = document.querySelector('.dvz-scroll-hint');
        if (!content) return;
        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () {
                var y = window.scrollY;
                if (y < window.innerHeight) {
                    content.style.transform = 'translateY(' + (y * 0.30) + 'px)';
                    if (hint) hint.style.opacity = 1 - y / 200;
                }
                ticking = false;
            });
        }, { passive: true });
    }());

    /* ── Draw-on-scroll: conector de pasos ──────────────────────── */
    (function () {
        var line  = document.getElementById('dvz-step-line');
        var steps = document.getElementById('dvz-steps');
        if (!line || !steps) return;
        var obs = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) {
                line.classList.add('dvz-drawn');
                obs.disconnect();
            }
        }, { threshold: 0.35 });
        obs.observe(steps);
    }());

    /* ── Partículas de fondo ─────────────────────────────────────── */
    (function () {
        var canvas = document.getElementById('dvz-particles');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var W, H;
        var MOUSE  = { x: -9999, y: -9999 };
        var COUNT  = 85;
        var DIST   = 145;
        var dots   = [];

        function resize() {
            W = canvas.width  = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize, { passive: true });
        window.addEventListener('mousemove', function (e) {
            MOUSE.x = e.clientX; MOUSE.y = e.clientY;
        }, { passive: true });

        for (var i = 0; i < COUNT; i++) {
            dots.push({
                x:  Math.random() * window.innerWidth,
                y:  Math.random() * window.innerHeight,
                vx: (Math.random() - 0.5) * 0.25,
                vy: (Math.random() - 0.5) * 0.25,
                r:  Math.random() * 1.4 + 0.4
            });
        }

        (function frame() {
            requestAnimationFrame(frame);
            ctx.clearRect(0, 0, W, H);

            for (var i = 0; i < dots.length; i++) {
                var d  = dots[i];
                var mdx = d.x - MOUSE.x, mdy = d.y - MOUSE.y;
                var md  = Math.sqrt(mdx * mdx + mdy * mdy);
                if (md < 110) { d.vx += mdx / md * 0.07; d.vy += mdy / md * 0.07; }
                d.vx *= 0.992; d.vy *= 0.992;
                d.x  += d.vx;  d.y  += d.vy;
                if (d.x < 0) d.x = W; if (d.x > W) d.x = 0;
                if (d.y < 0) d.y = H; if (d.y > H) d.y = 0;

                ctx.beginPath();
                ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(96,165,250,0.55)';
                ctx.fill();

                for (var j = i + 1; j < dots.length; j++) {
                    var d2  = dots[j];
                    var dx  = d.x - d2.x, dy = d.y - d2.y;
                    var dst = Math.sqrt(dx * dx + dy * dy);
                    if (dst < DIST) {
                        ctx.beginPath();
                        ctx.moveTo(d.x, d.y); ctx.lineTo(d2.x, d2.y);
                        ctx.strokeStyle = 'rgba(37,99,235,' + ((1 - dst / DIST) * 0.14) + ')';
                        ctx.lineWidth   = 0.8;
                        ctx.stroke();
                    }
                }
            }
        }());
    }());

}());
