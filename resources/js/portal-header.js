const closePanel = (trigger, panel) => {
    trigger?.setAttribute('aria-expanded', 'false');

    if (panel) {
        panel.hidden = true;
    }
};

const openPanel = (trigger, panel) => {
    trigger?.setAttribute('aria-expanded', 'true');

    if (panel) {
        panel.hidden = false;
    }
};

const initPortalHeader = (header) => {
    if (header.dataset.vdbsInitialized === 'true') {
        return;
    }

    header.dataset.vdbsInitialized = 'true';

    let lastScrollY = window.scrollY;
    let ticking = false;

    const updateScrollState = () => {
        const currentScrollY = window.scrollY;
        const delta = currentScrollY - lastScrollY;

        header.classList.toggle('is-scrolled', currentScrollY > 8);

        if (currentScrollY <= 16) {
            header.classList.remove('is-compact');
        } else if (delta > 5) {
            header.classList.add('is-compact');
        } else if (delta < -5) {
            header.classList.remove('is-compact');
        }

        lastScrollY = currentScrollY;
        ticking = false;
    };

    if (!header.classList.contains('vdbs-portal-header--preview')) {
        window.addEventListener(
            'scroll',
            () => {
                if (ticking) {
                    return;
                }

                ticking = true;
                window.requestAnimationFrame(updateScrollState);
            },
            { passive: true },
        );
    }

    const mobileTrigger = header.querySelector('[data-vdbs-mobile-menu-trigger]');
    const mobilePanel = header.querySelector('[data-vdbs-mobile-menu]');

    mobileTrigger?.addEventListener('click', () => {
        const opening = mobileTrigger.getAttribute('aria-expanded') !== 'true';

        if (opening) {
            openPanel(mobileTrigger, mobilePanel);
            header.classList.add('is-mobile-menu-open');
            document.body.classList.add('vdbs-mobile-menu-open');
        } else {
            closePanel(mobileTrigger, mobilePanel);
            header.classList.remove('is-mobile-menu-open');
            document.body.classList.remove('vdbs-mobile-menu-open');
        }
    });

    const accountRoot = header.querySelector('[data-vdbs-account-menu]');
    const accountTrigger = accountRoot?.querySelector('[data-vdbs-account-trigger]');
    const accountPanel = accountRoot?.querySelector('[data-vdbs-account-panel]');

    accountTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();

        if (accountTrigger.getAttribute('aria-expanded') === 'true') {
            closePanel(accountTrigger, accountPanel);
        } else {
            openPanel(accountTrigger, accountPanel);
        }
    });

    header.querySelectorAll('[data-vdbs-submenu]').forEach((root) => {
        const trigger = root.querySelector('[data-vdbs-submenu-trigger]');
        const panel = root.querySelector('[data-vdbs-submenu-panel]');

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();

            header.querySelectorAll('[data-vdbs-submenu]').forEach((otherRoot) => {
                if (otherRoot === root) {
                    return;
                }

                closePanel(
                    otherRoot.querySelector('[data-vdbs-submenu-trigger]'),
                    otherRoot.querySelector('[data-vdbs-submenu-panel]'),
                );
            });

            if (trigger.getAttribute('aria-expanded') === 'true') {
                closePanel(trigger, panel);
            } else {
                openPanel(trigger, panel);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) {
            closePanel(accountTrigger, accountPanel);

            header.querySelectorAll('[data-vdbs-submenu]').forEach((root) => {
                closePanel(
                    root.querySelector('[data-vdbs-submenu-trigger]'),
                    root.querySelector('[data-vdbs-submenu-panel]'),
                );
            });
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closePanel(accountTrigger, accountPanel);

        header.querySelectorAll('[data-vdbs-submenu]').forEach((root) => {
            closePanel(
                root.querySelector('[data-vdbs-submenu-trigger]'),
                root.querySelector('[data-vdbs-submenu-panel]'),
            );
        });

        closePanel(mobileTrigger, mobilePanel);
        header.classList.remove('is-mobile-menu-open');
        document.body.classList.remove('vdbs-mobile-menu-open');
    });
};

const bootPortalHeaders = () => {
    document.querySelectorAll('[data-vdbs-portal-header]').forEach(initPortalHeader);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPortalHeaders);
} else {
    bootPortalHeaders();
}

document.addEventListener('livewire:navigated', bootPortalHeaders);
