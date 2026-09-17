<?php

namespace App\Models;

use Database\Factories\LeadershipAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadershipAssignment extends Model
{
    /** @use HasFactory<LeadershipAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['actor_core_user_id', 'scope_type', 'scope_reference', 'scope_label', 'program_references', 'role_label', 'active', 'valid_from', 'valid_until'];

    protected $hidden = ['actor_core_user_id'];

    public function isCurrent(): bool
    {
        $today = now()->toDateString();

        return $this->active
            && ($this->valid_from === null || $this->valid_from->toDateString() <= $today)
            && ($this->valid_until === null || $this->valid_until->toDateString() >= $today);
    }

    protected function casts(): array
    {
        return ['program_references' => 'array', 'active' => 'boolean', 'valid_from' => 'date', 'valid_until' => 'date'];
    }
}
