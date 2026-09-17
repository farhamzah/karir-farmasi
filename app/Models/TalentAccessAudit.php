<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalentAccessAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_type', 'actor_reference', 'company_id', 'action', 'target_reference', 'query_fingerprint', 'result_count', 'filter_keys', 'created_at'];

    protected $hidden = ['actor_reference'];

    protected function casts(): array
    {
        return ['filter_keys' => 'array', 'created_at' => 'datetime'];
    }
}
