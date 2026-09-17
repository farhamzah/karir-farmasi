<?php

namespace App\Models;

use Database\Factories\CareerEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerEvent extends Model
{
    /** @use HasFactory<CareerEventFactory> */
    use HasFactory;

    protected $fillable = ['title', 'slug', 'reference', 'event_type', 'organizer', 'description', 'starts_at', 'ends_at', 'location_type', 'location_text', 'capacity', 'registration_opens_at', 'registration_closes_at', 'status', 'certificate_enabled', 'created_by_core_user_id'];

    protected $hidden = ['created_by_core_user_id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_opens_at' => 'datetime', 'registration_closes_at' => 'datetime', 'certificate_enabled' => 'boolean'];
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(CareerEventTopic::class, 'career_event_topic');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CareerEventRegistration::class);
    }
}
