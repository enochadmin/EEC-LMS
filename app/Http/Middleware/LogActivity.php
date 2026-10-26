<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every state-changing request in activity_logs.
 *
 * Runs after the response so the authenticated user is available (route
 * middleware has already executed). Read requests are ignored, as are a few
 * noisy endpoints that would otherwise flood the log.
 */
class LogActivity
{
    protected array $except = [
        'notifications/*',
        'logout',
        'livewire/*',
        '_ignition/*',
        'sanctum/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request)) {
            return $response;
        }

        $user = $request->user();

        $meta = [
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'route' => $request->route()?->getName(),
            'status' => $response->getStatusCode(),
        ];

        if ($request->route()?->parameters()) {
            $meta['parameters'] = collect($request->route()->parameters())
                ->map(fn ($value) => is_object($value) && method_exists($value, 'getKey')
                    ? $value->getKey()
                    : $value)
                ->all();
        }

        $input = $this->sanitize($request->except(['_token', '_method']));

        if ($input) {
            $meta['input'] = $input;
        }

        ActivityLog::record(
            userId: $user?->id,
            action: $meta['route'] ?? strtoupper($request->method()) . ' ' . $meta['path'],
            description: $this->describe($request, $user),
            meta: $meta,
            ip: $request->ip(),
        );

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return false;
        }

        if ($request->routeIs('login') || $request->routeIs('password.*')) {
            return false;
        }

        return ! $request->is(...$this->except);
    }

    protected function describe(Request $request, $user): string
    {
        $name = $user?->name ?? 'Guest';
        $route = $request->route()?->getName();

        return $route
            ? "{$name} performed {$route}"
            : "{$name} sent {$request->method()} /" . ltrim($request->path(), '/');
    }

    protected function sanitize(array $input): array
    {
        $blocked = ['password', 'password_confirmation', 'current_password', 'token'];

        foreach ($input as $key => $value) {
            if (in_array($key, $blocked, true)) {
                unset($input[$key]);
                continue;
            }

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $input[$key] = '[file] ' . $value->getClientOriginalName();
                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->sanitize($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $input[$key] = substr($value, 0, 500) . '…';
            }
        }

        return $input;
    }
}
