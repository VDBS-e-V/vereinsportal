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

    const submenuRoots = Array.from(
        header.querySelectorAll('[data-vdbs-submenu]'),
    );
    const supportsHover = window.matchMedia(
        '(hover: hover) and (pointer: fine)',
    ).matches;

    const closeOtherSubmenus = (currentRoot) => {
        submenuRoots.forEach((otherRoot) => {
            if (otherRoot === currentRoot) {
                return;
            }

            closePanel(
                otherRoot.querySelector('[data-vdbs-submenu-trigger]'),
                otherRoot.querySelector('[data-vdbs-submenu-panel]'),
            );
        });
    };

    submenuRoots.forEach((root) => {
        const trigger = root.querySelector('[data-vdbs-submenu-trigger]');
        const panel = root.querySelector('[data-vdbs-submenu-panel]');
        let hoverCloseTimer = null;

        const clearHoverClose = () => {
            if (hoverCloseTimer === null) {
                return;
            }

            window.clearTimeout(hoverCloseTimer);
            hoverCloseTimer = null;
        };

        const openSubmenu = () => {
            clearHoverClose();
            closeOtherSubmenus(root);
            openPanel(trigger, panel);
        };

        const closeSubmenu = () => {
            clearHoverClose();
            closePanel(trigger, panel);
        };

        trigger?.addEventListener('click', (event) => {
            event.stopPropagation();

            if (trigger.getAttribute('aria-expanded') === 'true') {
                closeSubmenu();
            } else {
                openSubmenu();
            }
        });

        if (supportsHover) {
            root.addEventListener('mouseenter', openSubmenu);
            root.addEventListener('mouseleave', () => {
                hoverCloseTimer = window.setTimeout(
                    closeSubmenu,
                    140,
                );
            });
        }

        root.addEventListener('focusin', openSubmenu);
        root.addEventListener('focusout', (event) => {
            if (!root.contains(event.relatedTarget)) {
                closeSubmenu();
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) {
            closePanel(accountTrigger, accountPanel);

            submenuRoots.forEach((root) => {
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

        submenuRoots.forEach((root) => {
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
