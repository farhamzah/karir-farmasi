<?php

namespace App\Cv;

use App\Models\CvTemplate;
use App\Models\CvTemplateVersion;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class CvTemplateCatalog
{
    public function __construct(private readonly CvTemplateConfiguration $configuration) {}

    /** @return Collection<int, CvTemplateVersion> */
    public function published(): Collection
    {
        return CvTemplate::query()->where('active', true)->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (CvTemplate $template) => $template->versions()->where('status', 'published')
                ->whereNotNull('published_at')->latest('published_at')->latest('id')->first())
            ->filter()->values()->each(fn (CvTemplateVersion $version) => $this->configuration->validated($version->configuration));
    }

    public function resolve(int $id): CvTemplateVersion
    {
        $version = $this->published()->firstWhere('id', $id);
        if ($version === null) {
            throw ValidationException::withMessages(['template_version_id' => 'Template yang dipilih tidak tersedia.']);
        }
        try {
            $this->configuration->validated($version->configuration);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['template_version_id' => $exception->getMessage()]);
        }

        return $version;
    }

    public function resolveBound(int $id): CvTemplateVersion
    {
        $version = CvTemplateVersion::query()->with('template')->whereKey($id)->where('status', 'published')->whereNotNull('published_at')->first();
        if ($version === null) {
            throw ValidationException::withMessages(['template_version_id' => 'Versi template CV tidak dapat dirender.']);
        }
        $this->configuration->validated($version->configuration);

        return $version;
    }

    /** @return list<array<string, mixed>> */
    public function frontend(): array
    {
        return $this->published()->map(fn (CvTemplateVersion $version) => [
            'version_id' => $version->id, 'key' => $version->template->key, 'name' => $version->template->name,
            'description' => $version->template->description, 'version' => $version->version,
            'configuration' => $this->configuration->normalized($version->configuration),
        ])->all();
    }
}
