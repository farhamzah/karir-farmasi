<?php

namespace App\Http\Controllers;

use App\Cv\CvShareLinks;
use App\Data\CareerActor;
use App\Http\Requests\StoreCvShareLinkRequest;
use App\Http\Requests\UpdateCvShareLinkRequest;
use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Models\CvShareLink;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CvShareLinkController extends Controller
{
    public function store(StoreCvShareLinkRequest $request, int $cv, CvShareLinks $links, CareerCvPolicy $policy): RedirectResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        abort_if($careerCv->latestPublishedRevision()->doesntExist(), 422, 'Terbitkan CV sebelum membuat tautan.');
        $links->create($careerCv, $request->validated());

        return back()->with('success', 'Tautan publik berhasil dibuat.');
    }

    public function update(UpdateCvShareLinkRequest $request, int $cv, string $share, CareerCvPolicy $policy): RedirectResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        $link = $this->ownedLink($careerCv, $share);
        $link->update($request->validated());

        return back()->with('success', 'Pengaturan tautan diperbarui.');
    }

    public function rotate(Request $request, int $cv, string $share, CvShareLinks $links, CareerCvPolicy $policy): RedirectResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        $links->rotate($this->ownedLink($careerCv, $share));

        return back()->with('success', 'Token lama dicabut dan tautan baru siap digunakan.');
    }

    public function destroy(Request $request, int $cv, string $share, CareerCvPolicy $policy): RedirectResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        $this->ownedLink($careerCv, $share)->update(['active' => false]);

        return back()->with('success', 'Tautan publik dinonaktifkan.');
    }

    private function ownedCv(Request $request, int $id): CareerCv
    {
        return CareerProfile::where('core_user_id', $this->actor($request)->coreUserId)->firstOrFail()->cvs()->findOrFail($id);
    }

    private function ownedLink(CareerCv $cv, string $publicId): CvShareLink
    {
        return $cv->shareLinks()->where('public_id', $publicId)->firstOrFail();
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
