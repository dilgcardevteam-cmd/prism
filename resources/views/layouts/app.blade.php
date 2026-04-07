<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('DILG-Logo.png') }}" type="image/png">

    @yield('head')

    @include('partials.google-sans-font')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" style="height: 40px; margin-bottom: 5px;">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    @include('partials.confirmation-modal')

    <div id="globalErrorModal" class="system-dialog-modal" aria-hidden="true">
        <div class="system-dialog-backdrop" data-error-dismiss></div>
        <div class="system-dialog-card" role="dialog" aria-modal="true" aria-labelledby="globalErrorModalTitle">
            <div class="system-dialog-header">
                <h3 id="globalErrorModalTitle" class="system-dialog-title">System Error</h3>
            </div>
            <div class="system-dialog-body" id="globalErrorModalMessage">An unexpected error occurred.</div>
            <div class="system-dialog-actions">
                <button type="button" class="system-dialog-btn error-ok" id="globalErrorOkBtn">OK</button>
            </div>
        </div>
    </div>

    <script>
        (function initializeSystemDialogs() {
            const confirmModal = document.getElementById('globalConfirmModal');
            const confirmMessage = document.getElementById('globalConfirmModalMessage');
            const confirmOkBtn = document.getElementById('globalConfirmOkBtn');
            const confirmCancelBtn = document.getElementById('globalConfirmCancelBtn');
            const confirmDismissTargets = document.querySelectorAll('[data-confirm-dismiss]');
            const errorModal = document.getElementById('globalErrorModal');
            const errorMessage = document.getElementById('globalErrorModalMessage');
            const errorOkBtn = document.getElementById('globalErrorOkBtn');
            const errorDismissTargets = document.querySelectorAll('[data-error-dismiss]');
            const nativeConfirm = window.confirm.bind(window);
            let nativeConfirmBypassCount = 0;
            let confirmCallback = null;
            let confirmCancelCallback = null;

            function openModal(modal) {
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('system-dialog-open');
            }

            function closeModal(modal) {
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.system-dialog-modal.is-open')) {
                    document.body.classList.remove('system-dialog-open');
                }
            }

            function closeConfirmModal(runCancelCallback) {
                const shouldRunCancel = runCancelCallback === true;
                const pendingCancel = confirmCancelCallback;
                confirmCallback = null;
                confirmCancelCallback = null;
                closeModal(confirmModal);
                if (shouldRunCancel && pendingCancel) {
                    pendingCancel();
                }
            }

            window.openConfirmationModal = function(message, onConfirm, onCancel) {
                if (!confirmModal || !confirmMessage) return;
                if (confirmModal.classList.contains('is-open')) {
                    return;
                }
                confirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
                confirmCancelCallback = typeof onCancel === 'function' ? onCancel : null;
                confirmMessage.textContent = message || 'Please confirm this action.';
                openModal(confirmModal);
                if (confirmOkBtn) {
                    confirmOkBtn.focus();
                }
            };

            window.showSystemErrorModal = function(message) {
                if (!errorModal || !errorMessage) return;
                errorMessage.textContent = message || 'An unexpected system error occurred. Please try again.';
                openModal(errorModal);
                if (errorOkBtn) {
                    errorOkBtn.focus();
                }
            };

            window.withNativeConfirmBypass = function(callback) {
                nativeConfirmBypassCount += 1;
                try {
                    return callback();
                } finally {
                    setTimeout(function() {
                        nativeConfirmBypassCount = Math.max(nativeConfirmBypassCount - 1, 0);
                    }, 0);
                }
            };

            window.confirm = function(message) {
                if (nativeConfirmBypassCount > 0) {
                    nativeConfirmBypassCount -= 1;
                    return true;
                }
                return nativeConfirm(message);
            };

            if (confirmOkBtn) {
                confirmOkBtn.addEventListener('click', function() {
                    const pending = confirmCallback;
                    closeConfirmModal(false);
                    if (pending) pending();
                });
            }

            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', function() {
                    closeConfirmModal(true);
                });
            }

            confirmDismissTargets.forEach((el) => {
                el.addEventListener('click', function() {
                    closeConfirmModal(true);
                });
            });

            if (errorOkBtn) {
                errorOkBtn.addEventListener('click', function() {
                    closeModal(errorModal);
                });
            }

            errorDismissTargets.forEach((el) => {
                el.addEventListener('click', function() {
                    closeModal(errorModal);
                });
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;
                if (confirmModal && confirmModal.classList.contains('is-open')) {
                    closeConfirmModal(true);
                    return;
                }
                if (errorModal && errorModal.classList.contains('is-open')) {
                    closeModal(errorModal);
                }
            });

            const initialError = @json(session('error'));
            if (initialError) {
                window.showSystemErrorModal(initialError);
            }

            window.addEventListener('error', function(event) {
                const message = (event && event.message) ? event.message : '';
                if (!message || message === 'Script error.') return;
                const source = event && typeof event.filename === 'string' ? event.filename : '';
                const sameOriginSource = !source || source.startsWith(window.location.origin) || source.startsWith('/');
                if (!sameOriginSource) return;
                window.showSystemErrorModal(message);
            });

            window.addEventListener('unhandledrejection', function(event) {
                const reason = event ? event.reason : null;
                const message = typeof reason === 'string' ? reason : (reason && reason.message ? reason.message : '');
                window.showSystemErrorModal(message || 'A background process failed. Please try again.');
            });
        })();

        // Confirmation for save/update/delete actions
        (function attachActionConfirms() {
            const defaultMessages = {
                save: 'Are you sure you want to save these changes?',
                delete: 'Are you sure you want to delete this item? This action cannot be undone.'
            };

            function getActionText(el) {
                const text = (el.textContent || el.value || '').trim().toLowerCase();
                return text;
            }

            function extractInlineConfirmMessage(code) {
                if (!code) return '';
                const match = code.match(/confirm\s*\(\s*(['"])(.*?)\1\s*\)/i);
                return match && match[2] ? match[2] : '';
            }

            function normalizeInlineConfirmHandlers() {
                document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
                    const inlineCode = form.getAttribute('onsubmit') || '';
                    const message = extractInlineConfirmMessage(inlineCode);
                    if (message && !form.dataset.confirm) {
                        form.dataset.confirm = message;
                    }
                    form.removeAttribute('onsubmit');
                });
            }

            function needsAutoConfirm(el, form) {
                if (!el || el.disabled) return false;
                if (el.dataset && el.dataset.confirmSkip === 'true') return false;
                if (el.dataset && el.dataset.confirm) return true;
                if (form && form.dataset && form.dataset.confirm) return true;
                const text = getActionText(el);
                if (!text) return false;
                const isSave = text.includes('save');
                const isDelete = text.includes('delete');
                return isSave || isDelete;
            }

            function resolveMessage(el, form) {
                if (el.dataset && el.dataset.confirm) return el.dataset.confirm;
                if (form && form.dataset && form.dataset.confirm) return form.dataset.confirm;
                const text = getActionText(el);
                return text.includes('delete') ? defaultMessages.delete : defaultMessages.save;
            }

            normalizeInlineConfirmHandlers();

            document.addEventListener('click', function(e) {
                const target = e.target.closest('button, input[type="submit"], input[type="button"], a');
                if (!target) return;
                const form = target.closest('form');

                if (target.dataset && target.dataset.confirmed === 'true') {
                    delete target.dataset.confirmed;
                    return;
                }

                if (!needsAutoConfirm(target, form)) return;

                e.preventDefault();
                e.stopPropagation();
                const message = resolveMessage(target, form);
                window.openConfirmationModal(message, function() {
                    target.dataset.confirmed = 'true';
                    const targetTag = target.tagName ? target.tagName.toUpperCase() : '';
                    const explicitType = (target.getAttribute('type') || '').toLowerCase();
                    const resolvedType = (target.type || explicitType || '').toLowerCase();
                    const isSubmitButton = targetTag === 'BUTTON' && (resolvedType === '' || resolvedType === 'submit');
                    const isSubmitInput = targetTag === 'INPUT' && resolvedType === 'submit';

                    if (form && (isSubmitButton || isSubmitInput)) {
                        window.withNativeConfirmBypass(function() {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit(target);
                            } else {
                                form.submit();
                            }
                        });
                        return;
                    }

                    window.withNativeConfirmBypass(function() {
                        target.click();
                    });
                });
            }, true);

            document.addEventListener('submit', function(e) {
                const submitter = e.submitter;
                const form = e.target;

                if (form && form.dataset && form.dataset.confirmed === 'true') {
                    delete form.dataset.confirmed;
                    return;
                }

                if (!submitter) {
                    if (!form || !form.dataset || !form.dataset.confirm) return;
                    e.preventDefault();
                    e.stopPropagation();
                    window.openConfirmationModal(form.dataset.confirm, function() {
                        form.dataset.confirmed = 'true';
                        window.withNativeConfirmBypass(function() {
                            form.submit();
                        });
                    });
                    return;
                }

                if (submitter.dataset && submitter.dataset.confirmed === 'true') {
                    delete submitter.dataset.confirmed;
                    return;
                }

                if (!needsAutoConfirm(submitter, form)) return;

                e.preventDefault();
                e.stopPropagation();
                const message = resolveMessage(submitter, form);
                window.openConfirmationModal(message, function() {
                    submitter.dataset.confirmed = 'true';
                    window.withNativeConfirmBypass(function() {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit(submitter);
                        } else {
                            form.submit();
                        }
                    });
                });
            }, true);
        })();
    </script>
</body>
</html>
