/*
 * Mehrstufiges Formular (Paket "forms"): Ein Formular mit [data-vdbs-wizard] und Schritten [data-vdbs-step] zeigt mit JavaScript einen Schritt
 * nach dem anderen. Ohne JavaScript stehen alle Schritte untereinander und das Formular wird in einem Zug abgeschickt.
 *   [data-vdbs-step-next] / [data-vdbs-step-prev]   weiter prüft die Pflichtfelder des aktuellen Schritts
 *   [data-vdbs-review-label="Bezeichnung"]          Felder (Eingabe, Auswahl, Checkbox) in der Zusammenfassung [data-vdbs-review-list]
 * Nach einem Serverfehler öffnet sich der erste Schritt mit einem fehlerhaften Feld.
 */

document.querySelectorAll('[data-vdbs-wizard]').forEach((form) => {
    const steps = [...form.querySelectorAll('[data-vdbs-step]')];

    if (steps.length < 2) {
        return;
    }

    let current = 0;

    const review = () => {
        const list = form.querySelector('[data-vdbs-review-list]');

        if (!list) {
            return;
        }

        list.replaceChildren();

        form.querySelectorAll('[data-vdbs-review-label]').forEach((field) => {
            let value = '';

            if (field.type === 'checkbox') {
                value = field.checked ? 'Ja' : 'Nein';
            } else if (field.tagName === 'SELECT') {
                value = field.selectedIndex >= 0 && field.value ? field.options[field.selectedIndex].text : '';
            } else {
                value = field.value.trim();
            }

            const term = document.createElement('dt');
            const detail = document.createElement('dd');

            term.textContent = field.dataset.vdbsReviewLabel;
            detail.textContent = value || '–';
            list.append(term, detail);
        });
    };

    const show = (index) => {
        current = Math.max(0, Math.min(steps.length - 1, index));
        steps.forEach((step, i) => { step.hidden = i !== current; });
        review();
        steps[current].querySelector('legend, h2, h3')?.setAttribute('tabindex', '-1');
        steps[current].querySelector('legend, h2, h3')?.focus({ preventScroll: false });
    };

    const valid = (step) => {
        const fields = [...step.querySelectorAll('input, select, textarea')].filter((field) => !field.disabled && field.type !== 'hidden');

        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();

                return false;
            }
        }

        return true;
    };

    form.addEventListener('click', (event) => {
        if (event.target.closest('[data-vdbs-step-next]')) {
            if (valid(steps[current])) {
                show(current + 1);
            }
        } else if (event.target.closest('[data-vdbs-step-prev]')) {
            show(current - 1);
        }
    });

    form.addEventListener('input', review);

    const failed = form.querySelector('.vdbs-field--error, .vdbs-choice--error');

    show(failed ? Math.max(0, steps.findIndex((step) => step.contains(failed))) : 0);
});
