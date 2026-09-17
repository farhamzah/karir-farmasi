<?php

namespace App\Data;

final readonly class CoreDirectoryPerson
{
    public function __construct(
        public string $coreUserId,
        public string $displayName,
    ) {}
}
