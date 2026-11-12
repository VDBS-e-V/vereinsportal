/*
 * Kanban-Tafel (Designsystem, Paket „Daten“): Karten per Ziehen und Ablegen in eine andere Spalte oder an eine andere Stelle verschieben.
 *
 * Markup: [data-vdbs-kanban] (mit data-token = CSRF-Token) enthält [data-vdbs-kanban-column] (mit data-status-id) mit [data-vdbs-kanban-list]
 * und darin Karten [data-vdbs-kanban-card] mit data-move-url. Beim Ablegen geht ein POST mit { status_id, index } an die Adresse der Karte;
 * schlägt er fehl, lädt die Seite neu und zeigt den echten Stand. Ohne JavaScript und per Tastatur bleibt das „Verschieben“-Formular der Karte.
 */

const boards = document.querySelectorAll('[data-vdbs-kanban]');

boards.forEach((board) => {
    const token = board.getAttribute('data-token') || '';
    let dragged = null;

    const listOf = (column) => column.querySelector('[data-vdbs-kanban-list]');
    const cardsOf = (list) => Array.from(list.querySelectorAll('[data-vdbs-kanban-card]'));

    const refreshCounts = () => {
        board.querySelectorAll('[data-vdbs-kanban-column]').forEach((column) => {
            const count = column.querySelector('[data-vdbs-kanban-count]');

            if (count) {
                count.textContent = String(cardsOf(listOf(column)).length);
            }
        });
    };

    board.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-vdbs-kanban-card][data-move-url]');

        if (!card) {
            return;
        }

        dragged = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.getAttribute('data-move-url'));
    });

    board.addEventListener('dragend', () => {
        dragged?.classList.remove('is-dragging');
        board.querySelectorAll('.is-over').forEach((column) => column.classList.remove('is-over'));
        dragged = null;
    });

    board.addEventListener('dragover', (event) => {
        const column = event.target.closest('[data-vdbs-kanban-column]');

        if (!column || !dragged) {
            return;
        }

        event.preventDefault();
        board.querySelectorAll('.is-over').forEach((other) => other !== column && other.classList.remove('is-over'));
        column.classList.add('is-over');

        const list = listOf(column);
        const after = cardsOf(list).filter((card) => card !== dragged).find((card) => {
            const box = card.getBoundingClientRect();

            return event.clientY < box.top + box.height / 2;
        });

        if (after) {
            list.insertBefore(dragged, after);
        } else {
            list.appendChild(dragged);
        }
    });

    board.addEventListener('drop', (event) => {
        const column = event.target.closest('[data-vdbs-kanban-column]');

        if (!column || !dragged) {
            return;
        }

        event.preventDefault();

        const card = dragged;
        const index = cardsOf(listOf(column)).indexOf(card);

        refreshCounts();

        fetch(card.getAttribute('data-move-url'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ status_id: Number(column.getAttribute('data-status-id')), index }),
            credentials: 'same-origin',
        }).then((response) => {
            if (!response.ok) {
                window.location.reload();
            }
        }).catch(() => window.location.reload());
    });
});
