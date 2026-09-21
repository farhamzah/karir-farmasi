<?php

namespace App\Http\Controllers;

use App\Cv\CareerCvProjection;
use App\Cv\CareerCvWriter;
use App\Cv\CvFieldVisibility;
use App\Cv\CvShareLinks;
use App\Cv\CvTemplateCatalog;
use App\Data\CareerActor;
use App\Http\Requests\SaveCareerCvRequest;
use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareerCvController extends Controller
{
    public function index(Request $request, CvShareLinks $links): Response
    {
        $profile = $this->profile($request);
        $cvs = $profile?->cvs()->with(['templateVersion.template', 'shareLinks.revision'])->latest('updated_at')->get()->map(function ($cv) use ($links) {
            $share = $cv->shareLinks->first(fn ($link) => $link->accessible() && $link->safeToken() !== null);

            return [
                'id' => $cv->id, 'name' => $cv->name, 'status' => $cv->status,
                'template' => $cv->templateVersion->template->name, 'template_key' => $cv->templateVersion->template->key,
                'version' => $cv->templateVersion->version,
                'updated_at' => $cv->updated_at->toAtomString(),
                'share_url' => $share ? $links->publicUrl($share) : null,
            ];
        })->all() ?? [];

        return Inertia::render('Cv/Index', ['profileReady' => $profile !== null, 'cvs' => $cvs]);
    }

    public function create(Request $request, CvTemplateCatalog $templates, CareerCvProjection $projection, CvFieldVisibility $fieldVisibility): Response|RedirectResponse
    {
        $profile = $this->profile($request);
        if ($profile === null) {
            return redirect()->route('profile.index')->with('error', 'Isi nama profesional terlebih dahulu sebelum membuat CV.');
        }

        return Inertia::render('Cv/Editor', ['mode' => 'create', 'cv' => null, 'templates' => $templates->frontend(), 'profile' => $projection->editorProfile($profile), 'sections' => $this->defaultSections($projection->editorProfile($profile)), 'visibleFields' => $fieldVisibility->editor(null)]);
    }

    public function store(SaveCareerCvRequest $request, CareerCvWriter $writer): RedirectResponse
    {
        $profile = $this->profile($request);
        if ($profile === null) {
            return redirect()->route('profile.index')->with('error', 'Profil profesional diperlukan untuk membuat CV.');
        }
        $cv = $writer->save($profile, $request->validated());

        return redirect()->route('cv.preview', $cv)->with('success', 'CV berhasil dibuat.');
    }

    public function edit(Request $request, int $cv, CvTemplateCatalog $templates, CareerCvProjection $projection, CareerCvPolicy $policy, CvFieldVisibility $fieldVisibility): Response
    {
        $profile = $this->profileOrFail($request);
        $careerCv = $this->ownedCv($profile, $cv);
        abort_unless($policy->view($this->actor($request), $careerCv), 404);
        $careerCv->load(['sectionPreferences', 'itemPreferences']);
        $profileData = $projection->editorProfile($profile);

        return Inertia::render('Cv/Editor', ['mode' => 'edit', 'templates' => $templates->frontend(), 'profile' => $profileData,
            'cv' => ['id' => $careerCv->id, 'name' => $careerCv->name, 'template_version_id' => $careerCv->cv_template_version_id,
                'custom_headline' => $careerCv->custom_headline, 'custom_summary' => $careerCv->custom_summary, 'status' => $careerCv->status],
            'sections' => $this->savedSections($careerCv, $profileData), 'visibleFields' => $fieldVisibility->editor($careerCv->field_visibility)]);
    }

    public function update(SaveCareerCvRequest $request, int $cv, CareerCvWriter $writer, CareerCvPolicy $policy): RedirectResponse
    {
        $profile = $this->profileOrFail($request);
        $careerCv = $this->ownedCv($profile, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        $writer->save($profile, $request->validated(), $careerCv);

        return redirect()->route('cv.preview', $careerCv)->with('success', 'Pengaturan CV diperbarui.');
    }

    public function destroy(Request $request, int $cv, CareerCvPolicy $policy): RedirectResponse
    {
        $profile = $this->profileOrFail($request);
        $careerCv = $this->ownedCv($profile, $cv);
        abort_unless($policy->delete($this->actor($request), $careerCv), 404);
        $careerCv->delete();

        return redirect()->route('cv.index')->with('success', 'CV dihapus tanpa mengubah profil profesional.');
    }

    private function profile(Request $request): ?CareerProfile
    {
        return CareerProfile::where('core_user_id', $this->actor($request)->coreUserId)->first();
    }

    private function profileOrFail(Request $request): CareerProfile
    {
        return $this->profile($request) ?? abort(404);
    }

    private function ownedCv(CareerProfile $profile, int $id): CareerCv
    {
        return $profile->cvs()->findOrFail($id);
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }

    /** @param array<string, mixed> $profile @return list<array<string, mixed>> */
    private function defaultSections(array $profile): array
    {
        return collect($profile['sections'])->values()->map(fn ($section, $order) => [
            'key' => $section['key'], 'label' => $section['label'], 'enabled' => true, 'sort_order' => $order,
            'display_title' => null, 'items' => collect($section['items'])->values()->map(fn ($item, $itemOrder) => $item + ['enabled' => true, 'sort_order' => $itemOrder])->all(),
        ])->all();
    }

    /** @param array<string, mixed> $profile @return list<array<string, mixed>> */
    private function savedSections(CareerCv $cv, array $profile): array
    {
        $sections = collect($profile['sections'])->keyBy('key');

        $saved = $cv->sectionPreferences->sortBy('sort_order')->values()->map(function ($preference) use ($cv, $sections) {
            $source = $sections[$preference->section_key];
            $preferences = $cv->itemPreferences->where('section_key', $preference->section_key)->keyBy('source_item_id');
            $items = collect($source['items'])->map(function ($item) use ($preferences) {
                $saved = $preferences->get($item['source_item_id']);

                return $item + ['enabled' => $saved?->enabled ?? false, 'sort_order' => $saved?->sort_order ?? 999];
            })->sortBy('sort_order')->values()->all();

            return ['key' => $preference->section_key, 'label' => $source['label'], 'enabled' => $preference->enabled,
                'sort_order' => $preference->sort_order, 'display_title' => $preference->display_title, 'items' => $items];
        });
        $known = $saved->pluck('key');
        $missing = $sections->reject(fn ($section, $key) => $known->contains($key))->values()->map(fn ($source, $index) => [
            'key' => $source['key'], 'label' => $source['label'], 'enabled' => false, 'sort_order' => $saved->count() + $index,
            'display_title' => null, 'items' => collect($source['items'])->values()->map(fn ($item, $itemOrder) => $item + ['enabled' => false, 'sort_order' => $itemOrder])->all(),
        ]);

        return $saved->concat($missing)->values()->all();
    }
}
