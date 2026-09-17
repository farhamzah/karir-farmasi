<?php

namespace App\Models;

use Database\Factories\CareerSkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerSkill extends OwnedProfileModel
{
    /** @use HasFactory<CareerSkillFactory> */
    use HasFactory;

    protected $fillable = ['name', 'category', 'level', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
