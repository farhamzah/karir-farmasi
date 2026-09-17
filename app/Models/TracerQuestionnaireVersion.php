<?php

namespace App\Models;

use Database\Factories\TracerQuestionnaireVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TracerQuestionnaireVersion extends Model
{
    /** @use HasFactory<TracerQuestionnaireVersionFactory> */
    use HasFactory;

    protected $fillable = ['tracer_period_id', 'version', 'questions', 'published_at', 'created_by_core_user_id'];

    protected $hidden = ['created_by_core_user_id'];

    public function period(): BelongsTo
    {
        return $this->belongsTo(TracerPeriod::class, 'tracer_period_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TracerSubmission::class);
    }

    protected function casts(): array
    {
        return ['questions' => 'array', 'published_at' => 'datetime'];
    }
}
