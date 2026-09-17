<?php

namespace App\Models;

use Database\Factories\CareerLanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerLanguage extends OwnedProfileModel
{
    /** @use HasFactory<CareerLanguageFactory> */
    use HasFactory;

    protected $fillable = ['language', 'proficiency', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
