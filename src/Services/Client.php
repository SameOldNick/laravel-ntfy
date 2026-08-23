<?php

namespace SameOldNick\Ntfy\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ntfy\Auth\Token;
use Ntfy\Auth\User;
use Ntfy\Exception\EndpointException;
use Ntfy\Exception\NtfyException;
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
    ) {
        //
    }

    /**
     * Send a message via ntfy.
     *
     * @throws NtfyException
     * @throws EndpointException
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
            ->maxRedirects(0)
            ->timeout(10);

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
