<?php

namespace SameOldNick\Ntfy\DTOs;

use Illuminate\Support\Facades\Storage;

class FakeMessageResponse extends MessageResponse
{
    public function attachment(): ?array
    {
        return $this->responseData['attachment'] ?? null;
    }

    /**
     * Get the attachment content, either from the provided content or from storage.
     */
    public function getAttachmentContent(): ?string
    {
        $attachment = $this->attachment();

        if (! $attachment) {
            return null;
        }

        return $attachment['content'] ?? Storage::disk($attachment['disk'] ?? null)->get($attachment['path'] ?? null);
    }
}
