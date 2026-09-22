<?php

namespace App\Cv;

use App\Contracts\CvPdfRenderer;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

final class ChromiumCvPdfRenderer implements CvPdfRenderer
{
    public function render(array $snapshot, ?string $photoDataUri = null): string
    {
        $executable = config('cv_exports.chromium_path');
        if (! is_string($executable) || ! is_file($executable)) {
            throw new RuntimeException('Chromium-compatible browser belum dikonfigurasi.');
        }

        $exportDirectory = storage_path('app/private/career/cv-export');
        $directory = $exportDirectory.'/work-'.bin2hex(random_bytes(12));
        File::ensureDirectoryExists($directory);
        $htmlPath = $directory.'/document.html';
        $pdfPath = $exportDirectory.'/'.bin2hex(random_bytes(12)).'.pdf';
        File::put($htmlPath, view('cv.document', ['cv' => $snapshot, 'photoDataUri' => $photoDataUri])->render());

        $process = new Process([
            $executable, '--headless=new', '--disable-gpu', '--disable-software-rasterizer',
            '--disable-dev-shm-usage', '--no-sandbox', '--no-pdf-header-footer',
            '--disable-extensions', '--disable-sync', '--no-first-run', '--allow-file-access-from-files',
            '--user-data-dir='.$directory.'/browser-profile', '--print-to-pdf='.$pdfPath,
            'file:///'.str_replace('\\', '/', $htmlPath),
        ]);
        $process->setTimeout((float) config('cv_exports.timeout_seconds', 45));
        try {
            $process->mustRun();
        } finally {
            File::deleteDirectory($directory);
        }
        if (! is_file($pdfPath) || filesize($pdfPath) < 500) {
            throw new RuntimeException('Chromium tidak menghasilkan PDF valid.');
        }

        return $pdfPath;
    }
}
