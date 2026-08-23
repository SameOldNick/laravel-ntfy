<?php

namespace SameOldNick\Ntfy\Facades;

use Illuminate\Support\Facades\Facade;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Services\Ntfy as NtfyService;
use SameOldNick\Ntfy\Services\NtfyFake;

class Ntfy extends Facade
{
    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(?ServerInfo $serverInfo = null): NtfyFake
    {
        static::swap($fake = new NtfyFake(
            $serverInfo ?? ServerInfo::createWithoutAuth(
                config('ntfy.server_url'),
                config('ntfy.default_topic'),
            ),
        ));

        return $fake;
    }

    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return NtfyService::class;
    }
}
