<?php

namespace SameOldNick\Ntfy\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ntfy\Auth\Token;
use Ntfy\Auth\User;
use Ntfy\Message;
use Ntfy\Server;

class Client
{
    /**
     * @param  Server  $server  Server URI
     * @param  User|Token|null  $auth  Authentication class instance
     */
    public function __construct(
        public readonly Server $server,
        public readonly User|Token|null $auth = null,
        public readonly ?array $options = null,
    ) {
        //
    }

    /**
     * Send a message via ntfy.
     *
     * @throws ConnectionException Thrown if the request fails due to a connection error.
     */
    public function send(Message $message): Response
    {
        $client = $this->createHttpClient();

        return $client->post($this->server->get(), $message->getData());
    }

    /**
     * Create the HTTP client with appropriate authentication if configured.
     */
    protected function createHttpClient(): PendingRequest
    {
        $httpClient = Http::createPendingRequest()
            ->asJson()
            ->acceptJson()
            ->maxRedirects(0);

        $options = $this->options ?? [];

        if ($options['timeout'] ?? 10 > 0) {
            $httpClient = $httpClient->timeout($options['timeout'] ?? 10);
        }

        if ($options['connect_timeout'] ?? 5 > 0) {
            $httpClient = $httpClient->connectTimeout($options['connect_timeout'] ?? 5);
        }

        if ($options['retry']['enabled'] ?? false) {
            $httpClient = $httpClient->retry($options['retry']['max_attempts'] ?? 3, $options['retry']['delay'] ?? 0, throw: false);
        }

        if (isset($options['verify_ssl']) && $options['verify_ssl'] === false) {
            $httpClient = $httpClient->withoutVerifying();
        }

        if ($options['options'] ?? false) {
            $httpClient = $httpClient->withOptions($options['options'] ?? []);
        }

        if ($this->auth instanceof User) {
            $httpClient = $httpClient->withBasicAuth(
                $this->auth->getUsername(),
                $this->auth->getPassword(),
            );
        } elseif ($this->auth instanceof Token) {
            $httpClient = $httpClient->withToken(
                $this->auth->getToken(),
            );
        }

        return $httpClient;
    }
}
