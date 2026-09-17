<?php

namespace App\Models;

use Database\Factories\CvSectionPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvSectionPreference extends Model
{
    /** @use HasFactory<CvSectionPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['career_cv_id', 'section_key', 'enabled', 'sort_order', 'display_title'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CareerCv::class, 'career_cv_id');
    }
}
