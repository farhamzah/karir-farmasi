<?php

namespace App\Operational;

use App\Models\CareerProfile;
use App\Models\TracerQuestionnaireVersion;
use App\Models\TracerSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TracerWorkflow
{
    /** @param array<string, mixed> $answers */
    public function saveDraft(TracerSubmission $submission, array $answers): TracerSubmission
    {
        if ($submission->status === 'submitted') {
            throw new \LogicException('Tracer submission has already been submitted.');
        }

        $submission->update(['answers' => $this->normalize($submission->questionnaireVersion, $answers, false), 'status' => 'draft']);

        return $submission->fresh();
    }

    /** @param array<string, mixed> $answers */
    public function submit(TracerSubmission $submission, array $answers): TracerSubmission
    {
        if ($submission->status === 'submitted') {
            throw new \LogicException('Tracer submission has already been submitted.');
        }

        $version = $submission->questionnaireVersion;
        $normalized = $this->normalize($version, $answers, true);
        $snapshot = ['questionnaire_version' => $version->version, 'questions' => $version->questions, 'answers' => $normalized, 'profile_prefill' => $submission->profile_prefill];

        return DB::transaction(function () use ($submission, $normalized, $snapshot): TracerSubmission {
            $submission->update([
                'answers' => $normalized,
                'submission_snapshot' => $snapshot,
                'snapshot_checksum' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $submission->fresh();
        });
    }

    /** @return array<string, mixed> */
    public function profilePrefill(CareerProfile $profile): array
    {
        return array_filter(['professional_name' => $profile->professional_name, 'city' => $profile->city], fn ($value) => $value !== null && $value !== '');
    }

    /** @param array<string, mixed> $answers
     * @return array<string, mixed>
     */
    private function normalize(TracerQuestionnaireVersion $version, array $answers, bool $requireComplete): array
    {
        $normalized = [];
        foreach ($version->questions as $question) {
            $key = (string) ($question['id'] ?? $question['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $value = $answers[$key] ?? null;
            if ($requireComplete && ($question['required'] ?? false) && ($value === null || $value === '')) {
                throw ValidationException::withMessages(["answers.$key" => 'Jawaban wajib diisi.']);
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
