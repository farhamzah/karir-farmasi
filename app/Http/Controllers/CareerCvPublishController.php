<?php

namespace App\Http\Controllers;

use App\Cv\CvPublisher;
use App\Data\CareerActor;
use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CareerCvPublishController extends Controller
{
    public function store(Request $request, int $cv, CvPublisher $publisher, CareerCvPolicy $policy): RedirectResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->update($this->actor($request), $careerCv), 404);
        $revision = $publisher->publish($careerCv);

        return back()->with('success', "CV publik revision {$revision->revision_number} berhasil diterbitkan.");
    }

    private function ownedCv(Request $request, int $id): CareerCv
    {
        return CareerProfile::where('core_user_id', $this->actor($request)->coreUserId)->firstOrFail()->cvs()->findOrFail($id);
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
