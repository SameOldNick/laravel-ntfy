<?php

namespace SameOldNick\Ntfy\Services;

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
    public function send(Message $message, ?ServerInfo $serverInfo = null): MessageResponse
    {
        $serverInfo = $serverInfo ?? ServerInfo::fromConfig();

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

        return new Client($server, $auth);
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
}
