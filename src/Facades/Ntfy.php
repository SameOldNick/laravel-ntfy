<?php

namespace SameOldNick\Ntfy\Facades;

use Illuminate\Support\Facades\Facade;
use SameOldNick\Ntfy\Services\Ntfy as NtfyService;
use SameOldNick\Ntfy\Services\NtfyFake;

class Ntfy extends Facade
{
    /**
     * Replace the bound instance with a fake.
     */
    public static function fake(): NtfyFake
    {
        static::swap($fake = new NtfyFake);

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
