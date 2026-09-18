<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerProfile;
use App\Support\CareerRoleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlumniDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $isAdministrator = in_array(CareerRoleRegistry::Administrator, $actor->roles, true);
        $query = trim($request->string('q')->toString());
        $graduationYear = $request->integer('graduation_year') ?: null;
        $visibleProfiles = CareerProfile::query()
            ->when(! $isAdministrator, fn ($builder) => $builder->where('visible_in_alumni_directory', true))
            ->whereNotNull('alumni_number');
        $total = (clone $visibleProfiles)->count();
        $graduationYears = (clone $visibleProfiles)->whereNotNull('graduation_year')
            ->distinct()->orderByDesc('graduation_year')->pluck('graduation_year')->all();
        $alumni = $visibleProfiles
            ->when($query !== '', fn ($builder) => $builder->where(fn ($match) => $match
                ->where('professional_name', 'like', "%{$query}%")
                ->orWhere('alumni_number', 'like', "%{$query}%")))
            ->when($graduationYear !== null, fn ($builder) => $builder->where('graduation_year', $graduationYear))
            ->orderBy('alumni_number')
            ->limit(500)
            ->get(['talent_reference', 'alumni_number', 'graduation_year', 'professional_name', 'photo_path'])
            ->map(fn (CareerProfile $profile): array => [
                'reference' => $profile->talent_reference,
                'name' => $profile->professional_name ?: 'Alumni Farmasi UBP',
                'nim' => $profile->alumni_number,
                'graduation_year' => $profile->graduation_year,
                'photo_url' => $profile->photo_path !== null ? route('alumni.photo', $profile) : null,
            ])
            ->all();

        return Inertia::render('Alumni/Index', [
            'alumni' => $alumni,
            'audience' => in_array(CareerRoleRegistry::Candidate, $actor->roles, true) ? 'alumni' : 'staff',
            'filters' => ['q' => $query, 'graduation_year' => $graduationYear],
            'graduationYears' => $graduationYears,
            'total' => $total,
            'unrestricted' => $isAdministrator,
        ]);
    }

    public function photo(Request $request, CareerProfile $profile): StreamedResponse
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $isAdministrator = in_array(CareerRoleRegistry::Administrator, $actor->roles, true);
        abort_unless(($isAdministrator || $profile->visible_in_alumni_directory) && $profile->alumni_number !== null && $profile->photo_path !== null, 404);
        abort_unless(Storage::disk('career_private')->exists($profile->photo_path), 404);

        return Storage::disk('career_private')->response($profile->photo_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
