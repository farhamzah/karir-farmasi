<?php

namespace App\Models;

use Database\Factories\CareerProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerProject extends OwnedProfileModel
{
    /** @use HasFactory<CareerProjectFactory> */
    use HasFactory;

    protected $fillable = ['title', 'category', 'description', 'project_url', 'attachment_path', 'sort_order', 'is_visible'];

    protected $hidden = ['attachment_path'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }
}
