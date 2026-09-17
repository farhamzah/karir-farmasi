<?php

namespace App\Models;

use Database\Factories\TracerSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TracerSubmission extends Model
{
    /** @use HasFactory<TracerSubmissionFactory> */
    use HasFactory;

    protected $fillable = ['tracer_period_id', 'tracer_questionnaire_version_id', 'career_profile_id', 'status', 'answers', 'profile_prefill', 'submission_snapshot', 'snapshot_checksum', 'submitted_at', 'reopened_at', 'reopened_by_core_user_id'];

    protected $hidden = ['career_profile_id', 'reopened_by_core_user_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $submission) => $submission->public_reference ??= (string) Str::uuid());
        static::updating(function (self $submission): void {
            if ($submission->getOriginal('status') === 'submitted' && $submission->isDirty(['answers', 'submission_snapshot', 'snapshot_checksum'])) {
                throw new \LogicException('Submitted tracer snapshots are immutable until explicitly reopened.');
            }
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TracerPeriod::class, 'tracer_period_id');
    }

    public function questionnaireVersion(): BelongsTo
    {
        return $this->belongsTo(TracerQuestionnaireVersion::class, 'tracer_questionnaire_version_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    protected function casts(): array
    {
        return ['answers' => 'array', 'profile_prefill' => 'array', 'submission_snapshot' => 'array', 'submitted_at' => 'datetime', 'reopened_at' => 'datetime'];
    }
}
