<?php

namespace App\Models;

use Database\Factories\CareerOrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerOrganization extends OwnedProfileModel
{
    /** @use HasFactory<CareerOrganizationFactory> */
    use HasFactory;

    protected $fillable = ['organization', 'role', 'start_date', 'end_date', 'description', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_visible' => 'boolean'];
    }
}
