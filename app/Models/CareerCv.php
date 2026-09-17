<?php

namespace App\Models;

use App\Contracts\OwnedCareerResource;
use Database\Factories\CareerCvFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class CareerCv extends Model implements OwnedCareerResource
{
    /** @use HasFactory<CareerCvFactory> */
    use HasFactory;

    protected $fillable = ['career_profile_id', 'cv_template_version_id', 'name', 'custom_headline', 'custom_summary', 'field_visibility', 'status'];

    protected $hidden = ['career_profile_id'];

    protected function casts(): array
    {
        return ['field_visibility' => 'array'];
    }

    protected static function booted(): void
    {
        static::deleting(function (CareerCv $cv): void {
            $cv->publishedRevisions()->whereNotNull('photo_path')->pluck('photo_path')->each(
                fn (string $path) => Storage::disk('career_private')->delete($path),
            );
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(CvTemplateVersion::class, 'cv_template_version_id');
    }

    public function sectionPreferences(): HasMany
    {
        return $this->hasMany(CvSectionPreference::class);
    }

    public function itemPreferences(): HasMany
    {
        return $this->hasMany(CvItemPreference::class);
    }

    public function publishedRevisions(): HasMany
    {
        return $this->hasMany(CvPublishedRevision::class);
    }

    public function latestPublishedRevision(): HasOne
    {
        return $this->hasOne(CvPublishedRevision::class)->ofMany('revision_number', 'max');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(CvShareLink::class);
    }

    public function ownerCoreUserId(): string
    {
        return (string) $this->profile->core_user_id;
    }
}
