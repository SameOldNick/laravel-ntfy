<?php

return [
    /**
     * Whether ntfy notifications are enabled.
     * This disables/enables the NtfyChannel. The Ntfy service can still be used directly.
     */
    'enabled' => env('NTFY_ENABLED', false),
    'server_url' => env('NTFY_SERVER_URL', 'https://ntfy.sh/'),
    /**
     * Auth method
     * Possible values: user, token, or null
     */
    'auth_method' => env('NTFY_AUTH_METHOD', null),
    'auth_credentials' => [
        'username' => env('NTFY_AUTH_USERNAME', ''),
        'password' => env('NTFY_AUTH_PASSWORD', ''),
    ],
    'auth_token' => env('NTFY_AUTH_TOKEN', ''),
    /**
     * The default topic to use if none is specified.
     */
    'default_topic' => env('NTFY_DEFAULT_TOPIC', null),
];
