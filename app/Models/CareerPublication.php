<?php

namespace App\Models;

use Database\Factories\CareerPublicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerPublication extends OwnedProfileModel
{
    /** @use HasFactory<CareerPublicationFactory> */
    use HasFactory;

    protected $fillable = ['title', 'publication_name', 'published_on', 'url', 'doi', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'is_visible' => 'boolean'];
    }
}
