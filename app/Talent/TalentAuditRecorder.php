<?php

namespace App\Talent;

use App\Data\CareerActor;
use App\Models\CompanyUser;
use App\Models\TalentAccessAudit;

final class TalentAuditRecorder
{
    /** @param array<string, mixed> $filters */
    public function search(CompanyUser|CareerActor $actor, array $filters, int $count): void
    {
        TalentAccessAudit::query()->create([
            ...$this->actor($actor),
            'action' => 'talent.search',
            'query_fingerprint' => hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR)),
            'result_count' => $count,
            'filter_keys' => array_values(array_keys(array_filter($filters, fn ($value) => filled($value)))),
            'created_at' => now(),
        ]);
    }

    public function view(CompanyUser|CareerActor $actor, string $targetReference): void
    {
        TalentAccessAudit::query()->create([
            ...$this->actor($actor), 'action' => 'talent.profile.view',
            'target_reference' => $targetReference, 'created_at' => now(),
        ]);
    }

    /** @return array{actor_type:string, actor_reference:string, company_id:int|null} */
    private function actor(CompanyUser|CareerActor $actor): array
    {
        return $actor instanceof CompanyUser
            ? ['actor_type' => 'company', 'actor_reference' => $actor->public_reference, 'company_id' => $actor->company_id]
            : ['actor_type' => 'internal', 'actor_reference' => $actor->coreUserId, 'company_id' => null];
    }
}
