<style>
    .app-dialog {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 9999;
    }
    .app-dialog.is-open {
        opacity: 1;
        pointer-events: auto;
    }
    .app-dialog__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
    }
    .app-dialog__panel {
        position: relative;
        width: min(92vw, 460px);
        border-radius: 16px;
        background: var(--color-card);
        color: var(--color-text);
        border: 1px solid var(--color-border);
        padding: 20px 22px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
        transform: translateY(8px);
        transition: transform 0.2s ease;
    }
    .app-dialog.is-open .app-dialog__panel {
        transform: translateY(0);
    }
    .app-dialog__title {
        margin: 0 0 8px;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .app-dialog__message {
        margin: 0 0 18px;
        line-height: 1.5;
        opacity: 0.82;
        white-space: pre-line;
    }
    .app-dialog__actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .app-dialog__button {
        border-radius: 10px;
        border: 1px solid var(--color-border);
        background: transparent;
        color: inherit;
        padding: 8px 14px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .app-dialog__button:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
    }
    .app-dialog__button--primary {
        background: var(--color-primary);
        color: #ffffff;
        border-color: transparent;
    }
    .app-dialog__button.is-hidden {
        display: none;
    }
    body.app-dialog-open {
        overflow: hidden;
    }
</style>

<div id="app-dialog" class="app-dialog" aria-hidden="true">
    <div class="app-dialog__backdrop" data-dialog-backdrop></div>
    <div class="app-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title" aria-describedby="app-dialog-message">
        <h3 id="app-dialog-title" class="app-dialog__title" data-dialog-title></h3>
        <div id="app-dialog-message" class="app-dialog__message" data-dialog-message></div>
        <div class="app-dialog__actions">
            <button class="app-dialog__button" type="button" data-dialog-cancel>Cancelar</button>
            <button class="app-dialog__button app-dialog__button--primary" type="button" data-dialog-confirm>Confirmar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const dialog = document.getElementById('app-dialog');
        if (!dialog) {
            return;
        }

        const titleEl = dialog.querySelector('[data-dialog-title]');
        const messageEl = dialog.querySelector('[data-dialog-message]');
        const cancelBtn = dialog.querySelector('[data-dialog-cancel]');
        const confirmBtn = dialog.querySelector('[data-dialog-confirm]');
        const backdrop = dialog.querySelector('[data-dialog-backdrop]');

        let resolver = null;
        let currentMode = 'confirm';
        let lastActiveElement = null;

        const closeDialog = (result) => {
            dialog.classList.remove('is-open');
            dialog.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('app-dialog-open');

            if (resolver) {
                resolver(result);
                resolver = null;
            }

            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
        };

        const openDialog = ({ title, message, confirmText, cancelText, mode }) => {
            currentMode = mode || 'confirm';
            lastActiveElement = document.activeElement;

            titleEl.textContent = title || (currentMode === 'alert' ? 'Aviso' : 'Confirmacao');
            messageEl.textContent = message || '';
            confirmBtn.textContent = confirmText || (currentMode === 'alert' ? 'OK' : 'Confirmar');
            cancelBtn.textContent = cancelText || 'Cancelar';

            if (currentMode === 'alert') {
                cancelBtn.classList.add('is-hidden');
            } else {
                cancelBtn.classList.remove('is-hidden');
            }

            dialog.classList.add('is-open');
            dialog.setAttribute('aria-hidden', 'false');
            document.body.classList.add('app-dialog-open');
            confirmBtn.focus();

            return new Promise((resolve) => {
                resolver = resolve;
            });
        };

        const onAccept = () => closeDialog(true);
        const onCancel = () => closeDialog(currentMode === 'alert');

        confirmBtn.addEventListener('click', onAccept);
        cancelBtn.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);

        document.addEventListener('keydown', (event) => {
            if (!dialog.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDialog(false);
            }
        });

        window.AppDialog = {
            alert: (message, options = {}) => openDialog({ ...options, message, mode: 'alert' }),
            confirm: (message, options = {}) => openDialog({ ...options, message, mode: 'confirm' }),
        };
        window.appAlert = window.AppDialog.alert;
        window.appConfirm = window.AppDialog.confirm;

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form || form.nodeName !== 'FORM') {
                return;
            }

            const message = form.dataset.confirm;
            if (!message) {
                return;
            }

            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = '';
                return;
            }

            event.preventDefault();
            window.AppDialog.confirm(message, {
                title: form.dataset.confirmTitle || undefined,
                confirmText: form.dataset.confirmButton || undefined,
                cancelText: form.dataset.cancelButton || undefined,
            }).then((confirmed) => {
                if (!confirmed) {
                    return;
                }

                form.dataset.confirmed = 'true';
                form.submit();
            });
        });
    })();
</script>
