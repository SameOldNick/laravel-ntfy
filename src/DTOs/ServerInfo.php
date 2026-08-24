<?php

namespace SameOldNick\Ntfy\DTOs;

/**
 * Data Transfer Object for ntfy server information.
 */
class ServerInfo
{
    /**
     * Create a new ServerInfo instance.
     *
     * It's recommended to use the static factory methods.
     */
    public function __construct(
        public readonly string $url,
        public readonly ?string $authUsername = null,
        public readonly ?string $authPassword = null,
        public readonly ?string $authToken = null,
        public readonly ?string $topic = null,
        public readonly ?array $options = null,
    ) {
        //
    }

    /**
     * Create a ServerInfo instance from configuration.
     */
    public static function fromConfig(?array $options = null): self
    {
        return new self(
            url: config('ntfy.server_url', 'https://ntfy.sh/'),
            authUsername: config('ntfy.auth_credentials.username'),
            authPassword: config('ntfy.auth_credentials.password'),
            authToken: config('ntfy.auth_token'),
            topic: config('ntfy.default_topic'),
            options: $options,
        );
    }

    /**
     * Create a ServerInfo instance from an array.
     */
    public static function fromArray(array $array): self
    {
        return new self(
            url: $array['server_url'],
            authUsername: $array['auth_username'] ?? null,
            authPassword: $array['auth_password'] ?? null,
            authToken: $array['auth_token'] ?? null,
            topic: $array['topic'] ?? null,
            options: $array['options'] ?? null,
        );
    }

    /**
     * Create ServerInfo with username and password authentication.
     */
    public static function createWithAuth(string $url, string $username, string $password, ?string $topic = null, ?array $options = null): self
    {
        return new self(
            url: $url,
            authUsername: $username,
            authPassword: $password,
            topic: $topic,
            options: $options,
        );
    }

    /**
     * Create ServerInfo with token authentication.
     */
    public static function createWithToken(string $url, string $token, ?string $topic = null, ?array $options = null): self
    {
        return new self(
            url: $url,
            authToken: $token,
            topic: $topic,
            options: $options,
        );
    }

    /**
     * Create ServerInfo without authentication.
     */
    public static function createWithoutAuth(string $url, ?string $topic = null, ?array $options = null): self
    {
        return new self(
            url: $url,
            topic: $topic,
            options: $options,
        );
    }
}
