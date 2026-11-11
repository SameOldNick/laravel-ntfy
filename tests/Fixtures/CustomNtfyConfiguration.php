<?php

namespace SameOldNick\Ntfy\Tests\Fixtures;

use SameOldNick\Ntfy\Models\NtfyConfiguration;

class CustomNtfyConfiguration extends NtfyConfiguration
{
    protected $table = 'ntfy_configurations';
}
