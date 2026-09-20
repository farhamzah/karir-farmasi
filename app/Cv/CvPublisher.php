<?php

namespace App\Cv;

use App\Models\CareerCv;
use App\Models\CvPublishedRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CvPublisher
{
    public function __construct(private readonly CareerCvProjection $projection) {}

    public function publish(CareerCv $cv): CvPublishedRevision
    {
        return DB::transaction(function () use ($cv): CvPublishedRevision {
            $locked = CareerCv::query()->whereKey($cv->getKey())->lockForUpdate()->firstOrFail();
            $snapshot = $this->publicSnapshot($this->projection->preview($locked));
            $checksum = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $number = ((int) $locked->publishedRevisions()->max('revision_number')) + 1;
            [$photoPath, $photoMime] = $this->snapshotPhoto($locked, $number);

            $revision = $locked->publishedRevisions()->create([
                'public_id' => (string) Str::uuid(),
                'cv_template_version_id' => $locked->cv_template_version_id,
                'revision_number' => $number,
                'snapshot' => $snapshot,
                'content_checksum' => $checksum,
                'photo_path' => $photoPath,
                'photo_mime' => $photoMime,
                'published_at' => now(),
            ]);

            $locked->shareLinks()->where('follow_latest_published', true)->update(['current_revision_id' => $revision->id]);

            return $revision;
        });
    }

    public function draftChecksum(CareerCv $cv): string
    {
        $snapshot = $this->publicSnapshot($this->projection->preview($cv));

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @param array<string, mixed> $preview @return array<string, mixed> */
    private function publicSnapshot(array $preview): array
    {
        return collect($preview)->only([
            'professional_name', 'headline', 'email', 'whatsapp', 'city', 'linkedin_url',
            'portfolio_url', 'has_photo', 'open_to_work', 'template', 'sections',
        ])->all();
    }

    /** @return array{?string, ?string} */
    private function snapshotPhoto(CareerCv $cv, int $number): array
    {
        $profile = $cv->profile;
        if (! $profile->photo_path || ! Storage::disk('career_private')->exists($profile->photo_path)) {
            return [null, null];
        }

        $extension = pathinfo($profile->photo_path, PATHINFO_EXTENSION) ?: 'bin';
        $path = "cv-published/{$cv->getKey()}/revision-{$number}.{$extension}";
        Storage::disk('career_private')->copy($profile->photo_path, $path);

        return [$path, Storage::disk('career_private')->mimeType($path) ?: 'application/octet-stream'];
    }
}
