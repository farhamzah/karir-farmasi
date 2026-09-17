<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CareerJobInvitation extends Model
{
    protected $fillable = ['company_id', 'company_user_id', 'career_job_id', 'career_profile_id', 'message', 'status', 'viewed_at', 'responded_at', 'expires_at'];

    protected $hidden = ['company_user_id', 'career_profile_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $invitation) => $invitation->public_reference ??= (string) Str::uuid());
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(CareerJob::class, 'career_job_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(CareerProfile::class, 'career_profile_id');
    }

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime', 'responded_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
