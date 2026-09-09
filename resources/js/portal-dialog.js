const dialogReturnFocus = new WeakMap();

const getDialogById = (id) => {
    const dialog = document.getElementById(id);

    return dialog instanceof HTMLDialogElement
        ? dialog
        : null;
};

document.addEventListener('click', (event) => {
    const openTrigger = event.target.closest('[data-vdbs-dialog-open]');

    if (openTrigger) {
        const dialog = getDialogById(
            openTrigger.getAttribute('data-vdbs-dialog-open'),
        );

        if (!dialog || dialog.open) {
            return;
        }

        dialogReturnFocus.set(dialog, openTrigger);
        dialog.showModal();

        return;
    }

    const closeTrigger = event.target.closest('[data-vdbs-dialog-close]');

    if (closeTrigger) {
        const dialog = closeTrigger.closest('dialog[data-vdbs-dialog]');

        if (dialog instanceof HTMLDialogElement) {
            dialog.close();
        }

        return;
    }

    const dialog = event.target.closest('dialog[data-vdbs-dialog]');

    if (
        dialog instanceof HTMLDialogElement
        && event.target === dialog
    ) {
        dialog.close();
    }
});

document.addEventListener(
    'close',
    (event) => {
        const dialog = event.target;

        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }

        const returnFocus = dialogReturnFocus.get(dialog);

        if (returnFocus instanceof HTMLElement) {
            returnFocus.focus();
        }

        dialogReturnFocus.delete(dialog);
    },
    true,
);
