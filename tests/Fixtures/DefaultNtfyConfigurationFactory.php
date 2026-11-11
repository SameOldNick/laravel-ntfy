<?php

namespace SameOldNick\Ntfy\Tests\Fixtures;

use SameOldNick\Ntfy\Models\NtfyConfiguration;
use Workbench\App\Models\User;

class DefaultNtfyConfigurationFactory
{
    public function make(NtfyConfiguration $related, User $parent): NtfyConfiguration
    {
        $related->forceFill(['topic' => 'string-'.$parent->getKey()]);

        return $related;
    }
}
