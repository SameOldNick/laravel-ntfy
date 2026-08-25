<?php

namespace SameOldNick\Ntfy\Channels;

use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Ntfy\Message;
use SameOldNick\Ntfy\Contracts\NtfyNotification;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Services\Ntfy;

class NtfyChannel
{
    /**
     * Create a new notification channel instance.
     */
    public function __construct(
        protected readonly Ntfy $ntfy,
    ) {
        //
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): ?Response
    {
        if (! $this->isEnabled()) {
            Log::warning('NtfyChannel: Ntfy is not enabled in the configuration. Skipping notification.', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return null;
        }

        $message = $this->toMessage($notifiable, $notification);

        if (! $message instanceof Message) {
            // Notification doesn't have a toNtfy method or doesn't implement NtfyNotification contract
            Log::warning('NtfyChannel: Notification does not implement NtfyNotification contract or have a toNtfy method. Skipping notification.', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return null;
        }

        $routeTo = method_exists($notifiable, 'routeNotificationFor') ? $notifiable->routeNotificationFor('ntfy', $notification) : null;

        if (is_array($routeTo)) {
            $routeTo = ServerInfo::fromArray($routeTo);
        }

        if (! $routeTo instanceof ServerInfo) {
            // If $routeTo is not a ServerInfo instance, log a warning and skip sending the notification
            Log::warning('NtfyChannel: routeNotificationFor did not return server info for notifiable', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return null;
        }

        return $this->ntfy->sendRequest($message, $routeTo);
    }

    /**
     * Check if ntfy notification channel is enabled in the configuration.
     */
    public function isEnabled(): bool
    {
        return $this->ntfy->isChannelEnabled();
    }

    /**
     * Get the ntfy message for the given notifiable.
     *
     * @return Message|null
     */
    protected function toMessage(object $notifiable, Notification $notification)
    {
        // Checks if the notification implements the NtfyNotification contract or has a toNtfy method
        // The latter is for consistency with other notification channels in Laravel
        if ($notification instanceof NtfyNotification || method_exists($notification, 'toNtfy')) {
            return $notification->toNtfy($notifiable);
        }

        return null;
    }
}
