<?php

namespace SameOldNick\Ntfy\Tests\Fixtures;

use Closure;
use Illuminate\Notifications\Notification;
use Ntfy\Message as NtfyMessage;
use SameOldNick\Ntfy\Contracts\NtfyNotification;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;
use SameOldNick\Ntfy\Services\MessageBuilder;

class TestNotification extends Notification implements NtfyNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $topic = null,
        public readonly ?int $priority = null,
        public readonly ?array $tags = null,
        public readonly ?Closure $withMessageBuilder = null,
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['ntfy'];
    }

    /**
     * Convert the notification to an ntfy Message.
     */
    public function toNtfy(object $notifiable): NtfyMessage|MessageWithAttachment
    {
        $builder = MessageBuilder::make()
            ->title($this->title)
            ->body($this->message);

        if ($this->topic) {
            $builder->topic($this->topic);
        }

        if ($this->priority) {
            $builder->priority($this->priority);
        }

        if ($this->tags) {
            $builder->tags($this->tags);
        }

        if ($this->withMessageBuilder) {
            $builder = ($this->withMessageBuilder)($builder);
        }

        return $builder->build();
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
