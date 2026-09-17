<?php

namespace App\Models;

use Database\Factories\CvTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplate extends Model
{
    /** @use HasFactory<CvTemplateFactory> */
    use HasFactory;

    protected $fillable = ['key', 'name', 'description', 'active', 'category', 'base_template_key', 'display_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CvTemplateVersion::class);
    }
}
