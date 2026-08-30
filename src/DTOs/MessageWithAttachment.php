<?php

namespace SameOldNick\Ntfy\DTOs;

use Ntfy\Message;

/**
 * Data Transfer Object for ntfy messages with attachments.
 */
final class MessageWithAttachment
{
    /**
     * Create a new message with an attachment.
     */
    public function __construct(
        public readonly Message $message,
        public readonly ?string $path,
        public readonly ?string $disk,
        public readonly ?string $content,
        public readonly ?string $filename,
    ) {
        //
    }
}
