{{--
    Global confirmation dialog.

    Replaces the browser's native confirm() box everywhere, without editing the
    twenty call sites that already use it: any form[onsubmit*="confirm("] or
    [onclick*="confirm("] is intercepted in the capture phase, the native handler
    never runs, and the action is replayed only after the user confirms.

    New code can skip the legacy syntax entirely:
        <form ... data-confirm="Delete this module?" data-confirm-title="Delete module" data-confirm-button="Delete">
--}}
<div id="lms-confirm" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="lms-confirm-title">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" data-confirm-cancel></div>

    <div class="fixed inset-0 flex items-end sm:items-center justify-center p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl ring-1 ring-black/5 overflow-hidden
                    transform transition ease-out duration-200 opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             id="lms-confirm-panel">

            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center" id="lms-confirm-icon">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900" id="lms-confirm-title">Are you sure?</h3>
                        <p class="mt-1 text-sm text-gray-600" id="lms-confirm-message"></p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="button" data-confirm-cancel
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button type="button" data-confirm-accept
                        class="inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        if (window.LmsConfirm) {
            return;
        }

        const root = document.getElementById('lms-confirm');
        const panel = document.getElementById('lms-confirm-panel');
        const titleEl = document.getElementById('lms-confirm-title');
        const messageEl = document.getElementById('lms-confirm-message');
        const acceptBtn = root.querySelector('[data-confirm-accept]');
        const cancelBtn = root.querySelector('[data-confirm-cancel]:not([data-confirm-cancel].fixed)');
        const iconWrap = document.getElementById('lms-confirm-icon');

        let resolver = null;
        let lastFocused = null;

        const TONES = {
            danger: {
                wrap: 'bg-red-100',
                icon: 'text-red-600',
                button: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
            },
            primary: {
                wrap: 'bg-indigo-100',
                icon: 'text-indigo-600',
                button: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
            },
        };

        function open(options) {
            lastFocused = document.activeElement;

            titleEl.textContent = options.title || 'Are you sure?';
            messageEl.textContent = options.message || '';
            acceptBtn.textContent = options.confirmLabel || 'Confirm';
            cancelBtn.textContent = options.cancelLabel || 'Cancel';

            const tone = TONES[options.tone] || TONES.primary;
            iconWrap.className = 'flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center ' + tone.wrap;
            iconWrap.querySelector('svg').setAttribute('class', 'h-6 w-6 ' + tone.icon);
            acceptBtn.className = 'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 ' + tone.button;

            root.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                panel.classList.remove('opacity-0', 'translate-y-4', 'sm:scale-95');
            });

            setTimeout(() => acceptBtn.focus(), 30);

            return new Promise((resolve) => {
                resolver = resolve;
            });
        }

        function close(result) {
            panel.classList.add('opacity-0', 'translate-y-4', 'sm:scale-95');
            document.body.classList.remove('overflow-hidden');

            setTimeout(() => {
                root.classList.add('hidden');
                if (lastFocused && lastFocused.focus) {
                    lastFocused.focus();
                }
            }, 150);

            if (resolver) {
                const done = resolver;
                resolver = null;
                done(result);
            }
        }

        acceptBtn.addEventListener('click', () => close(true));
        root.querySelectorAll('[data-confirm-cancel]').forEach((el) => {
            el.addEventListener('click', () => close(false));
        });

        document.addEventListener('keydown', (e) => {
            if (root.classList.contains('hidden')) {
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                close(false);
            }
            if (e.key === 'Enter' && document.activeElement === acceptBtn) {
                e.preventDefault();
                close(true);
            }
        });

        window.LmsConfirm = function (options) {
            if (typeof options === 'string') {
                options = { message: options };
            }
            return open(options || {});
        };

        // Pull the message out of legacy handlers: return confirm('...');
        function messageFrom(handler) {
            if (!handler) {
                return '';
            }
            const match = handler.match(/confirm\('([\s\S]*)'\)/);
            return match ? match[1].replace(/\\'/g, "'") : '';
        }

        function toneFor(text) {
            return /delete|remove|revoke|reject|reset/i.test(text) ? 'danger' : 'primary';
        }

        // ---- Forms: onsubmit="return confirm('...')" -------------------------
        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || form.dataset.lmsConfirmed === '1') {
                return;
            }

            const handler = form.getAttribute('onsubmit') || '';
            const explicit = form.getAttribute('data-confirm');
            const message = explicit || messageFrom(handler);

            if (!message || (!explicit && !/confirm\(/.test(handler))) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            window.LmsConfirm({
                title: form.getAttribute('data-confirm-title') || (toneFor(message) === 'danger' ? 'Please confirm' : 'Are you sure?'),
                message: message,
                confirmLabel: form.getAttribute('data-confirm-button') || (toneFor(message) === 'danger' ? 'Delete' : 'Confirm'),
                tone: toneFor(message),
            }).then((ok) => {
                if (!ok) {
                    return;
                }
                form.dataset.lmsConfirmed = '1';
                form.submit(); // native submit: skips onsubmit, so no second dialog
            });
        }, true);

        // ---- Buttons and links: onclick="return confirm('...')" --------------
        document.addEventListener('click', function (event) {
            const el = event.target.closest('[onclick*="confirm("]');

            if (!el || el.dataset.lmsConfirmed === '1' || el.tagName === 'FORM') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const message = el.getAttribute('data-confirm') || messageFrom(el.getAttribute('onclick')) || 'Are you sure?';

            window.LmsConfirm({
                title: el.getAttribute('data-confirm-title') || (toneFor(message) === 'danger' ? 'Please confirm' : 'Are you sure?'),
                message: message,
                confirmLabel: el.getAttribute('data-confirm-button') || (toneFor(message) === 'danger' ? 'Delete' : 'Confirm'),
                tone: toneFor(message),
            }).then((ok) => {
                if (!ok) {
                    return;
                }

                el.dataset.lmsConfirmed = '1';
                el.removeAttribute('onclick');

                if (el.tagName === 'A' && el.href) {
                    window.location.href = el.href;
                } else {
                    el.click();
                }
            });
        }, true);
    })();
</script>
