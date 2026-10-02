<?php

namespace SameOldNick\Ntfy\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notification;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Models\NtfyConfiguration;

/**
 * Trait NtfyNotifiable
 *
 * Provides ntfy-specific notification routing and configuration for notifiable models.
 *
 * @property-read NtfyConfiguration|null $ntfyConfiguration
 */
trait NtfyNotifiable
{
    /**
     * Get the ntfy configuration for the model.
     *
     * @return MorphOne<NtfyConfiguration>
     */
    public function ntfyConfiguration()
    {
        return $this->morphOne(NtfyConfiguration::class, 'notifiable');
    }

    /**
     * Route notifications for the ntfy channel.
     *
     * @param  Notification|null  $notification
     * @return ServerInfo|null
     */
    public function routeNotificationForNtfy($notification = null)
    {
        return $this->resolveNtfyRoute();
    }

    /**
     * Resolve the ntfy destination for this notifiable.
     *
     * @param  mixed  $default  The default value to return if no configuration is found. Can be a callback or an array.
     * @return ServerInfo|null
     */
    protected function resolveNtfyRoute($default = null)
    {
        if ($configuration = $this->ntfyConfiguration) {
            return $configuration->toServerInfo();
        }

        return value($default, $this);
    }
}
