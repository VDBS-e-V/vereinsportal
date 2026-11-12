/*
 * In die Zwischenablage kopieren: [data-vdbs-copy="Text"] (Knopf oder Menüeintrag).
 * Meldet „Kopiert“ für Screenreader (Live-Region) und kurz sichtbar am Knopf bzw. am Knopf des Aufklappmenüs, das sich dabei schließt.
 */

let liveRegion = null;

const announce = (message) => {
    if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.className = 'vdbs-sr-only';
        liveRegion.setAttribute('role', 'status');
        document.body.appendChild(liveRegion);
    }

    liveRegion.textContent = message;
    window.setTimeout(() => { liveRegion.textContent = ''; }, 3000);
};

const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        const area = document.createElement('textarea');

        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();

        const done = document.execCommand?.('copy') ?? false;

        area.remove();

        return done;
    }
};

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest?.('[data-vdbs-copy]');

    if (!trigger) {
        return;
    }

    const done = await copyText(trigger.dataset.vdbsCopy);
    const message = done ? 'Kopiert' : 'Kopieren nicht möglich';
    const details = trigger.closest('details[data-vdbs-dropdown]');
    const shown = details?.querySelector('summary') ?? trigger;

    announce(message);
    shown.classList.add('is-copied');
    shown.dataset.vdbsCopyMessage = message;
    window.setTimeout(() => {
        shown.classList.remove('is-copied');
        delete shown.dataset.vdbsCopyMessage;
    }, 2000);

    if (details) {
        details.removeAttribute('open');
        details.querySelector('summary')?.focus();
    }
});
