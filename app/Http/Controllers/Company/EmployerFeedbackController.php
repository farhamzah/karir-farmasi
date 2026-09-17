<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CareerJobApplication;
use App\Models\CompanyUser;
use App\Models\EmployerFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployerFeedbackController extends Controller
{
    public function store(Request $request, string $applicationReference): RedirectResponse
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        abort_unless($actor->company->canSearchTalent(), 403);
        $application = CareerJobApplication::query()->where('public_reference', $applicationReference)
            ->whereHas('job', fn ($query) => $query->where('company_id', $actor->company_id))->firstOrFail();
        $data = $request->validate([
            'relationship' => ['required', Rule::in(['applicant', 'interviewed', 'hired', 'started'])],
            'ratings' => ['required', 'array'],
            'ratings.professionalism' => ['required', 'integer', 'between:1,5'],
            'ratings.communication' => ['required', 'integer', 'between:1,5'],
            'ratings.technical' => ['required', 'integer', 'between:1,5'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'development_notes' => ['nullable', 'string', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);
        EmployerFeedback::query()->updateOrCreate(
            ['career_job_application_id' => $application->id, 'company_id' => $actor->company_id],
            $data + ['company_user_id' => $actor->id, 'career_profile_id' => $application->career_profile_id, 'submitted_at' => now()],
        );

        return back()->with('success', 'Masukan employer disimpan privat. Pelaporan menggunakan agregat.');
    }
}
