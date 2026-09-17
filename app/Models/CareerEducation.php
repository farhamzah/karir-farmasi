<?php

namespace App\Models;

use Database\Factories\CareerEducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerEducation extends OwnedProfileModel
{
    /** @use HasFactory<CareerEducationFactory> */
    use HasFactory;

    protected $table = 'career_educations';

    protected $fillable = ['institution_name', 'program_name', 'degree', 'start_year', 'end_year', 'status', 'source', 'source_reference', 'verified_at', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'is_visible' => 'boolean'];
    }
}
