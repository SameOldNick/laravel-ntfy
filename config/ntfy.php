<?php

use SameOldNick\Ntfy\Models\NtfyConfiguration;

return [
    /**
     * Whether ntfy notifications are enabled.
     * This disables/enables the NtfyChannel. The Ntfy service can still be used directly.
     */
    'enabled' => env('NTFY_ENABLED', false),

    'model' => [
        /**
         * The model to use for storing ntfy configurations.
         * It must extend \Illuminate\Database\Eloquent\Model and provide a toServerInfo() method
         * plus an "enabled" flag; the default model provides both.
         */
        'class' => NtfyConfiguration::class,

        /**
         * The morph name to use for the notifiable relationship.
         * It determines the foreign key ("{name}_id") and morph type ("{name}_type") columns.
         */
        'name' => 'notifiable',

        /**
         * The morph type to use for the notifiable relationship.
         * If null, it will use the default morph type.
         */
        'type' => null,

        /**
         * The morph id to use for the notifiable relationship.
         * If null, it will use the default morph id.
         */
        'id' => null,

        /**
         * The morph local key to use for the notifiable relationship.
         * If null, it will use the default local key.
         */
        'local_key' => null,
    ],

    /**
     * Global configuration for the Ntfy service.
     * These settings can be pulled using \SameOldNick\Ntfy\DTOs\ServerInfo::fromConfig().
     */
    'global' => [
        'server_url' => env('NTFY_SERVER_URL', 'https://ntfy.sh/'),
        /**
         * Auth method
         * Possible values: user, token, or null
         */
        'auth_username' => env('NTFY_AUTH_USERNAME', ''),
        'auth_password' => env('NTFY_AUTH_PASSWORD', ''),

        'auth_token' => env('NTFY_AUTH_TOKEN', ''),

        /**
         * The topic to use.
         */
        'topic' => env('NTFY_DEFAULT_TOPIC', null),

        /**
         * HTTP options for the Ntfy Client.
         * These options will be merged with any options specified in the ServerInfo object.
         */
        'http' => [
            /**
             * The timeout for HTTP requests in seconds.
             * Set to 0 for no timeout.
             */
            'timeout' => env('NTFY_HTTP_TIMEOUT', 10),

            /**
             * The connection timeout for HTTP requests in seconds.
             * Set to 0 for no timeout.
             */
            'connect_timeout' => env('NTFY_HTTP_CONNECT_TIMEOUT', 10),

            /**
             * Whether to verify SSL certificates.
             * Set to false to disable SSL verification (not recommended).
             */
            'verify_ssl' => env('NTFY_HTTP_VERIFY_SSL', true),

            /**
             * Retry settings for HTTP requests.
             * 'enabled' => Whether to enable retries.
             * 'max_attempts' => Maximum number of retry attempts.
             * 'delay' => Delay between retries in milliseconds.
             */
            'retry' => [
                'enabled' => env('NTFY_HTTP_RETRY_ENABLED', true),
                'max_attempts' => env('NTFY_HTTP_RETRY_MAX_ATTEMPTS', 3),
                'delay' => env('NTFY_HTTP_RETRY_DELAY', 100), // in milliseconds
            ],

            'options' => [
                // Additional Guzzle options can be added here.
            ],
        ],
    ],

    /**
     * HTTP options for the Ntfy Client.
     * These options will be merged with any options specified in the ServerInfo object.
     */
    'http' => [
        /**
         * The timeout for HTTP requests in seconds.
         * Set to 0 for no timeout.
         */
        'timeout' => env('NTFY_HTTP_TIMEOUT', 10),

        /**
         * The connection timeout for HTTP requests in seconds.
         * Set to 0 for no timeout.
         */
        'connect_timeout' => env('NTFY_HTTP_CONNECT_TIMEOUT', 10),

        /**
         * Whether to verify SSL certificates.
         * Set to false to disable SSL verification (not recommended).
         */
        'verify_ssl' => env('NTFY_HTTP_VERIFY_SSL', true),

        /**
         * Retry settings for HTTP requests.
         * 'enabled' => Whether to enable retries.
         * 'max_attempts' => Maximum number of retry attempts.
         * 'delay' => Delay between retries in milliseconds.
         */
        'retry' => [
            'enabled' => env('NTFY_HTTP_RETRY_ENABLED', true),
            'max_attempts' => env('NTFY_HTTP_RETRY_MAX_ATTEMPTS', 3),
            'delay' => env('NTFY_HTTP_RETRY_DELAY', 100), // in milliseconds
        ],

        'options' => [
            // Additional Guzzle options can be added here.
        ],
    ],
];
