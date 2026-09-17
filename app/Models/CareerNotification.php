<?php

namespace App\Models;

use Database\Factories\CareerNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CareerNotification extends Model
{
    /** @use HasFactory<CareerNotificationFactory> */
    use HasFactory;

    protected $fillable = ['recipient_type', 'recipient_reference', 'type', 'title', 'body', 'action_url', 'context', 'read_at'];

    protected static function booted(): void
    {
        static::creating(fn (self $notification) => $notification->public_reference ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['context' => 'array', 'read_at' => 'datetime'];
    }
}
