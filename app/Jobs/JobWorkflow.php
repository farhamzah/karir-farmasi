<?php

namespace App\Jobs;

use App\Models\CareerJob;
use Illuminate\Support\Str;

final class JobWorkflow
{
    public function syncTags(CareerJob $job, string|array|null $tags): void
    {
        $values = is_array($tags) ? $tags : preg_split('/[,;\n]+/', (string) $tags);
        $labels = collect($values ?: [])->map(fn (mixed $value) => trim((string) $value))
            ->filter()->unique(fn (string $value) => Str::lower($value))->values();

        $job->tags()->delete();
        foreach ($labels as $label) {
            $job->tags()->create([
                'category' => 'skill_topic',
                'label' => $label,
                'normalized_label' => Str::of($label)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish(),
            ]);
        }
    }

    public function flagPossibleDuplicate(CareerJob $job): void
    {
        $duplicate = CareerJob::query()->whereKeyNot($job->id)
            ->where(function ($query) use ($job): void {
                $query->where(function ($match) use ($job): void {
                    $match->where('employer_display_name', $job->employer_display_name)
                        ->where('title', $job->title)
                        ->where('city', $job->city);
                });

                if ($job->source_reference) {
                    $query->orWhere('source_reference', $job->source_reference);
                }
            })
            ->latest('id')
            ->first();

        $job->update([
            'possible_duplicate' => $duplicate !== null,
            'duplicate_of_job_id' => $duplicate?->id,
        ]);
    }

    public function expireDueJobs(): int
    {
        return CareerJob::query()->where('status', 'published')->whereNotNull('expires_at')->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'closed_at' => now()]);
    }

    public function publish(CareerJob $job, string $reviewer, ?string $note = null): void
    {
        $job->update([
            'status' => 'published',
            'published_at' => $job->published_at ?? now(),
            'closed_at' => null,
            'reviewed_by' => $reviewer,
            'review_note' => $note,
        ]);
    }
}
