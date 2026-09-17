<?php

namespace App\Models;

use Database\Factories\CareerApplicationDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerApplicationDocument extends Model
{
    /** @use HasFactory<CareerApplicationDocumentFactory> */
    use HasFactory;

    protected $fillable = ['career_job_application_id', 'type', 'disk', 'path', 'original_name', 'mime', 'size', 'uploaded_at'];

    protected $hidden = ['path'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CareerJobApplication::class, 'career_job_application_id');
    }

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }
}
