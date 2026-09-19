<?php

namespace Tests\Fakes;

use App\Contracts\CoreAlumniGateway;
use App\Exceptions\CoreAlumniOperationFailed;

class FakeCoreAlumniGateway implements CoreAlumniGateway
{
    public array $registered = [];

    public ?array $approved = null;

    public array $approvals = [];

    public array $failedApprovals = [];

    public ?array $rejected = null;

    public array $statusResponse = ['reference' => 'KARIR-SYN-001', 'status' => 'pending'];

    public function register(array $data): array
    {
        $this->registered = $data;

        return ['reference' => 'KARIR-SYN-001', 'status' => 'pending'];
    }

    public function status(string $reference): array
    {
        return $this->statusResponse;
    }

    public function registrations(?string $status = null, int $page = 1): array
    {
        return ['data' => [[
            'reference' => 'KARIR-SYN-001', 'student_number' => 'SYN-001',
            'full_name' => 'Alumni Sintetis', 'status' => $status ?? 'pending',
        ]], 'meta' => ['current_page' => $page]];
    }

    public function registration(string $reference): array
    {
        $suffix = substr($reference, -3);

        return ['reference' => $reference, 'student_number' => 'SYN-'.$suffix,
            'full_name' => $reference === 'KARIR-SYN-001' ? 'Alumni Sintetis' : 'Alumni Sintetis '.$suffix,
            'graduation_year' => 2025, 'status' => 'pending'];
    }

    public function approve(string $reference, string $approverCoreUserId): array
    {
        if (in_array($reference, $this->failedApprovals, true)) {
            throw new CoreAlumniOperationFailed('Approval sintetis gagal.');
        }

        $this->approved = [$reference, $approverCoreUserId];
        $this->approvals[] = [$reference, $approverCoreUserId];

        return ['reference' => $reference, 'status' => 'approved', 'core_user_id' => 'fixture-alumni-core-'.strtolower(substr($reference, -3))];
    }

    public function reject(string $reference, string $approverCoreUserId, string $reason): array
    {
        $this->rejected = [$reference, $approverCoreUserId, $reason];

        return ['reference' => $reference, 'status' => 'rejected'];
    }
}
