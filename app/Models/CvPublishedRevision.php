<?php

namespace App\Models;

use Database\Factories\CvPublishedRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvPublishedRevision extends Model
{
    /** @use HasFactory<CvPublishedRevisionFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id', 'career_cv_id', 'cv_template_version_id', 'revision_number', 'snapshot',
        'content_checksum', 'photo_path', 'photo_mime', 'published_at',
    ];

    protected $hidden = ['career_cv_id', 'cv_template_version_id', 'photo_path'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Published CV revisions are immutable.'));
    }

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'published_at' => 'immutable_datetime'];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CareerCv::class, 'career_cv_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(CvTemplateVersion::class, 'cv_template_version_id');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(CvShareLink::class, 'current_revision_id');
    }
}
