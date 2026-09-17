<?php

namespace App\Cv;

class CvFieldVisibility
{
    /** @var array<string, string> */
    private const FIELDS = [
        'photo' => 'Foto profesional',
        'city' => 'Domisili',
        'email' => 'Email profesional',
        'whatsapp' => 'WhatsApp',
        'linkedin_url' => 'LinkedIn',
        'portfolio_url' => 'Portofolio',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::FIELDS);
    }

    /** @param array<string, mixed>|null $visibility @return array<string, bool> */
    public function normalized(?array $visibility): array
    {
        return collect(self::FIELDS)->mapWithKeys(
            fn (string $label, string $key): array => [$key => ! array_key_exists($key, $visibility ?? []) || (bool) $visibility[$key]],
        )->all();
    }

    /** @param array<string, mixed>|null $visibility @return list<array{key: string, label: string, enabled: bool}> */
    public function editor(?array $visibility): array
    {
        $normalized = $this->normalized($visibility);

        return collect(self::FIELDS)->map(
            fn (string $label, string $key): array => ['key' => $key, 'label' => $label, 'enabled' => $normalized[$key]],
        )->values()->all();
    }
}
