<?php

namespace SameOldNick\Ntfy\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
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
     * Resolve the ntfy destination for this notifiable.
     */
    protected function resolveNtfyRoute($default = null)
    {
        if ($configuration = $this->ntfyConfiguration) {
            return match (true) {
                ! empty($configuration->username) || ! empty($configuration->password) => ServerInfo::createWithAuth(
                    url: $configuration->server_url,
                    topic: $configuration->topic,
                    username: $configuration->username,
                    password: $configuration->password,
                ),
                ! empty($configuration->auth_token) => ServerInfo::createWithToken(
                    url: $configuration->server_url,
                    topic: $configuration->topic,
                    token: $configuration->auth_token,
                ),
                default => ServerInfo::createWithoutAuth(
                    url: $configuration->server_url,
                    topic: $configuration->topic,
                ),
            };
        }

        return value($default);
    }
}
