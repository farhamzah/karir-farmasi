<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerJobReport extends Model
{
    protected $fillable = ['career_job_id', 'career_profile_id', 'reason', 'detail', 'status'];
}
