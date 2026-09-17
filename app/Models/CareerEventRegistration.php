<?php

namespace App\Models;

use App\Contracts\OwnedCareerResource;
use Database\Factories\CareerEventRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CareerEventRegistration extends Model implements OwnedCareerResource
{
    /** @use HasFactory<CareerEventRegistrationFactory> */
    use HasFactory;

    protected $fillable = ['career_event_id', 'career_profile_id', 'role', 'status', 'registered_at', 'attended_at', 'completed_at', 'cancelled_at'];

    protected $hidden = ['career_profile_id'];

    protected function casts(): array
    {
        return ['registered_at' => 'datetime', 'attended_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CareerEvent::class, 'career_event_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(CareerEventCertificate::class);
    }

    public function ownerCoreUserId(): string
    {
        return (string) $this->profile->core_user_id;
    }
}
