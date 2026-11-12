/*
 * Kanban-Tafel: Karten per Ziehen und Ablegen in eine andere Spalte oder an eine andere Stelle verschieben.
 * Ohne JavaScript (und per Tastatur) geht das Verschieben über „Verschieben“ an jeder Karte.
 */
(function () {
    var board = document.querySelector('[data-kanban]');

    if (!board) {
        return;
    }

    var token = board.getAttribute('data-token');
    var dragged = null;

    function cardsOf(list) {
        return Array.prototype.slice.call(list.querySelectorAll('[data-task-id]'));
    }

    function refreshCounts() {
        board.querySelectorAll('[data-column]').forEach(function (column) {
            var count = column.querySelector('[data-count]');

            if (count) {
                count.textContent = String(cardsOf(column.querySelector('[data-list]')).length);
            }
        });
    }

    board.addEventListener('dragstart', function (event) {
        var card = event.target.closest('[data-task-id]');

        if (!card) {
            return;
        }

        dragged = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.getAttribute('data-task-id'));
    });

    board.addEventListener('dragend', function () {
        if (dragged) {
            dragged.classList.remove('is-dragging');
        }

        board.querySelectorAll('.is-over').forEach(function (column) {
            column.classList.remove('is-over');
        });
        dragged = null;
    });

    board.addEventListener('dragover', function (event) {
        var column = event.target.closest('[data-column]');

        if (!column || !dragged) {
            return;
        }

        event.preventDefault();
        board.querySelectorAll('.is-over').forEach(function (other) {
            if (other !== column) {
                other.classList.remove('is-over');
            }
        });
        column.classList.add('is-over');

        var list = column.querySelector('[data-list]');
        var after = cardsOf(list).filter(function (card) {
            return card !== dragged;
        }).find(function (card) {
            var box = card.getBoundingClientRect();

            return event.clientY < box.top + box.height / 2;
        });

        if (after) {
            list.insertBefore(dragged, after);
        } else {
            list.appendChild(dragged);
        }
    });

    board.addEventListener('drop', function (event) {
        var column = event.target.closest('[data-column]');

        if (!column || !dragged) {
            return;
        }

        event.preventDefault();

        var card = dragged;
        var list = column.querySelector('[data-list]');
        var index = cardsOf(list).indexOf(card);

        refreshCounts();

        fetch(card.getAttribute('data-move-url'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ status_id: Number(column.getAttribute('data-status-id')), index: index }),
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                window.location.reload();
            }
        }).catch(function () {
            window.location.reload();
        });
    });
})();
