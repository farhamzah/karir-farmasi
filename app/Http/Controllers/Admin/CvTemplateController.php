<?php

namespace App\Http\Controllers\Admin;

use App\Cv\CvTemplateConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCvTemplateRequest;
use App\Http\Requests\UpdateCvTemplateDraftRequest;
use App\Models\CvTemplate;
use App\Models\CvTemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CvTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = CvTemplate::query()->with(['versions' => fn ($query) => $query->withCount('cvs')->latest('id')])
            ->orderBy('display_order')->orderBy('id')->get()->map(fn (CvTemplate $template) => $this->summary($template));

        return Inertia::render('Admin/CvTemplates/Index', [
            'templates' => $templates,
            'baseTemplates' => CvTemplate::query()->whereIn('key', ['cv-01', 'cv-02', 'cv-03', 'cv-04', 'cv-05'])
                ->where('active', true)->orderBy('display_order')->get(['key', 'name']),
        ]);
    }

    public function store(StoreCvTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $base = CvTemplate::query()->where('key', $data['base_template_key'])->firstOrFail();
        $source = $this->currentVersion($base);

        $template = DB::transaction(function () use ($data, $source): CvTemplate {
            $template = CvTemplate::create([
                'key' => $data['key'], 'name' => $data['name'], 'description' => $data['description'],
                'active' => true, 'category' => 'admin-variation', 'base_template_key' => $data['base_template_key'],
                'display_order' => ((int) CvTemplate::max('display_order')) + 10,
            ]);
            $template->versions()->create([
                'version' => 'draft-1', 'configuration' => $source->configuration,
                'status' => 'draft', 'published_at' => null,
            ]);

            return $template;
        });

        return redirect()->route('admin.cv-templates.show', $template)->with('success', 'Variasi template dibuat sebagai draf aman.');
    }

    public function show(CvTemplate $template): Response
    {
        $template->load(['versions' => fn ($query) => $query->withCount('cvs')->latest('id')]);
        $draft = $template->versions->firstWhere('status', 'draft');
        $current = $this->currentVersion($template, false);
        $editable = $draft ?? $current;

        return Inertia::render('Admin/CvTemplates/Edit', [
            'template' => $this->summary($template),
            'draft' => $draft ? $this->versionData($draft) : null,
            'editableConfiguration' => $editable ? app(CvTemplateConfiguration::class)->normalized($editable->configuration) : null,
            'allowed' => CvTemplateConfiguration::allowed(),
            'versions' => $template->versions->map(fn (CvTemplateVersion $version) => $this->versionData($version))->values(),
        ]);
    }

    public function update(UpdateCvTemplateDraftRequest $request, CvTemplate $template): RedirectResponse
    {
        $data = $request->validated();
        $configuration = app(CvTemplateConfiguration::class)->validated($data['configuration']);

        DB::transaction(function () use ($template, $data, $configuration): void {
            $template->update(['name' => $data['name'], 'description' => $data['description']]);
            $draft = $template->versions()->where('status', 'draft')->latest('id')->first();
            if ($draft === null) {
                $draft = $template->versions()->create([
                    'version' => 'draft-'.($template->versions()->count() + 1),
                    'configuration' => $configuration, 'status' => 'draft', 'published_at' => null,
                ]);
            } else {
                $draft->update(['configuration' => $configuration]);
            }
        });

        return back()->with('success', 'Konfigurasi draf tersimpan. Versi terbit tetap tidak berubah.');
    }

    public function duplicate(Request $request, CvTemplate $template): RedirectResponse
    {
        $request->validate(['key' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:cv_templates,key']]);
        $source = $this->currentVersion($template);
        $copy = DB::transaction(function () use ($request, $template, $source): CvTemplate {
            $copy = CvTemplate::create([
                'key' => $request->string('key')->toString(), 'name' => $template->name.' Salinan',
                'description' => $template->description, 'active' => true, 'category' => 'admin-variation',
                'base_template_key' => $template->base_template_key ?: $template->key,
                'display_order' => ((int) CvTemplate::max('display_order')) + 10,
            ]);
            $copy->versions()->create(['version' => 'draft-1', 'configuration' => $source->configuration, 'status' => 'draft']);

            return $copy;
        });

        return redirect()->route('admin.cv-templates.show', $copy)->with('success', 'Template diduplikasi sebagai draf.');
    }

    public function publish(CvTemplate $template): RedirectResponse
    {
        $draft = $template->versions()->where('status', 'draft')->latest('id')->first();
        if ($draft === null) {
            throw ValidationException::withMessages(['template' => 'Simpan perubahan sebagai draf sebelum menerbitkan versi baru.']);
        }
        app(CvTemplateConfiguration::class)->validated($draft->configuration);
        $draft->update(['version' => $this->nextVersion($template), 'status' => 'published', 'published_at' => now()]);
        $template->update(['active' => true]);

        return back()->with('success', 'Versi baru diterbitkan. CV lama tetap terikat ke versi sebelumnya.');
    }

    public function retire(CvTemplate $template): RedirectResponse
    {
        $template->update(['active' => false]);

        return back()->with('success', 'Template dipensiunkan untuk pilihan baru; CV lama tetap dapat dirender.');
    }

    public function reactivate(CvTemplate $template): RedirectResponse
    {
        if ($this->currentVersion($template, false) === null) {
            throw ValidationException::withMessages(['template' => 'Template belum memiliki versi terbit.']);
        }
        $template->update(['active' => true]);

        return back()->with('success', 'Template kembali tersedia.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.id' => ['required', 'integer', 'distinct', 'exists:cv_templates,id'],
            'templates.*.display_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        DB::transaction(function () use ($data): void {
            foreach ($data['templates'] as $item) {
                CvTemplate::whereKey($item['id'])->update(['display_order' => $item['display_order']]);
            }
        });

        return back()->with('success', 'Urutan template diperbarui.');
    }

    public function preview(CvTemplate $template): Response
    {
        $draft = $template->versions()->where('status', 'draft')->latest('id')->first();
        $version = $draft ?? $this->currentVersion($template);

        return Inertia::render('Admin/CvTemplates/Preview', [
            'template' => ['id' => $template->id, 'key' => $template->key, 'name' => $template->name,
                'version' => $version->version, 'configuration' => app(CvTemplateConfiguration::class)->normalized($version->configuration)],
            'fixture' => $this->syntheticFixture(),
        ]);
    }

    private function currentVersion(CvTemplate $template, bool $required = true): ?CvTemplateVersion
    {
        $version = $template->versions()->where('status', 'published')->whereNotNull('published_at')
            ->latest('published_at')->latest('id')->first();
        if ($required && $version === null) {
            throw ValidationException::withMessages(['template' => 'Template dasar belum memiliki versi terbit.']);
        }

        return $version;
    }

    /** @return array<string, mixed> */
    private function summary(CvTemplate $template): array
    {
        $current = $template->versions->where('status', 'published')->sortByDesc('id')->first();

        return ['id' => $template->id, 'key' => $template->key, 'name' => $template->name,
            'description' => $template->description, 'active' => $template->active, 'category' => $template->category,
            'base_template_key' => $template->base_template_key, 'display_order' => $template->display_order,
            'current_version' => $current?->version, 'usage_count' => $template->versions->sum('cvs_count'),
            'has_draft' => $template->versions->contains('status', 'draft'), 'updated_at' => $template->updated_at?->toAtomString()];
    }

    /** @return array<string, mixed> */
    private function versionData(CvTemplateVersion $version): array
    {
        return ['id' => $version->id, 'version' => $version->version, 'status' => $version->status,
            'configuration' => app(CvTemplateConfiguration::class)->normalized($version->configuration),
            'usage_count' => $version->cvs_count ?? $version->cvs()->count(), 'published_at' => $version->published_at?->toAtomString()];
    }

    private function nextVersion(CvTemplate $template): string
    {
        $latest = $this->currentVersion($template, false);
        if ($latest === null || ! preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $latest->version, $parts)) {
            return '1.0.0';
        }

        return $parts[1].'.'.$parts[2].'.'.((int) $parts[3] + 1);
    }

    /** @return array<string, mixed> */
    private function syntheticFixture(): array
    {
        return [
            'professional_name' => 'Alya Nūr Pramesti, S.Farm.', 'headline' => 'Profesional farmasi yang teliti, adaptif, dan berorientasi pada mutu',
            'email' => 'alya.nur@fixture.invalid', 'whatsapp' => '+62 812 0000 006', 'city' => 'Karawang', 'has_photo' => false,
            'sections' => [
                ['key' => 'summary', 'title' => 'Profil', 'items' => [['description' => 'Alumni Farmasi UBP dengan pengalaman sintetis dalam pelayanan, pengendalian mutu, organisasi, dan riset terapan.']]],
                ['key' => 'experience', 'title' => 'Pengalaman', 'items' => [['title' => 'Praktik Kerja Profesi Apoteker', 'organization' => 'Instalasi Farmasi Sintetis', 'location' => 'Karawang', 'start_date' => '2025-01-01', 'end_date' => '2025-06-30', 'description' => 'Mendukung pengelolaan obat dan edukasi pasien menggunakan data pengujian sintetis.']]],
                ['key' => 'education', 'title' => 'Pendidikan', 'items' => [['degree' => 'Sarjana Farmasi', 'program_name' => 'Farmasi', 'institution_name' => 'Universitas Buana Perjuangan Karawang', 'end_year' => 2025]]],
                ['key' => 'skills', 'title' => 'Keahlian', 'items' => [['name' => 'Pelayanan kefarmasian'], ['name' => 'Dokumentasi mutu'], ['name' => 'Komunikasi pasien']]],
                ['key' => 'publications', 'title' => 'Riset & Publikasi', 'items' => [['title' => 'Kajian Stabilitas Sediaan Farmasi Sintetis', 'publication_name' => 'Prosiding Uji Lokal', 'published_on' => '2025-08-01']]],
            ],
        ];
    }
}
