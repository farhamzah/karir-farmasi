<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerNotification;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function candidate(Request $request): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return $this->page('candidate', $actor->coreUserId, '/dashboard');
    }

    public function company(Request $request): Response
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);

        return $this->page('company_user', $actor->public_reference, '/company/dashboard');
    }

    public function readCandidate(Request $request, string $reference): RedirectResponse
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $this->markRead('candidate', $actor->coreUserId, $reference);

        return back();
    }

    public function readCompany(Request $request, string $reference): RedirectResponse
    {
        /** @var CompanyUser $actor */
        $actor = $request->attributes->get(CompanyUser::class);
        $this->markRead('company_user', $actor->public_reference, $reference);

        return back();
    }

    private function page(string $type, string $recipient, string $home): Response
    {
        $items = CareerNotification::query()->where('recipient_type', $type)->where('recipient_reference', $recipient)
            ->latest()->limit(100)->get()->map(fn (CareerNotification $item): array => [
                'reference' => $item->public_reference,
                'type' => $item->type,
                'title' => $item->title,
                'body' => $item->body,
                'action_url' => $item->action_url,
                'read' => $item->read_at !== null,
                'created_at' => $item->created_at->diffForHumans(),
            ])->all();

        return Inertia::render('Notifications/Index', ['notifications' => $items, 'home' => $home, 'audience' => $type]);
    }

    private function markRead(string $type, string $recipient, string $reference): void
    {
        CareerNotification::query()->where('recipient_type', $type)->where('recipient_reference', $recipient)
            ->where('public_reference', $reference)->firstOrFail()->update(['read_at' => now()]);
    }
}
