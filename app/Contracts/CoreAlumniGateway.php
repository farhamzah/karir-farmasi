<?php

namespace App\Contracts;

interface CoreAlumniGateway
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function register(array $data): array;

    /** @return array<string, mixed> */
    public function status(string $reference): array;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, mixed>} */
    public function registrations(?string $status = null, int $page = 1): array;

    /** @return array<string, mixed> */
    public function registration(string $reference): array;

    /** @return array<string, mixed> */
    public function approve(string $reference, string $approverCoreUserId): array;

    /** @return array<string, mixed> */
    public function reject(string $reference, string $approverCoreUserId, string $reason): array;
}
