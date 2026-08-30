<?php

namespace SameOldNick\Ntfy\Services;

use Illuminate\Support\Collection;
use Ntfy\Message;
use PHPUnit\Framework\Assert as PHPUnit;
use SameOldNick\Ntfy\DTOs\FakeMessageResponse;
use SameOldNick\Ntfy\DTOs\MessageResponse;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;
use SameOldNick\Ntfy\DTOs\ServerInfo;

/**
 * Fake implementation of Ntfy for testing.
 */
class NtfyFake extends Ntfy
{
    /** @var Collection<int, Message|MessageWithAttachment> */
    protected Collection $messages;

    /**
     * Create a new fake instance.
     */
    public function __construct()
    {
        $this->messages = collect();
    }

    /**
     * {@inheritDoc}
     */
    public function send(Message|MessageWithAttachment $message, ServerInfo $serverInfo): MessageResponse
    {
        $this->assignTopic($message, $serverInfo);

        $this->messages->push($message);

        $data = $message instanceof MessageWithAttachment
            ? $message->message->getData()
            : $message->getData();

        return new FakeMessageResponse([
            'topic' => $data['topic'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'priority' => $data['priority'] ?? null,
            'time' => (string) time(),
            'id' => uniqid('fake-message-', true),
            'attachment' => $message instanceof MessageWithAttachment ? [
                'path' => $message->path,
                'disk' => $message->disk,
                'content' => $message->content,
                'filename' => $message->filename,
            ] : null,
        ]);
    }

    /**
     * Assert that a message was sent.
     */
    public function assertSent(?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->sent($callback)->count() > 0,
            'The expected message was not sent.',
        );
    }

    /**
     * Assert that a message was not sent.
     */
    public function assertNotSent(?callable $callback = null): void
    {
        PHPUnit::assertTrue(
            $this->sent($callback)->count() === 0,
            'The unexpected message was sent.',
        );
    }

    /**
     * Assert the number of messages sent.
     */
    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->messages);
    }

    /**
     * Assert no messages were sent.
     */
    public function assertNothingSent(): void
    {
        $this->assertSentCount(0);
    }

    /**
     * Get all sent messages matching a callback.
     *
     * @return Collection<int, Message|MessageWithAttachment>
     */
    public function sent(?callable $callback = null): Collection
    {
        if (! $callback) {
            return $this->messages;
        }

        return $this->messages->filter(fn ($message) => $callback($message));
    }
}
