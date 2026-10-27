// VDBS Portal — manueller Hell/Dunkel-Umschalter.
//
// Persistiert die Wahl in localStorage (vdbs-theme: "light" | "dark") und
// setzt data-theme auf <html>. Ohne gespeicherte Wahl greift automatisch
// prefers-color-scheme (reines CSS, s. tokens.css) — der Button zeigt den
// aktuell wirksamen Zustand unabhaengig davon, ob er von einer manuellen
// Wahl oder von der Systemeinstellung kommt.
const STORAGE_KEY = 'vdbs-theme';

const prefersDark = () =>
    window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;

const getStoredTheme = () => {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return stored === 'light' || stored === 'dark' ? stored : null;
    } catch {
        return null;
    }
};

const storeTheme = (theme) => {
    try {
        if (theme === null) {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, theme);
        }
    } catch {
        // Speicher nicht verfuegbar (z. B. privater Modus) — Umschalter
        // bleibt fuer die laufende Seitenansicht trotzdem funktional, die
        // Wahl geht beim naechsten Laden nur wieder auf prefers-color-scheme
        // zurueck.
    }
};

const effectiveTheme = () => getStoredTheme() ?? (prefersDark() ? 'dark' : 'light');

const applyTheme = (theme) => {
    if (theme === null) {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
};

const initThemeToggles = () => {
    document.querySelectorAll('[data-vdbs-theme-toggle]').forEach((button) => {
        if (button.dataset.vdbsInitialized === 'true') {
            return;
        }

        button.dataset.vdbsInitialized = 'true';

        const syncPressedState = () => {
            button.setAttribute(
                'aria-pressed',
                effectiveTheme() === 'dark' ? 'true' : 'false',
            );
        };

        syncPressedState();

        button.addEventListener('click', () => {
            const next = effectiveTheme() === 'dark' ? 'light' : 'dark';

            applyTheme(next);
            storeTheme(next);
            syncPressedState();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeToggles);
} else {
    initThemeToggles();
}

document.addEventListener('livewire:navigated', initThemeToggles);
