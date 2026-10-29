/*
 * Navigations-Helfer (Paket "navigation"). Alles ist Verbesserung: ohne JavaScript bleiben Tabs untereinander
 * und Aufklappmenüs (details) nutzbar.
 *  - Tabs mit Inhalt   [data-vdbs-tabs] > [data-vdbs-tab]   Pfeiltasten, Pos1/Ende, #id wählt den Tab
 *  - Aufklappmenü      [data-vdbs-dropdown]                 Esc und Klick daneben schließen
 */

const initTabs = (root) => {
    const panels = [...root.querySelectorAll(':scope > [data-vdbs-tab]')];

    if (panels.length === 0) {
        return;
    }

    const list = document.createElement('div');
    list.className = 'vdbs-tabs__list';
    list.setAttribute('role', 'tablist');
    list.setAttribute('aria-label', root.dataset.label ?? '');

    const tabs = panels.map((panel) => {
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.className = 'vdbs-tabs__tab';
        tab.id = `${panel.id}-tab`;
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', panel.id);
        tab.textContent = panel.dataset.label;

        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', tab.id);
        panel.tabIndex = 0;

        list.append(tab);

        return tab;
    });

    const select = (index, focus = false) => {
        tabs.forEach((tab, i) => {
            const active = i === index;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
            panels[i].hidden = !active;
        });

        if (focus) {
            tabs[index].focus();
        }
    };

    list.addEventListener('click', (event) => {
        const index = tabs.indexOf(event.target.closest('[role="tab"]'));

        if (index >= 0) {
            select(index);
        }
    });

    list.addEventListener('keydown', (event) => {
        const current = tabs.indexOf(document.activeElement);
        const last = tabs.length - 1;
        const next = { ArrowRight: current === last ? 0 : current + 1, ArrowLeft: current === 0 ? last : current - 1, Home: 0, End: last }[event.key];

        if (current >= 0 && next !== undefined) {
            event.preventDefault();
            select(next, true);
        }
    });

    root.prepend(list);
    root.classList.add('vdbs-tabs--enhanced');

    const fromHash = panels.findIndex((panel) => `#${panel.id}` === window.location.hash);
    const fromData = panels.findIndex((panel) => panel.id === root.dataset.active);

    select(Math.max(fromHash, fromData, 0));
};

document.querySelectorAll('[data-vdbs-tabs]').forEach(initTabs);

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-vdbs-dropdown][open]').forEach((dropdown) => {
        if (!dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-vdbs-dropdown][open]').forEach((dropdown) => {
        dropdown.removeAttribute('open');
        dropdown.querySelector('summary')?.focus();
    });
});
