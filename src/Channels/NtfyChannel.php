<?php

namespace SameOldNick\Ntfy\Channels;

use Exception;
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
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->isEnabled()) {
            Log::warning('NtfyChannel: Ntfy is not enabled in the configuration. Skipping notification.', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return;
        }

        $message = $this->toMessage($notifiable, $notification);

        if (! $message instanceof Message) {
            // Notification doesn't have a toNtfy method or doesn't implement NtfyNotification contract
            Log::warning('NtfyChannel: Notification does not implement NtfyNotification contract or have a toNtfy method. Skipping notification.', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return;
        }

        $routeTo = null;

        if (method_exists($notifiable, 'routeNotificationFor')) {
            /**
             * TODO:
             *  - Rely on routeNotificationFor returning a ServerInfo instance
             *  - If it returns null, skip sending the notification
             *  - The topic should be set in the ServerInfo object returned by the notifiable
             *  - This allows logic to be encapsulated in the notifiable model and keeps the channel implementation simpler
             *  - It also prevents all notifiables notifications going to the global server/topic if they haven't set up their ntfy configuration yet, which could be a privacy concern
             */
            $routeTo = $notifiable->routeNotificationFor('ntfy', $notification);
        }

        if (! $routeTo instanceof ServerInfo) {
            // If routeNotificationFor doesn't return a ServerInfo instance, log a warning and skip sending the notification
            Log::warning('NtfyChannel: routeNotificationFor did not return a ServerInfo instance for notifiable', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return;
        }

        try {
            $this->ntfy->send($message, $routeTo);
        } catch (Exception $e) {
            // Log the error but don't throw an exception to avoid breaking the notification flow
            Log::error('Failed to send ntfy notification: '.$e->getMessage(), [
                'exception' => $e,
                'notifiable' => $notifiable,
                'notification' => $notification,
                'message' => $message->getData(),
            ]);

            throw $e; // Rethrow the exception to allow the notification system to handle it (e.g., retry, log, etc.)
        }
    }

    /**
     * Check if ntfy is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.ntfy.enabled', false);
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
