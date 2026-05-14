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
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.22);
        font-size: 13px;
        line-height: 1.4;
        animation: app-global-toast-in 180ms ease-out;
    }

    .app-global-toast.error {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .app-global-toast.success {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .app-global-toast.info {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1e3a8a;
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

        function showToast(message, type, duration) {
            if (!toastStack) {
                return;
            }

            const toast = document.createElement('div');
            const safeType = type === 'success' || type === 'info' ? type : 'error';
            const timeout = Number.isFinite(duration) ? duration : (safeType === 'error' ? 6000 : 4000);

            toast.className = 'app-global-toast ' + safeType;

            const text = document.createElement('div');
            text.textContent = normalizeMessage(message);
            toast.appendChild(text);

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

        function formatFileSizeFromKilobytes(maxKilobytes) {
            const kilobytes = Number(maxKilobytes);
            if (!Number.isFinite(kilobytes) || kilobytes <= 0) {
                return '';
            }

            if (kilobytes >= 1024) {
                const megabytes = kilobytes / 1024;
                const roundedMegabytes = Math.round(megabytes * 100) / 100;
                return Number.isInteger(roundedMegabytes)
                    ? `${roundedMegabytes} MB`
                    : `${roundedMegabytes.toFixed(2).replace(/\.?0+$/, '')} MB`;
            }

            return `${kilobytes} KB`;
        }

        function resolveFileInputLabel(input) {
            if (!input) {
                return 'This upload';
            }

            const escapeSelector = function(value) {
                if (window.CSS && typeof window.CSS.escape === 'function') {
                    return window.CSS.escape(value);
                }

                return String(value).replace(/["\\]/g, '\\$&');
            };

            const explicitLabel = (input.getAttribute('data-file-label') || '').trim();
            if (explicitLabel !== '') {
                return explicitLabel;
            }

            if (input.id) {
                const label = document.querySelector(`label[for="${escapeSelector(input.id)}"]`);
                const labelText = label ? (label.textContent || '').trim() : '';
                if (labelText !== '') {
                    return labelText.replace(/\s+/g, ' ');
                }

                const controller = document.querySelector(`[aria-controls="${escapeSelector(input.id)}"]`);
                const controllerText = controller
                    ? ((controller.getAttribute('aria-label') || controller.getAttribute('title') || controller.textContent || '').trim())
                    : '';
                if (controllerText !== '') {
                    return controllerText.replace(/\s+/g, ' ');
                }
            }

            let previousSibling = input.previousElementSibling;
            while (previousSibling) {
                if (previousSibling.tagName === 'LABEL') {
                    const siblingLabelText = (previousSibling.textContent || '').trim();
                    if (siblingLabelText !== '') {
                        return siblingLabelText.replace(/\s+/g, ' ');
                    }
                }
                previousSibling = previousSibling.previousElementSibling;
            }

            const parentLabel = input.parentElement ? input.parentElement.querySelector('label') : null;
            const parentLabelText = parentLabel ? (parentLabel.textContent || '').trim() : '';
            if (parentLabelText !== '') {
                return parentLabelText.replace(/\s+/g, ' ');
            }

            const ariaLabel = (input.getAttribute('aria-label') || '').trim();
            if (ariaLabel !== '') {
                return ariaLabel;
            }

            const name = (input.getAttribute('name') || '').trim();
            if (name !== '') {
                return name.replace(/\[\]$/, '').replace(/[_-]+/g, ' ');
            }

            return 'This upload';
        }

        function notifyFileTooLarge(input, file, maxKilobytes) {
            const limitLabel = formatFileSizeFromKilobytes(maxKilobytes) || 'the allowed size';
            const fieldLabel = resolveFileInputLabel(input);
            const fileName = file && typeof file.name === 'string' && file.name.trim() !== ''
                ? `"${file.name.trim()}"`
                : 'The selected file';

            showToast(`${fileName} exceeds the ${limitLabel} file size limit for ${fieldLabel}.`, 'error', 6500);
        }

        function initializeFileSizeValidation() {
            const initializedAttribute = 'data-app-file-size-check-ready';

            const bindInput = function(input) {
                if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
                    return;
                }

                const maxKilobytes = Number(input.getAttribute('data-max-size-kb'));
                if (!Number.isFinite(maxKilobytes) || maxKilobytes <= 0) {
                    return;
                }

                if (input.hasAttribute(initializedAttribute)) {
                    return;
                }

                const limitLabel = formatFileSizeFromKilobytes(maxKilobytes);
                if (limitLabel !== '') {
                    const currentTitle = (input.getAttribute('title') || '').trim();
                    if (!/maximum file size/i.test(currentTitle)) {
                        input.setAttribute('title', currentTitle !== '' ? `${currentTitle} Maximum file size: ${limitLabel}.` : `Maximum file size: ${limitLabel}.`);
                    }
                }

                input.setAttribute(initializedAttribute, '1');
                input.addEventListener('change', function() {
                    const files = Array.from(input.files || []);
                    if (files.length === 0) {
                        return;
                    }

                    const maxBytes = maxKilobytes * 1024;
                    const oversizedFile = files.find(function(file) {
                        return file && Number.isFinite(file.size) && file.size > maxBytes;
                    });

                    if (!oversizedFile) {
                        return;
                    }

                    input.value = '';
                    notifyFileTooLarge(input, oversizedFile, maxKilobytes);
                }, true);
            };

            document.querySelectorAll('input[type="file"][data-max-size-kb]').forEach(bindInput);

            if (!document.body || typeof MutationObserver !== 'function') {
                return;
            }

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (!(node instanceof Element)) {
                            return;
                        }

                        if (node.matches && node.matches('input[type="file"][data-max-size-kb]')) {
                            bindInput(node);
                        }

                        if (node.querySelectorAll) {
                            node.querySelectorAll('input[type="file"][data-max-size-kb]').forEach(bindInput);
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        window.AppUI = window.AppUI || {};
        window.AppUI.confirm = showConfirm;
        window.AppUI.error = showGlobalError;
        window.AppUI.toast = showToast;
        window.AppUI.initializeFileSizeValidation = initializeFileSizeValidation;

        initializeFileSizeValidation();

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

        const initialErrors = [];
        @if (session('error'))
            initialErrors.push(@json(session('error')));
        @endif
        @if ($errors->any())
            initialErrors.push(@json($errors->first()));
        @endif
        initialErrors.forEach(function(message) {
            showGlobalError(message);
        });
    })();
</script>
