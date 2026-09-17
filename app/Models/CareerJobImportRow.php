<?php

namespace App\Models;

use Database\Factories\CareerJobImportRowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerJobImportRow extends Model
{
    /** @use HasFactory<CareerJobImportRowFactory> */
    use HasFactory;

    protected $fillable = ['career_job_import_batch_id', 'row_number', 'payload', 'errors', 'possible_duplicate_job_id', 'imported_job_id'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CareerJobImportBatch::class, 'career_job_import_batch_id');
    }

    protected function casts(): array
    {
        return ['payload' => 'array', 'errors' => 'array'];
    }
}
