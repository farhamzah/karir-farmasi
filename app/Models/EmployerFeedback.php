<?php

namespace App\Models;

use Database\Factories\EmployerFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployerFeedback extends Model
{
    /** @use HasFactory<EmployerFeedbackFactory> */
    use HasFactory;

    protected $table = 'employer_feedback';

    protected $fillable = ['career_job_application_id', 'company_id', 'company_user_id', 'career_profile_id', 'relationship', 'ratings', 'strengths', 'development_notes', 'comment', 'submitted_at'];

    protected $hidden = ['career_profile_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $feedback) => $feedback->public_reference ??= (string) Str::uuid());
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CareerJobApplication::class, 'career_job_application_id');
    }

    protected function casts(): array
    {
        return ['ratings' => 'array', 'submitted_at' => 'datetime'];
    }
}
