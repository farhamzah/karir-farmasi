<?php

namespace App\Models;

use Database\Factories\CareerJobPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerJobPreference extends OwnedProfileModel
{
    /** @use HasFactory<CareerJobPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['target_roles', 'employment_types', 'preferred_locations', 'willing_to_relocate', 'availability_date'];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'employment_types' => 'array',
            'preferred_locations' => 'array',
            'willing_to_relocate' => 'boolean',
            'availability_date' => 'date',
        ];
    }
}
