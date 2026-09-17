<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerJobExternalAction extends Model
{
    protected $fillable = ['career_job_id', 'career_profile_id', 'action', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
