<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'method',
        'path',
        'subject_type',
        'subject_id',
        'description',
        'meta',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write an activity entry. Used by the logging middleware and by domain
     * events that want a human-readable description.
     */
    public static function record(
        ?int $userId,
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $meta = [],
        ?string $ip = null,
    ): self {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'method' => $meta['method'] ?? 'ACTION',
            'path' => $meta['path'] ?? '',
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'meta' => $meta ?: null,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }
}
