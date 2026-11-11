<?php

namespace SameOldNick\Ntfy\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Ntfy\Message;
use SameOldNick\Ntfy\Contracts\NtfyNotification;
use SameOldNick\Ntfy\Contracts\ResolvesNtfyConfiguration;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;
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
        if (! $this->isEnabled($notifiable)) {
            Log::warning('NtfyChannel: Ntfy is not enabled. Skipping notification.', [
                'notifiable' => $notifiable,
                'notification' => $notification,
            ]);

            return null;
        }

        $message = $this->toMessage($notifiable, $notification);

        if (! ($message instanceof Message || $message instanceof MessageWithAttachment)) {
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
     * Check if the ntfy notification channel is enabled.
     *
     * Pass a notifiable to also honour the `enabled` flag stored on its configuration.
     */
    public function isEnabled(?object $notifiable = null): bool
    {
        if (! $this->ntfy->isChannelEnabled()) {
            return false;
        }

        return $notifiable === null || $this->isEnabledForNotifiable($notifiable);
    }

    /**
     * Check if ntfy delivery is enabled for the given notifiable.
     */
    protected function isEnabledForNotifiable(object $notifiable): bool
    {
        if (! $notifiable instanceof Model) {
            // Notifiables without a stored configuration (e.g. AnonymousNotifiable) are routed through
            // routeNotificationFor, so the channel-level setting is the only gate for them.
            return true;
        }

        $ntfyConfiguration = app(ResolvesNtfyConfiguration::class)->resolveForNotifiable($notifiable);

        if ($ntfyConfiguration === null) {
            // No stored configuration: delivery is still possible through routeNotificationFor.
            return true;
        }

        $enabled = $ntfyConfiguration->getAttribute('enabled');

        // Configurations stored before the flag existed have no value, so treat them as enabled.
        return $enabled === null || filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get the ntfy message for the given notifiable.
     *
     * @return Message|MessageWithAttachment|null
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
