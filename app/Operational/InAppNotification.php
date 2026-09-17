<?php

namespace App\Operational;

use App\Models\CareerNotification;

class InAppNotification
{
    /** @param array<string, mixed> $context */
    public function send(string $recipientType, string $recipientReference, string $type, string $title, string $body, ?string $actionUrl = null, array $context = []): CareerNotification
    {
        return CareerNotification::query()->create([
            'recipient_type' => $recipientType,
            'recipient_reference' => $recipientReference,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'context' => $context,
        ]);
    }
}
