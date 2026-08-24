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
use SameOldNick\Ntfy\DTOs\ServerInfo;

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
     */
    public function send(Message $message, ServerInfo $serverInfo): MessageResponse
    {
        $response = $this->sendRequest($message, $serverInfo);

        return $this->processResponse($response);
    }

    /**
     * Send a message via ntfy and return the raw HTTP response.
     *
     * @throws NtfyException
     * @throws EndpointException
     */
    public function sendRequest(Message $message, ServerInfo $serverInfo): Response
    {
        // If message doesn't have a topic, set the default
        $this->assignTopic($message, $serverInfo);

        return $this->createClient($serverInfo)->send($message);
    }

    /**
     * Create the Ntfy Client instance.
     */
    public function createClient(ServerInfo $serverInfo): Client
    {
        $server = new Server($serverInfo->url);

        $auth = match (true) {
            ! empty($serverInfo->authUsername) || ! empty($serverInfo->authPassword) => new User(
                $serverInfo->authUsername,
                $serverInfo->authPassword,
            ),
            ! empty($serverInfo->authToken) => new Token(
                $serverInfo->authToken,
            ),
            default => null,
        };

        $options = $this->getHttpOptions($serverInfo);

        return new Client($server, $auth, $options);
    }

    /**
     * Get the HTTP options for the Ntfy Client.
     */
    protected function getHttpOptions(ServerInfo $serverInfo): array
    {
        $serverOptions = $serverInfo->options ?? [];
        $globalOptions = config('ntfy.http', []);

        return array_merge($globalOptions, $serverOptions);
    }

    /**
     * Assign the topic to the ServerInfo topic if set
     */
    protected function assignTopic(Message $message, ServerInfo $serverInfo): void
    {
        if ($defaultTopic = $serverInfo->topic) {
            // ServerInfo topic takes precedence over message topic, as the message may not have one set and the ServerInfo topic is required for sending
            $message->topic($defaultTopic);
        }
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
        } catch (ConnectionException $e) {
            throw new NtfyException('Connection error: '.$e->getMessage(), 0, $e);
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
