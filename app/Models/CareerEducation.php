<?php

namespace App\Models;

use Database\Factories\CareerEducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerEducation extends OwnedProfileModel
{
    /** @use HasFactory<CareerEducationFactory> */
    use HasFactory;

    protected $table = 'career_educations';

    protected $fillable = ['institution_name', 'program_name', 'degree', 'gpa', 'start_year', 'end_year', 'status', 'source', 'source_reference', 'verified_at', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['gpa' => 'decimal:2', 'verified_at' => 'datetime', 'is_visible' => 'boolean'];
    }
}
