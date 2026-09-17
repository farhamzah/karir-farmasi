<?php

namespace App\Models;

use Database\Factories\CareerCertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CareerCertification extends OwnedProfileModel
{
    /** @use HasFactory<CareerCertificationFactory> */
    use HasFactory;

    protected $fillable = ['title', 'issuer', 'issue_date', 'expiry_date', 'credential_id', 'credential_url', 'attachment_path', 'sort_order', 'is_visible'];

    protected $hidden = ['attachment_path'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date', 'is_visible' => 'boolean'];
    }
}
