<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareerJobApplication extends Model
{
    protected $fillable = ['career_job_id', 'career_profile_id', 'career_cv_id', 'cv_template_version_id', 'method', 'status', 'cv_snapshot', 'snapshot_checksum', 'cover_letter', 'supporting_document_path', 'submitted_at', 'withdrawn_at'];

    protected $hidden = ['career_profile_id', 'career_cv_id', 'cv_template_version_id', 'supporting_document_path'];

    protected static function booted(): void
    {
        static::creating(fn (self $application) => $application->public_reference ??= (string) Str::uuid());
        static::updating(function (self $application): void {
            if ($application->isDirty(['cv_snapshot', 'snapshot_checksum', 'cv_template_version_id'])) {
                throw new \LogicException('Submitted application CV snapshots are immutable.');
            }
        });
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(CareerJob::class, 'career_job_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CareerCv::class, 'career_cv_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(CareerApplicationTransition::class)->orderBy('created_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CareerApplicationDocument::class);
    }

    public function employerFeedback(): HasMany
    {
        return $this->hasMany(EmployerFeedback::class);
    }

    protected function casts(): array
    {
        return ['cv_snapshot' => 'array', 'submitted_at' => 'datetime', 'withdrawn_at' => 'datetime'];
    }
}
