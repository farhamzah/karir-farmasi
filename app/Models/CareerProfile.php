<?php

namespace App\Models;

use App\Contracts\OwnedCareerResource;
use Database\Factories\CareerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CareerProfile extends Model implements OwnedCareerResource
{
    /** @use HasFactory<CareerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'core_user_id', 'talent_reference', 'professional_name', 'headline', 'professional_summary',
        'professional_email', 'whatsapp', 'city', 'linkedin_url', 'portfolio_url',
        'photo_path', 'open_to_work', 'profile_visibility', 'section_visibility',
        'last_confirmed_at', 'discoverable_by_verified_companies',
        'discoverable_by_internal_leadership', 'discoverability_updated_at',
    ];

    protected $hidden = ['core_user_id', 'photo_path'];

    protected static function booted(): void
    {
        static::creating(fn (CareerProfile $profile) => $profile->talent_reference ??= (string) Str::uuid());
    }

    public function ownerCoreUserId(): string
    {
        return $this->core_user_id;
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CareerEducation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CareerExperience::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CareerSkill::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(CareerCertification::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(CareerOrganization::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CareerProject::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(CareerPublication::class);
    }

    public function languages(): HasMany
    {
        return $this->hasMany(CareerLanguage::class);
    }

    public function jobPreference(): HasOne
    {
        return $this->hasOne(CareerJobPreference::class);
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(CareerCv::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(CareerJobApplication::class);
    }

    public function jobInvitations(): HasMany
    {
        return $this->hasMany(CareerJobInvitation::class);
    }

    public function jobBookmarks(): HasMany
    {
        return $this->hasMany(CareerJobBookmark::class);
    }

    public function tracerSubmissions(): HasMany
    {
        return $this->hasMany(TracerSubmission::class);
    }

    public function employerFeedback(): HasMany
    {
        return $this->hasMany(EmployerFeedback::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(CareerEventRegistration::class);
    }

    public function completedEventRegistrations(): HasMany
    {
        return $this->eventRegistrations()->whereNotNull('completed_at');
    }

    public function eventCertificates(): HasManyThrough
    {
        return $this->hasManyThrough(
            CareerEventCertificate::class,
            CareerEventRegistration::class,
            'career_profile_id',
            'career_event_registration_id',
        )->whereNull('career_event_certificates.revoked_at');
    }

    protected function casts(): array
    {
        return [
            'open_to_work' => 'boolean',
            'section_visibility' => 'array',
            'last_confirmed_at' => 'datetime',
            'discoverable_by_verified_companies' => 'boolean',
            'discoverable_by_internal_leadership' => 'boolean',
            'discoverability_updated_at' => 'datetime',
        ];
    }
}
