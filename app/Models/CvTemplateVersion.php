<?php

namespace App\Models;

use Database\Factories\CvTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplateVersion extends Model
{
    /** @use HasFactory<CvTemplateVersionFactory> */
    use HasFactory;

    protected $fillable = ['cv_template_id', 'version', 'configuration', 'status', 'published_at'];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'published_at' => 'datetime'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CvTemplate::class, 'cv_template_id');
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(CareerCv::class);
    }
}
