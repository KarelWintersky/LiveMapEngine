;(function () {
    'use strict';

    var burger = document.getElementById('top-bar-burger');
    var nav = document.getElementById('top-bar-nav');

    if (burger && nav) {
        burger.addEventListener('click', function () {
            burger.classList.toggle('active');
            nav.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!burger.contains(e.target) && !nav.contains(e.target)) {
                burger.classList.remove('active');
                nav.classList.remove('open');
            }
        });
    }

    var cards = document.querySelectorAll('.map-card');
    if (cards.length && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('map-card--visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        cards.forEach(function (card) {
            observer.observe(card);
        });
    } else {
        cards.forEach(function (card) {
            card.classList.add('map-card--visible');
        });
    }

    if (typeof flash_messages !== 'undefined' && flash_messages) {
        var msg = '';
        var type = '';

        if (typeof flash_messages === 'object' && !Array.isArray(flash_messages)) {
            if (flash_messages.success) {
                msg = flash_messages.success;
                type = 'success';
            } else if (flash_messages.error) {
                msg = flash_messages.error;
                type = 'error';
            } else if (flash_messages.custom) {
                msg = flash_messages.custom;
                type = 'custom';
            }
        } else if (Array.isArray(flash_messages) && flash_messages.length) {
            msg = flash_messages.join(' · ');
            type = 'custom';
        }

        if (msg) {
            var bar = document.createElement('div');
            bar.className = 'notify-bar notify-bar--' + type;

            if (typeof msg === 'string') {
                msg = [msg];
            }

            bar.textContent = msg.join(' · ');

            Object.assign(bar.style, {
                position: 'fixed',
                top: '64px',
                left: '50%',
                transform: 'translateX(-50%)',
                zIndex: '200',
                padding: '12px 24px',
                borderRadius: '6px',
                fontSize: '0.9rem',
                fontFamily: '"Poppins", sans-serif',
                maxWidth: '600px',
                textAlign: 'center',
                boxShadow: '0 2px 12px rgba(0,0,0,0.15)',
                opacity: '0',
                transition: 'opacity 0.3s'
            });

            if (type === 'error') {
                bar.style.background = '#d32f2f';
                bar.style.color = '#fff';
            } else if (type === 'success') {
                bar.style.background = '#2e7d32';
                bar.style.color = '#fff';
            } else {
                bar.style.background = '#1565c0';
                bar.style.color = '#fff';
            }

            document.body.appendChild(bar);

            requestAnimationFrame(function () {
                bar.style.opacity = '1';
            });

            var delay = type === 'error' ? 8000 : 3000;
            setTimeout(function () {
                bar.style.opacity = '0';
                setTimeout(function () { bar.remove(); }, 400);
            }, delay);
        }
    }
})();
