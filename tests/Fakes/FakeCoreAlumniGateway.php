<?php

namespace Tests\Fakes;

use App\Contracts\CoreAlumniGateway;

class FakeCoreAlumniGateway implements CoreAlumniGateway
{
    public array $registered = [];

    public ?array $approved = null;

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
        return ['reference' => $reference, 'student_number' => 'SYN-001',
            'full_name' => 'Alumni Sintetis', 'status' => 'pending'];
    }

    public function approve(string $reference, string $approverCoreUserId): array
    {
        $this->approved = [$reference, $approverCoreUserId];

        return ['reference' => $reference, 'status' => 'approved'];
    }

    public function reject(string $reference, string $approverCoreUserId, string $reason): array
    {
        $this->rejected = [$reference, $approverCoreUserId, $reason];

        return ['reference' => $reference, 'status' => 'rejected'];
    }
}
