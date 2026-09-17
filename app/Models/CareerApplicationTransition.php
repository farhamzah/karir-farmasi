<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerApplicationTransition extends Model
{
    public $timestamps = false;

    protected $fillable = ['career_job_application_id', 'from_status', 'to_status', 'actor_type', 'actor_reference', 'note', 'created_at'];

    protected $hidden = ['actor_reference'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CareerJobApplication::class, 'career_job_application_id');
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
