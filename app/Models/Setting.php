<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple key/value settings store used for certificate branding:
 * signatory name, signatory title, logo path and signature image path.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const SIGNATORY_NAME = 'certificate.signatory_name';
    public const SIGNATORY_TITLE = 'certificate.signatory_title';
    public const LOGO_PATH = 'certificate.logo_path';
    public const SIGNATURE_PATH = 'certificate.signature_path';

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Data the certificate PDF needs, with safe fallbacks.
     */
    public static function signatory(): array
    {
        return [
            'name' => static::get(static::SIGNATORY_NAME),
            'title' => static::get(static::SIGNATORY_TITLE),
            'logo_path' => static::get(static::LOGO_PATH),
            'signature_path' => static::get(static::SIGNATURE_PATH),
        ];
    }
}
