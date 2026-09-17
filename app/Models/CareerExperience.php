<?php

namespace App\Models;

use Database\Factories\CareerExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerExperience extends OwnedProfileModel
{
    /** @use HasFactory<CareerExperienceFactory> */
    use HasFactory;

    protected $fillable = ['type', 'organization', 'title', 'location', 'start_date', 'end_date', 'currently_active', 'description', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'currently_active' => 'boolean', 'is_visible' => 'boolean'];
    }
}
