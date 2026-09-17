<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentProfileIndex extends Model
{
    protected $table = 'talent_profile_indexes';

    protected $fillable = ['career_profile_id', 'professional_name', 'headline', 'city', 'education_level', 'education_program', 'graduation_year', 'experience_types', 'sectors', 'skills', 'certifications', 'event_topics', 'event_roles', 'project_tags', 'publication_keywords', 'languages', 'target_roles', 'preferred_locations', 'normalized_terms', 'open_to_work', 'willing_to_relocate', 'availability_date', 'last_confirmed_at'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    protected function casts(): array
    {
        return ['experience_types' => 'array', 'sectors' => 'array', 'skills' => 'array', 'certifications' => 'array', 'event_topics' => 'array', 'event_roles' => 'array', 'project_tags' => 'array', 'publication_keywords' => 'array', 'languages' => 'array', 'target_roles' => 'array', 'preferred_locations' => 'array', 'open_to_work' => 'boolean', 'willing_to_relocate' => 'boolean', 'availability_date' => 'date', 'last_confirmed_at' => 'datetime'];
    }
}
