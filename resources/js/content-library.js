const copyText = async (value) => {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
};

const initCodeExample = (root) => {
    if (root.dataset.vdbsInitialized === 'true') {
        return;
    }

    root.dataset.vdbsInitialized = 'true';

    const button = root.querySelector('[data-vdbs-copy-code]');
    const source = root.querySelector('[data-vdbs-code-source]');
    const status = root.querySelector('[data-vdbs-copy-status]');
    const label = root.querySelector('[data-vdbs-copy-label]');

    button?.addEventListener('click', async () => {
        if (!source) {
            return;
        }

        try {
            await copyText(source.textContent ?? '');

            if (status) {
                status.textContent = 'Code kopiert.';
            }

            button.setAttribute('data-copy-state', 'copied');

            if (label) {
                label.textContent = 'Kopiert';
            }

            window.setTimeout(() => {
                button.removeAttribute('data-copy-state');

                if (label) {
                    label.textContent = 'Code kopieren';
                }

                if (status) {
                    status.textContent = '';
                }
            }, 1800);
        } catch {
            if (status) {
                status.textContent = 'Code konnte nicht kopiert werden.';
            }
        }
    });
};

const bootContentLibrary = () => {
    document
        .querySelectorAll('[data-vdbs-code-example]')
        .forEach(initCodeExample);
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        bootContentLibrary,
    );
} else {
    bootContentLibrary();
}

document.addEventListener(
    'livewire:navigated',
    bootContentLibrary,
);
