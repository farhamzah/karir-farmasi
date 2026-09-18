<?php

namespace App\Jobs;

use App\Models\CareerJob;

final class JobPresenter
{
    public function card(CareerJob $job): array
    {
        return [
            'reference' => $job->public_reference,
            'title' => $job->title,
            'employer' => $job->employer_display_name,
            'employment_type' => str($job->employment_type)->replace('_', ' ')->title()->toString(),
            'work_mode' => str($job->work_mode)->title()->toString(),
            'city' => $job->city,
            'expires_at' => $job->expires_at?->toDateString(),
            'source' => [
                'type' => $job->source_type,
                'name' => $job->source_name,
                'verified' => $job->source_verified_at !== null,
            ],
            'tags' => $job->tags->pluck('label')->all(),
            'possible_duplicate' => $job->possible_duplicate,
            'application_method' => $job->application_method,
            'flyer_url' => $job->flyer_path ? route('job-flyers.show', $job->public_reference) : null,
            'flyer_alt_text' => $job->flyer_alt_text,
        ];
    }

    public function detail(CareerJob $job): array
    {
        return $this->card($job) + [
            'description' => $job->description,
            'requirements' => $job->requirements,
            'responsibilities' => $job->responsibilities,
            'education_requirement' => $job->education_requirement,
            'experience_requirement' => $job->experience_requirement,
            'location_text' => $job->location_text,
            'salary' => $job->salary_visible ? ['min' => $job->salary_min, 'max' => $job->salary_max] : null,
            'openings' => $job->openings,
            'application_method' => $job->application_method,
            'application_instruction' => $job->application_instruction,
            'external_apply_url' => $job->application_method === 'external_url' ? $job->external_apply_url : null,
            'external_apply_email' => in_array($job->application_method, ['email_instruction', 'email'], true) ? $job->external_apply_email : null,
        ];
    }
}
