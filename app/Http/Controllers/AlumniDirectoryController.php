<?php

namespace App\Http\Controllers;

use App\Models\CareerProfile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlumniDirectoryController extends Controller
{
    public function index(): Response
    {
        $alumni = CareerProfile::query()
            ->where('visible_in_alumni_directory', true)
            ->whereNotNull('alumni_number')
            ->orderBy('alumni_number')
            ->get(['talent_reference', 'alumni_number', 'graduation_year', 'professional_name', 'photo_path'])
            ->map(fn (CareerProfile $profile): array => [
                'reference' => $profile->talent_reference,
                'name' => $profile->professional_name ?: 'Alumni Farmasi UBP',
                'nim' => $profile->alumni_number,
                'graduation_year' => $profile->graduation_year,
                'photo_url' => $profile->photo_path !== null ? route('alumni.photo', $profile) : null,
            ])
            ->all();

        return Inertia::render('Alumni/Index', ['alumni' => $alumni]);
    }

    public function photo(CareerProfile $profile): StreamedResponse
    {
        abort_unless($profile->visible_in_alumni_directory && $profile->alumni_number !== null && $profile->photo_path !== null, 404);
        abort_unless(Storage::disk('career_private')->exists($profile->photo_path), 404);

        return Storage::disk('career_private')->response($profile->photo_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
