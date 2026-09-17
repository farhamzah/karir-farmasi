<?php

namespace App\Cv;

use InvalidArgumentException;

final class CvTemplateConfiguration
{
    private const ALLOWED = [
        'layout' => ['single-column', 'sidebar-main', 'split'],
        'photo' => ['hidden', 'circle', 'oval'],
        'typography' => ['classic', 'modern', 'academic'],
        'spacing' => ['comfortable', 'balanced', 'compact'],
        'header_style' => ['classic', 'hero', 'sidebar'],
        'section_style' => ['rule', 'accent', 'plain'],
        'accent' => ['neutral', 'brand', 'soft'],
        'page_padding' => ['standard', 'compact'],
    ];

    private const LEGACY_KEYS = ['layout', 'photo', 'typography', 'spacing', 'section_style'];

    /** @return array<string, list<string>> */
    public static function allowed(): array
    {
        return self::ALLOWED;
    }

    /** @param array<string, mixed> $configuration @return array<string, string> */
    public function validated(array $configuration): array
    {
        $keys = array_keys($configuration);
        $allowedKeys = array_keys(self::ALLOWED);
        sort($keys);
        sort($allowedKeys);
        $legacyKeys = self::LEGACY_KEYS;
        sort($legacyKeys);
        if ($keys !== $allowedKeys && $keys !== $legacyKeys) {
            throw new InvalidArgumentException('Konfigurasi template tidak mengikuti allowlist.');
        }
        foreach ($configuration as $key => $value) {
            if (! is_string($value) || ! in_array($value, self::ALLOWED[$key], true)) {
                throw new InvalidArgumentException("Nilai konfigurasi [$key] tidak aman.");
            }
        }

        return $configuration;
    }

    /** @param array<string, mixed> $configuration @return array<string, string> */
    public function normalized(array $configuration): array
    {
        $validated = $this->validated($configuration);

        return $validated + [
            'header_style' => $validated['layout'] === 'sidebar-main' ? 'hero' : 'classic',
            'accent' => $validated['section_style'] === 'accent' ? 'brand' : 'neutral',
            'page_padding' => 'standard',
        ];
    }
}
