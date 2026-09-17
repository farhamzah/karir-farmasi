<?php

namespace App\Models;

use App\Contracts\OwnedCareerResource;
use Database\Factories\CareerEventCertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerEventCertificate extends Model implements OwnedCareerResource
{
    /** @use HasFactory<CareerEventCertificateFactory> */
    use HasFactory;

    protected $fillable = ['career_event_registration_id', 'certificate_number', 'verification_code', 'issued_at', 'file_path', 'file_mime', 'issued_by_core_user_id', 'revoked_at', 'revoked_by_core_user_id', 'revocation_reason'];

    protected $hidden = ['file_path', 'verification_code', 'issued_by_core_user_id', 'revoked_by_core_user_id'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CareerEventRegistration::class, 'career_event_registration_id');
    }

    public function ownerCoreUserId(): string
    {
        return $this->registration->ownerCoreUserId();
    }
}
