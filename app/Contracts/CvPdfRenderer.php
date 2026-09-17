<?php

namespace App\Contracts;

interface CvPdfRenderer
{
    /** @param array<string, mixed> $snapshot */
    public function render(array $snapshot, ?string $photoDataUri = null): string;
}
