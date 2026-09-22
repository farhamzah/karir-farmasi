<?php

namespace App\Cv;

use App\Models\CvPublishedRevision;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

final class CvSocialPreview
{
    public function image(CvPublishedRevision $revision): string
    {
        $directory = storage_path('app/private/career/cv-social-preview');
        File::ensureDirectoryExists($directory);
        $output = $directory.'/'.$revision->public_id.'.png';
        if (is_file($output) && filesize($output) > 1000) {
            return $output;
        }

        $browser = config('cv_exports.chromium_path');
        if (! is_string($browser) || ! is_file($browser)) {
            throw new RuntimeException('Browser untuk pratinjau CV belum tersedia.');
        }

        $work = $directory.'/work-'.bin2hex(random_bytes(12));
        File::ensureDirectoryExists($work);
        $html = $work.'/preview.html';
        $photo = null;
        if (($revision->snapshot['has_photo'] ?? false) && $revision->photo_path
            && Storage::disk('career_private')->exists($revision->photo_path)) {
            $mime = $revision->photo_mime ?: 'image/jpeg';
            $photo = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('career_private')->get($revision->photo_path));
        }
        File::put($html, view('cv.social-preview', ['cv' => $revision->snapshot, 'photo' => $photo])->render());

        $process = new Process([
            $browser, '--headless=new', '--disable-gpu', '--disable-dev-shm-usage', '--no-sandbox',
            '--disable-extensions', '--disable-sync', '--no-first-run', '--allow-file-access-from-files',
            '--hide-scrollbars', '--force-device-scale-factor=1', '--window-size=1200,630',
            '--user-data-dir='.$work.'/browser-profile', '--screenshot='.$output,
            'file:///'.str_replace('\\', '/', $html),
        ]);
        $process->setTimeout((float) config('cv_exports.timeout_seconds', 45));
        try {
            $process->mustRun();
        } finally {
            File::deleteDirectory($work);
        }
        if (! is_file($output) || filesize($output) < 1000) {
            throw new RuntimeException('Gambar pratinjau CV tidak berhasil dibuat.');
        }

        return $output;
    }
}
