<?php

namespace App\Models;

use Database\Factories\CvShareLinkFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CvShareLink extends Model
{
    /** @use HasFactory<CvShareLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id', 'career_cv_id', 'current_revision_id', 'token_hash', 'token_ciphertext',
        'label', 'active', 'follow_latest_published', 'allow_pdf_download', 'expires_at',
        'view_count', 'last_viewed_at', 'rotated_at',
    ];

    protected $hidden = ['career_cv_id', 'current_revision_id', 'token_hash', 'token_ciphertext'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean', 'follow_latest_published' => 'boolean',
            'allow_pdf_download' => 'boolean', 'expires_at' => 'immutable_datetime',
            'last_viewed_at' => 'immutable_datetime', 'rotated_at' => 'immutable_datetime',
        ];
    }

    public function cv(): BelongsTo
    {
        return $this->belongsTo(CareerCv::class, 'career_cv_id');
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(CvPublishedRevision::class, 'current_revision_id');
    }

    public function token(): string
    {
        return Crypt::decryptString($this->token_ciphertext);
    }

    public function safeToken(): ?string
    {
        try {
            return $this->token();
        } catch (DecryptException) {
            return null;
        }
    }

    public function accessible(): bool
    {
        return $this->active && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
