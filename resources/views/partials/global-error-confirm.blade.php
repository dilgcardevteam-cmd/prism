{{-- Global runtime error handling + modal-based confirmation prompts --}}
<style>
    .app-global-toast-stack {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 11000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: min(420px, calc(100vw - 32px));
        pointer-events: none;
    }

    .app-global-toast {
        pointer-events: auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.16);
        font-size: 13px;
        line-height: 1.4;
        animation: app-global-toast-in 180ms ease-out;
    }

    .app-global-toast.success {
        background: #ecfdf3;
        border-color: #86efac;
        color: #14532d;
    }

    .app-global-toast.warning {
        background: #fff7e6;
        border-color: #fcd34d;
        color: #92400e;
    }

    .app-global-toast.error {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }

    .app-global-toast.info {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }

    .app-global-toast-body {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }

    .app-global-toast-icon {
        width: 18px;
        height: 18px;
        margin-top: 1px;
        flex: 0 0 18px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        background: currentColor;
        color: #ffffff;
    }

    .app-global-toast.warning .app-global-toast-icon {
        color: #92400e;
        background: #fed7aa;
    }

    .app-global-toast.error .app-global-toast-icon {
        color: #991b1b;
        background: #fecaca;
    }

    .app-global-toast.info .app-global-toast-icon {
        color: #1d4ed8;
        background: #dbeafe;
    }

    .app-global-toast-text {
        min-width: 0;
        flex: 1;
    }

    .app-global-toast-title {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.3;
    }

    .app-global-toast-message {
        margin-top: 2px;
        font-size: 12px;
        line-height: 1.45;
        opacity: 0.92;
    }

    .app-global-toast-close {
        background: transparent;
        border: 0;
        color: inherit;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
        padding: 0;
        margin-top: 1px;
    }

    .app-global-confirm-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 12000;
        padding: 16px;
    }

    .app-global-confirm-backdrop.is-open {
        display: flex;
    }

    .app-global-confirm-modal {
        width: min(460px, 100%);
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 20px 48px rgba(2, 6, 23, 0.25);
        padding: 18px;
    }

    .app-global-confirm-title {
        margin: 0 0 10px;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
    }

    .app-global-confirm-message {
        margin: 0;
        color: #374151;
        font-size: 14px;
        line-height: 1.5;
    }

    .app-global-confirm-actions {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .app-global-confirm-btn {
        border: 0;
        border-radius: 8px;
        padding: 9px 14px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
    }

    .app-global-confirm-btn.cancel {
        background: #e5e7eb;
        color: #111827;
    }

    .app-global-confirm-btn.ok {
        background: #1d4ed8;
        color: #ffffff;
    }

    @keyframes app-global-toast-in {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div id="appGlobalToastStack" class="app-global-toast-stack" aria-live="polite" aria-atomic="true"></div>

<div id="appGlobalConfirmBackdrop" class="app-global-confirm-backdrop" aria-hidden="true">
    <div class="app-global-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="appGlobalConfirmTitle" aria-describedby="appGlobalConfirmMessage">
        <h3 id="appGlobalConfirmTitle" class="app-global-confirm-title">Confirm Action</h3>
        <p id="appGlobalConfirmMessage" class="app-global-confirm-message"></p>
        <div class="app-global-confirm-actions">
            <button type="button" id="appGlobalConfirmCancel" class="app-global-confirm-btn cancel">Cancel</button>
            <button type="button" id="appGlobalConfirmOk" class="app-global-confirm-btn ok">Confirm</button>
        </div>
    </div>
</div>

<script>
    (function initAppGlobalUi() {
        if (window.__appGlobalUiReady) {
            return;
        }
        window.__appGlobalUiReady = true;

        const toastStack = document.getElementById('appGlobalToastStack');
        const confirmBackdrop = document.getElementById('appGlobalConfirmBackdrop');
        const confirmTitle = document.getElementById('appGlobalConfirmTitle');
        const confirmMessage = document.getElementById('appGlobalConfirmMessage');
        const confirmCancel = document.getElementById('appGlobalConfirmCancel');
        const confirmOk = document.getElementById('appGlobalConfirmOk');

        function normalizeMessage(message) {
            if (typeof message === 'string') {
                return message.trim();
            }
            if (message && typeof message.message === 'string') {
                return message.message.trim();
            }
            return 'An unexpected error occurred.';
        }

        function getToastMeta(type) {
            if (type === 'success') {
                return { title: 'Success', icon: '✓' };
            }
            if (type === 'warning') {
                return { title: 'Warning', icon: '!' };
            }
            if (type === 'info') {
                return { title: 'Info', icon: 'i' };
            }
            return { title: 'Error', icon: '!' };
        }

        function showToast(message, type, duration) {
            if (!toastStack) {
                return;
            }

            const toast = document.createElement('div');
            const safeType = type === 'success' || type === 'warning' || type === 'info' ? type : 'error';
            const timeout = Number.isFinite(duration) ? duration : (safeType === 'error' ? 6000 : 4200);
            const meta = getToastMeta(safeType);

            toast.className = 'app-global-toast ' + safeType;

            const body = document.createElement('div');
            body.className = 'app-global-toast-body';

            const icon = document.createElement('div');
            icon.className = 'app-global-toast-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = meta.icon;

            const text = document.createElement('div');
            text.className = 'app-global-toast-text';

            const title = document.createElement('div');
            title.className = 'app-global-toast-title';
            title.textContent = meta.title;

            const messageNode = document.createElement('div');
            messageNode.className = 'app-global-toast-message';
            messageNode.textContent = normalizeMessage(message);

            text.appendChild(title);
            text.appendChild(messageNode);
            body.appendChild(icon);
            body.appendChild(text);
            toast.appendChild(body);

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'app-global-toast-close';
            closeButton.setAttribute('aria-label', 'Dismiss message');
            closeButton.textContent = '×';
            closeButton.addEventListener('click', function() {
                toast.remove();
            });
            toast.appendChild(closeButton);

            toastStack.appendChild(toast);

            window.setTimeout(function() {
                toast.remove();
            }, timeout);
        }

        let confirmResolver = null;
        let lastActiveElement = null;

        function closeConfirm(result) {
            if (!confirmBackdrop) {
                return;
            }

            confirmBackdrop.classList.remove('is-open');
            confirmBackdrop.setAttribute('aria-hidden', 'true');

            const resolver = confirmResolver;
            confirmResolver = null;
            if (typeof resolver === 'function') {
                resolver(Boolean(result));
            }

            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
            lastActiveElement = null;
        }

        function showConfirm(message, options) {
            if (typeof window.openConfirmationModal === 'function') {
                return new Promise(function(resolve) {
                    window.openConfirmationModal(
                        normalizeMessage(message),
                        function() {
                            resolve(true);
                        },
                        function() {
                            resolve(false);
                        }
                    );
                });
            }

            if (!confirmBackdrop) {
                return Promise.resolve(window.confirm(normalizeMessage(message)));
            }

            if (confirmResolver) {
                closeConfirm(false);
            }

            const resolvedOptions = options || {};
            const titleText = typeof resolvedOptions.title === 'string' && resolvedOptions.title.trim() !== ''
                ? resolvedOptions.title.trim()
                : 'Confirm Action';
            const messageText = normalizeMessage(message);
            const confirmLabel = typeof resolvedOptions.confirmLabel === 'string' && resolvedOptions.confirmLabel.trim() !== ''
                ? resolvedOptions.confirmLabel.trim()
                : 'Confirm';
            const cancelLabel = typeof resolvedOptions.cancelLabel === 'string' && resolvedOptions.cancelLabel.trim() !== ''
                ? resolvedOptions.cancelLabel.trim()
                : 'Cancel';

            if (confirmTitle) {
                confirmTitle.textContent = titleText;
            }
            if (confirmMessage) {
                confirmMessage.textContent = messageText;
            }
            if (confirmOk) {
                confirmOk.textContent = confirmLabel;
            }
            if (confirmCancel) {
                confirmCancel.textContent = cancelLabel;
            }

            lastActiveElement = document.activeElement;
            confirmBackdrop.classList.add('is-open');
            confirmBackdrop.setAttribute('aria-hidden', 'false');

            window.setTimeout(function() {
                if (confirmOk && typeof confirmOk.focus === 'function') {
                    confirmOk.focus();
                }
            }, 0);

            return new Promise(function(resolve) {
                confirmResolver = resolve;
            });
        }

        if (confirmCancel) {
            confirmCancel.addEventListener('click', function() {
                closeConfirm(false);
            });
        }

        if (confirmOk) {
            confirmOk.addEventListener('click', function() {
                closeConfirm(true);
            });
        }

        if (confirmBackdrop) {
            confirmBackdrop.addEventListener('click', function(event) {
                if (event.target === confirmBackdrop) {
                    closeConfirm(false);
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && confirmBackdrop && confirmBackdrop.classList.contains('is-open')) {
                event.preventDefault();
                closeConfirm(false);
            }
        });

        const recentErrors = new Map();
        const duplicateWindowMs = 6000;

        function shouldDisplayError(message) {
            const now = Date.now();
            const key = normalizeMessage(message);
            const previous = recentErrors.get(key);
            recentErrors.set(key, now);
            if (typeof previous === 'number' && (now - previous) < duplicateWindowMs) {
                return false;
            }
            return true;
        }

        function showGlobalError(message) {
            if (!shouldDisplayError(message)) {
                return;
            }
            showToast(message, 'error');
        }

        window.AppUI = window.AppUI || {};
        window.AppUI.confirm = showConfirm;
        window.AppUI.error = showGlobalError;
        window.AppUI.toast = showToast;

        const initialFlashMessages = [];
        @if (session('success'))
            initialFlashMessages.push({ message: @json(session('success')), type: 'success' });
        @endif
        @if (session('warning'))
            initialFlashMessages.push({ message: @json(session('warning')), type: 'warning' });
        @endif
        @if (session('info'))
            initialFlashMessages.push({ message: @json(session('info')), type: 'info' });
        @endif
        @if (session('error'))
            initialFlashMessages.push({ message: @json(session('error')), type: 'error' });
        @endif
        @if ($errors->any())
            initialFlashMessages.push({ message: @json($errors->first()), type: 'error' });
        @endif

        initialFlashMessages.forEach(function(entry) {
            showToast(entry.message, entry.type);
        });

        window.addEventListener('error', function(event) {
            const message = (event && (event.message || (event.error && event.error.message))) || 'A script error occurred.';
            showGlobalError(message);
        });

        window.addEventListener('unhandledrejection', function(event) {
            const reason = event ? event.reason : null;
            const message = normalizeMessage(reason || 'An unhandled promise error occurred.');
            showGlobalError(message);
        });

        const defaultMessages = {
            save: 'Are you sure you want to save these changes?',
            update: 'Are you sure you want to update this record?',
            delete: 'Are you sure you want to delete this item? This action cannot be undone.',
        };

        function getActionText(el) {
            return ((el && (el.textContent || el.value)) || '').trim().toLowerCase();
        }

        function hasInlineConfirm(el) {
            if (!el) return false;
            const onclick = el.getAttribute ? (el.getAttribute('onclick') || '') : '';
            if (onclick.includes('confirm(')) {
                return true;
            }
            const form = el.closest ? el.closest('form') : null;
            const onsubmit = form ? (form.getAttribute('onsubmit') || '') : '';
            return onsubmit.includes('confirm(');
        }

        function needsAutoConfirm(el) {
            if (!el || el.disabled) return false;
            if (el.dataset && el.dataset.confirmSkip === 'true') return false;
            const form = el.closest ? el.closest('form') : null;
            if (form && form.dataset && form.dataset.confirmSkip === 'true') return false;
            if (form && form.dataset && form.dataset.confirmSkipOnce === 'true') {
                delete form.dataset.confirmSkipOnce;
                return false;
            }
            if (el.dataset && el.dataset.confirm) return true;
            if (hasInlineConfirm(el)) return false;

            const text = getActionText(el);
            if (!text) return false;

            return text.includes('save') || text.includes('update') || text.includes('delete');
        }

        function resolveMessage(el) {
            if (el && el.dataset && el.dataset.confirm) {
                return el.dataset.confirm;
            }
            const text = getActionText(el);
            if (text.includes('delete')) {
                return defaultMessages.delete;
            }
            if (text.includes('update')) {
                return defaultMessages.update;
            }
            return defaultMessages.save;
        }

        function runConfirmedAction(target) {
            if (!target) {
                return;
            }

            const tag = target.tagName ? target.tagName.toLowerCase() : '';
            const form = target.closest ? target.closest('form') : null;

            if (tag === 'a') {
                const href = target.getAttribute('href');
                if (href && href !== '#') {
                    window.location.href = href;
                }
                return;
            }

            const rawType = (target.getAttribute && target.getAttribute('type')) || target.type || '';
            const resolvedType = String(rawType).toLowerCase();
            const isSubmitControl = (tag === 'button' && (resolvedType === '' || resolvedType === 'submit'))
                || (tag === 'input' && resolvedType === 'submit');
            if (form && isSubmitControl) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(target);
                } else {
                    form.submit();
                }
                return;
            }

            if (typeof target.click === 'function') {
                target.click();
            }
        }

        if (typeof window.openConfirmationModal !== 'function') {
            document.addEventListener('click', function(event) {
                const target = event.target && event.target.closest
                    ? event.target.closest('button, input[type="submit"], input[type="button"], a')
                    : null;

                if (!target) {
                    return;
                }

                if (target.dataset && target.dataset.appConfirmed === 'true') {
                    delete target.dataset.appConfirmed;
                    return;
                }

                if (!needsAutoConfirm(target)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                showConfirm(resolveMessage(target)).then(function(confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    if (target.dataset) {
                        target.dataset.appConfirmed = 'true';
                    }
                    runConfirmedAction(target);
                });
            }, true);

            document.addEventListener('submit', function(event) {
                const form = event.target;
                const submitter = event.submitter || null;

                if (submitter && submitter.dataset && submitter.dataset.appConfirmed === 'true') {
                    delete submitter.dataset.appConfirmed;
                    return;
                }

                const candidate = submitter || (form && form.querySelector ? form.querySelector('button[type="submit"], input[type="submit"]') : null);
                if (!needsAutoConfirm(candidate)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                showConfirm(resolveMessage(candidate)).then(function(confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    if (submitter && submitter.dataset) {
                        submitter.dataset.appConfirmed = 'true';
                    }

                    if (submitter && typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter);
                    } else {
                        form.submit();
                    }
                });
            }, true);
        }

    })();
</script>
