<?php

namespace SameOldNick\Ntfy\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Ntfy\Auth\Token;
use Ntfy\Auth\User;
use Ntfy\Exception\EndpointException;
use Ntfy\Exception\NtfyException;
use Ntfy\Message;
use Ntfy\Server;
use SameOldNick\Ntfy\DTOs\MessageResponse;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Enums\AuthMethod;
use SameOldNick\Ntfy\Exceptions\InvalidServerUrlException;

class Ntfy
{
    /**
     * Create a new Ntfy service instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Send a message via ntfy.
     *
     * @throws NtfyException
     * @throws EndpointException
     * @throws InvalidServerUrlException Thrown if the server URL is not a valid HTTP(S) URL.
     */
    public function send(Message|MessageWithAttachment $message, ServerInfo $serverInfo): MessageResponse
    {
        try {
            $response = $this->sendRequest($message, $serverInfo);

            return $this->processResponse($response);
        } catch (ConnectionException $e) {
            // ConnectionException needs to be caught here because it's not thrown by the processResponse method, but by the sendRequest method.
            // We want to wrap it in a NtfyException for consistency.
            throw new NtfyException('Connection error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Send a message via ntfy and return the raw HTTP response.
     *
     * @throws ConnectionException Thrown if the request fails due to a connection error.
     * @throws InvalidServerUrlException Thrown if the server URL is not a valid HTTP(S) URL.
     */
    public function sendRequest(Message|MessageWithAttachment $message, ServerInfo $serverInfo): Response
    {
        // If message doesn't have a topic, set the default
        $this->assignTopic($message, $serverInfo);

        return $this->createClient($serverInfo)->send($message);
    }

    /**
     * Create the Ntfy Client instance.
     *
     * @throws InvalidServerUrlException Thrown if the server URL is not a valid HTTP(S) URL.
     */
    public function createClient(ServerInfo $serverInfo): Client
    {
        if (! $serverInfo->hasUrl()) {
            throw InvalidServerUrlException::forUrl($serverInfo->url);
        }

        $server = new Server($serverInfo->url);

        $auth = match ($serverInfo->getAuthMethod()) {
            AuthMethod::Login => new User(
                $serverInfo->authUsername,
                $serverInfo->authPassword,
            ),
            AuthMethod::Token => new Token(
                $serverInfo->authToken,
            ),
            default => null,
        };

        $options = $this->getHttpOptions($serverInfo);

        return new Client($server, $auth, $options);
    }

    /**
     * Check if ntfy notification channel is enabled in the configuration.
     */
    public function isChannelEnabled(): bool
    {
        return (bool) config('ntfy.enabled', false);
    }

    /**
     * Get the HTTP options for the Ntfy Client.
     */
    protected function getHttpOptions(ServerInfo $serverInfo): array
    {
        $serverOptions = $serverInfo->httpOptions ?? [];
        $globalOptions = config('ntfy.http', []);

        return array_merge($globalOptions, $serverOptions);
    }

    /**
     * Assign the topic to the ServerInfo topic if set
     */
    protected function assignTopic(Message|MessageWithAttachment $message, ServerInfo $serverInfo): void
    {
        if (! $defaultTopic = $serverInfo->topic) {
            return;
        }

        $target = $message instanceof MessageWithAttachment ? $message->message : $message;
        $target->topic($defaultTopic);
    }

    /**
     * Process the HTTP response and return a MessageResponse instance.
     *
     * @throws NtfyException
     * @throws EndpointException
     */
    public function processResponse(Response $response): MessageResponse
    {
        try {
            $response->throw();

            return new MessageResponse($response->json());
        } catch (RequestException $e) {
            if ($e->response->header('Content-Type') === 'application/json') {
                $json = $e->response->json();

                if (isset($json['error'], $json['code'])) {
                    $message = sprintf(
                        '%s (error code: %s, http status: %s)',
                        $json['error'],
                        $json['code'],
                        $json['http'] ?? $e->response->status(),
                    );

                    throw new EndpointException('Request error: '.$message, 0, $e);
                }
            }

            throw new EndpointException('Request error: '.$e->getMessage(), 0, $e);
        }
    }
}
