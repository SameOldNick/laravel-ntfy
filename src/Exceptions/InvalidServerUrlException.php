<?php

namespace SameOldNick\Ntfy\Exceptions;

use Ntfy\Exception\NtfyException;
use Throwable;

/**
 * Exception thrown when a server URL is not a valid HTTP(S) URL.
 *
 * Extends the ntfy library exception so existing catch blocks keep working.
 */
class InvalidServerUrlException extends NtfyException
{
    /**
     * Create an exception for the given server URL.
     */
    public static function forUrl(string $url, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Invalid ntfy server URL "%s": expected a valid http:// or https:// URL.', $url),
            0,
            $previous,
        );
    }
}
