<?php

namespace SameOldNick\Ntfy\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Ntfy\Auth\Token;
use Ntfy\Auth\User;
use Ntfy\Message;
use Ntfy\Server;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;

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
    public function send(Message|MessageWithAttachment $message): Response
    {
        return $message instanceof MessageWithAttachment
            ? $this->sendMessageWithAttachment($message)
            : $this->sendMessage($message);
    }

    /**
     * Send a message via ntfy and return the raw HTTP response.
     *
     * @throws ConnectionException Thrown if the request fails due to a connection error.
     */
    public function sendMessage(Message $message): Response
    {
        $client = $this->createHttpClient()->asJson();

        return $client->post($this->server->get(), $message->getData());
    }

    /**
     * Send a message with an attachment via ntfy and return the raw HTTP response.
     *
     * @throws ConnectionException Thrown if the request fails due to a connection error.
     */
    public function sendMessageWithAttachment(MessageWithAttachment $message): Response
    {
        $data = $message->message->getData();

        $topic = (string) ($data['topic'] ?? '');
        unset($data['topic']);

        $headers = $this->messageDataToHeaders($data);
        $headers['X-Filename'] = $this->getAttachmentFilename($message);

        $client = $this->createHttpClient()
            ->withHeaders($headers)
            ->withBody($this->getAttachmentContent($message), 'application/octet-stream');

        return $client->put(rtrim($this->server->get(), '/').'/'.$topic);
    }

    /**
     * Get the filename to send in the Filename header for an attachment.
     */
    protected function getAttachmentFilename(MessageWithAttachment $message): string
    {
        if ($message->filename !== null) {
            return $message->filename;
        }

        if ($message->path !== null) {
            return basename($message->path);
        }

        return 'message.txt';
    }

    /**
     * Convert ntfy message data into publish headers.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function messageDataToHeaders(array $data): array
    {
        $headers = [];

        $mappings = [
            'message' => 'X-Message',
            'title' => 'X-Title',
            'priority' => 'X-Priority',
            'click' => 'X-Click',
            'icon' => 'X-Icon',
            'delay' => 'X-Delay',
            'email' => 'X-Email',
            'cache' => 'X-Cache',
            'firebase' => 'X-Firebase',
        ];

        foreach ($mappings as $key => $header) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $headers[$header] = (string) $data[$key];
            }
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $headers['X-Tags'] = implode(',', $data['tags']);
        }

        if (isset($data['markdown']) && $data['markdown'] === true) {
            $headers['X-Markdown'] = 'yes';
        }

        if (isset($data['actions']) && is_array($data['actions'])) {
            $headers['X-Actions'] = json_encode($data['actions']);
        }

        return $headers;
    }

    /**
     * Get the content of the attachment for a MessageWithAttachment.
     *
     * @throws InvalidArgumentException if neither content nor path is set.
     */
    protected function getAttachmentContent(MessageWithAttachment $message): string
    {
        if ($message->content !== null) {
            return $message->content;
        }

        if ($message->path !== null) {
            return Storage::disk($message->disk)->get($message->path);
        }

        throw new InvalidArgumentException('MessageWithAttachment must have either content or path set.');
    }

    /**
     * Create the HTTP client with appropriate authentication if configured.
     */
    protected function createHttpClient(): PendingRequest
    {
        $httpClient = Http::createPendingRequest()
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

        if ($this->auth) {
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
        }

        return $httpClient;
    }
}
