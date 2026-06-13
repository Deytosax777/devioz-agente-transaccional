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
                // El widget aún no cargó: reintentar brevemente
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
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'none';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.dvz-card, .dvz-product-card').forEach(function (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(18px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    }
})();
