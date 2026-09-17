<?php

namespace App\Models;

use Database\Factories\CareerJobImportBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareerJobImportBatch extends Model
{
    /** @use HasFactory<CareerJobImportBatchFactory> */
    use HasFactory;

    protected $fillable = ['actor_core_user_id', 'original_name', 'status', 'valid_count', 'invalid_count', 'duplicate_count', 'imported_at'];

    protected $hidden = ['actor_core_user_id'];

    protected static function booted(): void
    {
        static::creating(fn (self $batch) => $batch->public_reference ??= (string) Str::uuid());
    }

    public function rows(): HasMany
    {
        return $this->hasMany(CareerJobImportRow::class);
    }

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }
}
