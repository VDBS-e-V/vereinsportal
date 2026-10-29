/*
 * Feedback-Helfer (Paket "feedback"). Alles ist Verbesserung: ohne JavaScript bleiben Toasts im Inhalt stehen.
 *  - Toast             [data-vdbs-toast]   Schließen-Knopf, automatisches Schließen (data-timeout in Sekunden,
 *                                          pausiert bei Hover und Fokus)
 *  - Tooltip           [data-vdbs-tooltip] verbindet das Element per aria-describedby, Esc blendet aus
 */

const closeToast = (toast) => {
    if (!toast.isConnected) {
        return;
    }

    toast.classList.add('vdbs-toast--leaving');
    window.setTimeout(() => toast.remove(), 220);
};

const armToast = (toast) => {
    const seconds = Number(toast.dataset.timeout ?? 0);

    if (seconds <= 0) {
        return;
    }

    let remaining = seconds * 1000;
    let started = Date.now();
    let timer = window.setTimeout(() => closeToast(toast), remaining);

    const pause = () => {
        window.clearTimeout(timer);
        remaining -= Date.now() - started;
    };

    const resume = () => {
        started = Date.now();
        timer = window.setTimeout(() => closeToast(toast), Math.max(remaining, 2000));
    };

    toast.addEventListener('mouseenter', pause);
    toast.addEventListener('mouseleave', resume);
    toast.addEventListener('focusin', pause);
    toast.addEventListener('focusout', resume);
};

document.querySelectorAll('[data-vdbs-toast]').forEach(armToast);

document.addEventListener('click', (event) => {
    const close = event.target.closest('[data-vdbs-toast-close]');

    if (close) {
        closeToast(close.closest('[data-vdbs-toast]'));
    }
});

document.querySelectorAll('[data-vdbs-tooltip]').forEach((tooltip) => {
    const text = tooltip.querySelector('[role="tooltip"]');
    const target = tooltip.querySelector('a, button, input, select, textarea, [tabindex]');

    if (text && target && !target.hasAttribute('aria-describedby')) {
        target.setAttribute('aria-describedby', text.id);
    }

    ['mouseleave', 'focusout'].forEach((name) => tooltip.addEventListener(name, () => tooltip.classList.remove('vdbs-tooltip--dismissed')));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('[data-vdbs-tooltip]:hover, [data-vdbs-tooltip]:focus-within').forEach((tooltip) => tooltip.classList.add('vdbs-tooltip--dismissed'));
    }
});
