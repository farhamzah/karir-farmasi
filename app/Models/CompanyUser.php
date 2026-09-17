<?php

namespace App\Models;

use Database\Factories\CompanyUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompanyUser extends Model
{
    /** @use HasFactory<CompanyUserFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'email', 'password', 'role', 'active', 'last_login_at'];

    protected $hidden = ['password'];

    protected static function booted(): void
    {
        static::creating(fn (CompanyUser $user) => $user->public_reference ??= (string) Str::uuid());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'last_login_at' => 'datetime', 'password' => 'hashed'];
    }
}
