<?php

namespace App\Data;

final readonly class CoreStudyProgram
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
    ) {}
}
