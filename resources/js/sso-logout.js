/*
 * Abmeldung bei angebundenen Systemen (Front-Channel Logout): Für jedes Listenelement mit data-sso-logout wird die Abmelde-Adresse in einem
 * unsichtbaren Rahmen aufgerufen. Der Browser sendet dabei die Anmeldung des Systems mit, sodass es abmeldet. Ob das System die Abmeldung tatsächlich
 * durchführt, kann der Browser nicht sehen; der Haken heißt „aufgerufen“. Ohne JavaScript übernimmt <noscript> in der Seite das Aufrufen.
 */

const items = Array.from(document.querySelectorAll('[data-sso-logout]'));

items.forEach((item) => {
    const state = item.querySelector('.sso-services__state');
    const frame = document.createElement('iframe');
    let finished = false;

    const finish = (done) => {
        if (finished) {
            return;
        }

        finished = true;

        if (state) {
            state.dataset.state = done ? 'done' : 'unknown';
            state.textContent = done ? '✔' : 'keine Rückmeldung';
            state.setAttribute('aria-label', done ? 'abgemeldet' : 'keine Rückmeldung');
        }
    };

    frame.className = 'sso-frames';
    frame.tabIndex = -1;
    frame.setAttribute('title', 'Abmeldung');
    frame.addEventListener('load', () => finish(true));
    frame.addEventListener('error', () => finish(false));
    frame.src = item.dataset.ssoLogout;
    document.body.appendChild(frame);

    window.setTimeout(() => finish(false), 8000);
});

// Zustimmungsseite: „Alles einblenden“ öffnet oder schließt alle Abschnitte.
document.querySelectorAll('[data-sso-expand]').forEach((button) => {
    button.addEventListener('click', () => {
        const sections = Array.from(document.querySelectorAll('details.sso-section'));
        const open = sections.some((section) => !section.open);

        sections.forEach((section) => { section.open = open; });
        button.textContent = open ? 'Alles ausblenden' : 'Alles einblenden';
    });
});
