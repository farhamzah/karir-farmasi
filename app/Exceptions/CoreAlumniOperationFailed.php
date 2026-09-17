<?php

namespace App\Exceptions;

use RuntimeException;

class CoreAlumniOperationFailed extends RuntimeException
{
    /** @param array<string, mixed> $errors */
    public function __construct(
        string $message,
        public readonly int $status = 503,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
