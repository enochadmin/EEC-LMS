{{-- Drop-in time tracker for learner-facing pages.
     Usage: <x-time-tracker :module-id="$module->id" :lesson-id="$lesson->id ?? 0" />
     Counts only while the tab is visible, flushes every 30s and on hide/unload. --}}
@props(['moduleId', 'lessonId' => 0])

@auth
    @if (auth()->user()->hasRole('learner'))
        <script>
            (function () {
                const endpoint = @json(route('api.learning-time'));
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const moduleId = {{ (int) $moduleId }};
                const lessonId = {{ (int) $lessonId }};
                const FLUSH_EVERY = 30; // seconds

                let pending = 0;
                let visible = document.visibilityState === 'visible';

                function flush(useBeacon) {
                    if (pending < 1) return;

                    const seconds = Math.min(pending, 120);
                    pending = 0;

                    if (useBeacon && navigator.sendBeacon) {
                        // sendBeacon cannot set headers, so hand the token over
                        // as a form field that Laravel's VerifyCsrfToken accepts.
                        const body = new URLSearchParams({
                            _token: token,
                            training_module_id: moduleId,
                            lesson_id: lessonId,
                            seconds: seconds,
                        });
                        navigator.sendBeacon(endpoint, body);
                        return;
                    }

                    fetch(endpoint, {
                        method: 'POST',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            training_module_id: moduleId,
                            lesson_id: lessonId,
                            seconds: seconds,
                        }),
                    }).catch(() => {});
                }

                setInterval(function () {
                    if (!visible) return;
                    pending++;
                    if (pending >= FLUSH_EVERY) flush(false);
                }, 1000);

                document.addEventListener('visibilitychange', function () {
                    visible = document.visibilityState === 'visible';
                    if (!visible) flush(true);
                });

                window.addEventListener('pagehide', function () { flush(true); });
            })();
        </script>
    @endif
@endauth