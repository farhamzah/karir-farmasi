<?php

namespace App\Models;

use Database\Factories\CareerJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareerJob extends Model
{
    /** @use HasFactory<CareerJobFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'created_by_type', 'created_by_reference', 'employer_display_name', 'title', 'employment_type',
        'work_mode', 'city', 'location_text', 'description', 'requirements', 'responsibilities',
        'education_requirement', 'experience_requirement', 'salary_min', 'salary_max', 'salary_visible', 'openings',
        'status', 'application_method', 'external_apply_url', 'external_apply_email', 'application_instruction',
        'source_type', 'source_name', 'source_reference', 'received_at', 'source_verified_at',
        'source_verified_by', 'internal_notes', 'source_attachment_path', 'published_at', 'expires_at', 'review_at',
        'closed_at', 'duplicate_of_job_id', 'possible_duplicate', 'reviewed_by', 'review_note',
    ];

    protected $hidden = ['created_by_reference', 'source_verified_by', 'internal_notes', 'source_attachment_path'];

    protected static function booted(): void
    {
        static::creating(fn (CareerJob $job) => $job->public_reference ??= (string) Str::uuid());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(CareerJobTag::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CareerJobApplication::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CareerJobInvitation::class);
    }

    public function externalActions(): HasMany
    {
        return $this->hasMany(CareerJobExternalAction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CareerJobReport::class);
    }

    public function isPublishedAndOpen(): bool
    {
        return $this->status === 'published' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function scopeVisibleToCandidates(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(fn (Builder $builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    protected function casts(): array
    {
        return ['salary_visible' => 'boolean', 'possible_duplicate' => 'boolean', 'received_at' => 'datetime',
            'source_verified_at' => 'datetime', 'published_at' => 'datetime', 'expires_at' => 'datetime',
            'review_at' => 'datetime', 'closed_at' => 'datetime'];
    }
}
