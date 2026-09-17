<?php

namespace App\Models;

use Database\Factories\TracerPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TracerPeriod extends Model
{
    /** @use HasFactory<TracerPeriodFactory> */
    use HasFactory;

    protected $fillable = ['title', 'cohort', 'program_reference', 'faculty_reference', 'starts_on', 'ends_on', 'status', 'created_by_core_user_id'];

    protected $hidden = ['created_by_core_user_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $period) => $period->public_reference ??= (string) Str::uuid());
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TracerQuestionnaireVersion::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TracerSubmission::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published' && $this->starts_on->startOfDay()->lte(now()) && $this->ends_on->endOfDay()->gte(now());
    }

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }
}
