<?php

namespace App\Http\Controllers;

use App\Models\CareerEvent;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EventFlyerController extends Controller
{
    public function show(CareerEvent $event): StreamedResponse
    {
        abort_unless($event->status === 'published' && $event->flyer_path !== null, 404);

        return $this->response($event, 'public, max-age=300');
    }

    public function admin(CareerEvent $event): StreamedResponse
    {
        abort_if($event->flyer_path === null, 404);

        return $this->response($event, 'private, no-store');
    }

    private function response(CareerEvent $event, string $cacheControl): StreamedResponse
    {
        abort_unless(Storage::disk('career_private')->exists($event->flyer_path), 404);

        return Storage::disk('career_private')->response($event->flyer_path, null, [
            'Cache-Control' => $cacheControl,
            'Content-Type' => $event->flyer_mime ?? 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
