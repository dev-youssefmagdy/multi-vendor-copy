<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Shared by AdminNotification / TenantNotification: the JSON shape the bell
 * dropdown renders. `openUrl` is the panel's "open" route, which marks the
 * notification read and then redirects to the linked page.
 */
trait FormatsNotificationFeed
{
    abstract public function targetUrl(): ?string;

    /** @return array<string, mixed> */
    public function toFeedItem(string $openUrl): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => (bool) $this->is_read,
            'has_link' => $this->targetUrl() !== null,
            'open_url' => $openUrl,
            'created_at' => $this->created_at?->toIso8601String(),
            'time' => $this->created_at?->diffForHumans(),
        ];
    }
}
