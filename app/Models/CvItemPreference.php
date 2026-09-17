<?php

namespace App\Models;

use Database\Factories\CvItemPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvItemPreference extends Model
{
    /** @use HasFactory<CvItemPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['career_cv_id', 'section_key', 'source_item_id', 'enabled', 'sort_order'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CareerCv::class, 'career_cv_id');
    }
}
