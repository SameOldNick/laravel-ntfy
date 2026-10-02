<?php

namespace SameOldNick\Ntfy\DTOs;

use Illuminate\Support\Str;
use SameOldNick\Ntfy\Enums\AuthMethod;

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
        public readonly ?array $httpOptions = null,
    ) {
        //
    }

    /**
     * Check if the server URL is valid.
     */
    public function hasUrl(): bool
    {
        return ! empty($this->url) && Str::isUrl($this->url, ['https', 'http']);
    }

    /**
     * Check if authentication information is provided.
     */
    public function hasAuth(): bool
    {
        return ! empty($this->authUsername) || ! empty($this->authPassword) || ! empty($this->authToken);
    }

    /**
     * Get the authentication method based on provided credentials.
     */
    public function getAuthMethod(): AuthMethod
    {
        return match (true) {
            ! empty($this->authUsername) || ! empty($this->authPassword) => AuthMethod::Login,
            ! empty($this->authToken) => AuthMethod::Token,
            default => AuthMethod::None,
        };
    }

    /**
     * Create a ServerInfo instance from configuration.
     */
    public static function fromConfig(): self
    {
        $config = config('ntfy.global', []);

        return self::fromArray(array_merge($config));
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
            httpOptions: $array['http'] ?? null,
        );
    }

    /**
     * Create ServerInfo with username and password authentication.
     */
    public static function createWithAuth(string $url, string $username, string $password, ?string $topic = null, ?array $http = null): self
    {
        return new self(
            url: $url,
            authUsername: $username,
            authPassword: $password,
            topic: $topic,
            httpOptions: $http,
        );
    }

    /**
     * Create ServerInfo with token authentication.
     */
    public static function createWithToken(string $url, string $token, ?string $topic = null, ?array $http = null): self
    {
        return new self(
            url: $url,
            authToken: $token,
            topic: $topic,
            httpOptions: $http,
        );
    }

    /**
     * Create ServerInfo without authentication.
     */
    public static function createWithoutAuth(string $url, ?string $topic = null, ?array $http = null): self
    {
        return new self(
            url: $url,
            topic: $topic,
            httpOptions: $http,
        );
    }
}
