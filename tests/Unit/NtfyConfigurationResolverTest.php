<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use InvalidArgumentException;
use SameOldNick\Ntfy\Contracts\ResolvesNtfyConfiguration;
use SameOldNick\Ntfy\Models\NtfyConfiguration;
use SameOldNick\Ntfy\Services\NtfyConfigurationResolver;
use SameOldNick\Ntfy\Tests\TestCase;
use stdClass;
use Workbench\App\Models\User;

class NtfyConfigurationResolverTest extends TestCase
{
    /**
     * Test the resolver uses the default model and morph options.
     */
    public function test_resolves_default_options(): void
    {
        $resolver = $this->app->make(ResolvesNtfyConfiguration::class);

        $this->assertInstanceOf(NtfyConfigurationResolver::class, $resolver);
        $this->assertSame(NtfyConfiguration::class, $resolver->getModelClass());
        $this->assertSame('notifiable', $resolver->getMorphName());
        $this->assertNull($resolver->getMorphType());
        $this->assertNull($resolver->getMorphId());
        $this->assertNull($resolver->getMorphLocalKey());
    }

    /**
     * Test the model class can be set in the config.
     */
    public function test_model_class_is_configurable(): void
    {
        config(['ntfy.model.class' => CustomNtfyConfiguration::class]);

        $resolver = $this->app->make(ResolvesNtfyConfiguration::class);

        $this->assertSame(CustomNtfyConfiguration::class, $resolver->getModelClass());
    }

    /**
     * Test the morph options are read from the config.
     */
    public function test_morph_options_are_configurable(): void
    {
        config([
            'ntfy.model.name' => 'ntfy',
            'ntfy.model.type' => 'user_type',
            'ntfy.model.id' => 'user_id',
            'ntfy.model.local_key' => 'uuid',
        ]);

        $resolver = $this->app->make(ResolvesNtfyConfiguration::class);

        $this->assertSame('ntfy', $resolver->getMorphName());
        $this->assertSame('user_type', $resolver->getMorphType());
        $this->assertSame('user_id', $resolver->getMorphId());
        $this->assertSame('uuid', $resolver->getMorphLocalKey());
    }

    /**
     * Test a model class that is not an Eloquent model is rejected.
     */
    public function test_rejects_non_eloquent_model_class(): void
    {
        config(['ntfy.model.class' => self::class]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a subclass of');

        $this->app->make(ResolvesNtfyConfiguration::class);
    }

    /**
     * Test the configuration is resolved for a notifiable.
     */
    public function test_resolves_configuration_for_notifiable(): void
    {
        $user = User::factory()->create();

        $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
        ]);

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertInstanceOf(NtfyConfiguration::class, $configuration);
        $this->assertSame('alerts', $configuration->topic);
    }

    /**
     * Test the configured model class is used to resolve the configuration.
     */
    public function test_resolves_configuration_using_configured_model(): void
    {
        config(['ntfy.model.class' => CustomNtfyConfiguration::class]);

        $user = User::factory()->create();

        $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'custom',
        ]);

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertInstanceOf(CustomNtfyConfiguration::class, $configuration);
        $this->assertSame('custom', $configuration->topic);
    }

    /**
     * Test resolving returns null when no configuration exists.
     */
    public function test_returns_null_when_notifiable_has_no_configuration(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user));
    }

    /**
     * Test resolving requires an Eloquent model.
     */
    public function test_requires_an_eloquent_model(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an instance of');

        $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable(new stdClass);
    }
}

class CustomNtfyConfiguration extends NtfyConfiguration
{
    protected $table = 'ntfy_configurations';
}
