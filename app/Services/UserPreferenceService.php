<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserPreferenceService
{
    public const DEFAULTS = [
        'theme' => 'system',
        'high_contrast' => false,
        'reduced_motion' => false,
        'font_scale' => '100',
        'compact_mode' => false,
        'sidebar_collapsed' => false,
        'locale' => 'es',
    ];

    public const ALLOWED_THEMES = ['system', 'light', 'dark', 'high_contrast', 'color_accessible'];
    public const ALLOWED_FONT_SCALES = ['100', '112', '125', '150'];
    public const ALLOWED_LOCALES = ['es', 'en'];

    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function forUser(?User $user): array
    {
        return $this->normalize($user?->preferences);
    }

    public function update(User $user, array $preferences): array
    {
        $normalized = $this->normalize([
            ...$this->forUser($user),
            ...$preferences,
        ]);

        $user->forceFill(['preferences' => $normalized])->save();

        return $normalized;
    }

    public function normalize(?array $preferences): array
    {
        $preferences = is_array($preferences) ? $preferences : [];

        return [
            'theme' => in_array($preferences['theme'] ?? null, self::ALLOWED_THEMES, true)
                ? $preferences['theme']
                : self::DEFAULTS['theme'],
            'high_contrast' => (bool) ($preferences['high_contrast'] ?? self::DEFAULTS['high_contrast']),
            'reduced_motion' => (bool) ($preferences['reduced_motion'] ?? self::DEFAULTS['reduced_motion']),
            'font_scale' => in_array((string) ($preferences['font_scale'] ?? ''), self::ALLOWED_FONT_SCALES, true)
                ? (string) $preferences['font_scale']
                : self::DEFAULTS['font_scale'],
            'compact_mode' => (bool) ($preferences['compact_mode'] ?? self::DEFAULTS['compact_mode']),
            'sidebar_collapsed' => (bool) ($preferences['sidebar_collapsed'] ?? self::DEFAULTS['sidebar_collapsed']),
            'locale' => in_array($preferences['locale'] ?? null, self::ALLOWED_LOCALES, true)
                ? $preferences['locale']
                : self::DEFAULTS['locale'],
        ];
    }

    public function rejectUnknownKeys(array $preferences): void
    {
        $unknownKeys = array_values(array_diff(array_keys($preferences), array_keys(self::DEFAULTS)));

        if ($unknownKeys !== []) {
            throw ValidationException::withMessages([
                'preferences' => 'Las preferencias contienen claves no permitidas: '.implode(', ', $unknownKeys).'.',
            ]);
        }
    }
}
