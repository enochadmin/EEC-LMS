<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Resolves where a notification should take the reader when it is clicked.
 *
 * New notifications store an explicit `url`. Older rows only carry a `type`
 * and some ids, so this class derives the best destination from those and
 * falls back to null (rendered as plain text) when nothing sensible exists.
 */
class NotificationUrl
{
    public static function for(mixed $data, ?User $user = null): ?string
    {
        if (! is_array($data)) {
            return null;
        }

        if (! empty($data['url']) && is_string($data['url'])) {
            return self::internal($data['url']);
        }

        return self::fallback($data, $user);
    }

    /**
     * Links are written by our own notification classes via route(), so the
     * origin is never trusted — only the path is. Keeping the host made
     * notifications dead whenever they were created on localhost:8000 and read
     * on 127.0.0.1:8000 (or vice versa): the host comparison failed and the
     * message rendered as plain, unclickable text. Stripping the origin also
     * guarantees a stored payload can never redirect off-site.
     */
    protected static function internal(string $url): ?string
    {
        $url = trim($url);

        if ($url === '' || Str::startsWith(Str::lower($url), ['javascript:', 'data:', 'vbscript:'])) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Str::startsWith($path, '/')) {
            $path = '/' . $path;
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    }

    protected static function fallback(array $data, ?User $user): ?string
    {
        $type = $data['type'] ?? null;

        switch ($type) {
            case 'lesson_submitted':
            case 'retake_request':
                return self::route('lesson-approvals.index');

            case 'lesson_approved':
            case 'lesson_rejected':
                if (! empty($data['lesson_id']) && ! empty($data['module_id'])) {
                    return self::route('modules.lessons.show', [
                        'module' => $data['module_id'],
                        'lesson' => $data['lesson_id'],
                    ]);
                }

                return self::module($data);

            case 'module_completion_approval_request':
                // Land the instructor straight on the evaluation screen for
                // this learner instead of a generic queue.
                if (! empty($data['learner_id']) && ! empty($data['module_id'])) {
                    $direct = self::route('modules.user-evaluation', [
                        'module' => $data['module_id'],
                        'user' => $data['learner_id'],
                    ]);

                    if ($direct) {
                        return $direct;
                    }
                }

                return self::route('module-approvals.index');

            case 'module_approved':
            case 'module_rejected':
            case 'module_completed':
            case 'enrollment':
                return self::module($data);

            case 'certificate_issued':
                return self::route('certificates.index');

            case 'quiz_attempt':
                return ! empty($data['quiz_id'])
                    ? self::route('quizzes.results', ['quiz' => $data['quiz_id']])
                    : self::route('quizzes.index');

            case 'retake_response':
                return self::route('my.progress');

            default:
                return null;
        }
    }

    protected static function module(array $data): ?string
    {
        return ! empty($data['module_id'])
            ? self::route('modules.show', ['module' => $data['module_id']])
            : null;
    }

    protected static function route(string $name, array $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }
}
