<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = ['legal_name', 'display_name', 'business_sector', 'company_size', 'website', 'city', 'description', 'verification_status', 'verified_at', 'verified_by_core_user_id', 'decision_note', 'active'];

    protected $hidden = ['verified_by_core_user_id'];

    protected static function booted(): void
    {
        static::creating(fn (Company $company) => $company->public_reference ??= (string) Str::uuid());
    }

    public function users(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(CompanyShortlist::class);
    }

    public function canSearchTalent(): bool
    {
        return $this->active && $this->verification_status === 'verified';
    }

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'active' => 'boolean'];
    }
}
