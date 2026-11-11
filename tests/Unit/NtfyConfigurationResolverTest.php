<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use InvalidArgumentException;
use SameOldNick\Ntfy\Contracts\ResolvesNtfyConfiguration;
use SameOldNick\Ntfy\Models\NtfyConfiguration;
use SameOldNick\Ntfy\Services\NtfyConfigurationResolver;
use SameOldNick\Ntfy\Tests\Fixtures\CustomNtfyConfiguration;
use SameOldNick\Ntfy\Tests\Fixtures\DefaultNtfyConfigurationFactory;
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
        $this->assertFalse($resolver->default);
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
        $this->assertNull($user->ntfyConfiguration);
    }

    /**
     * Test a default of true returns an empty model instance.
     */
    public function test_uses_empty_model_as_default_when_enabled(): void
    {
        config(['ntfy.model.default' => true]);

        $user = User::factory()->create();

        $configuration = $user->ntfyConfiguration;

        $this->assertInstanceOf(NtfyConfiguration::class, $configuration);
        $this->assertFalse($configuration->exists);
        $this->assertNull($configuration->getKey());
    }

    /**
     * Test an array default fills the related model.
     */
    public function test_uses_default_attributes(): void
    {
        config(['ntfy.model.default' => [
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'default-topic',
        ]]);

        $user = User::factory()->create();

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertInstanceOf(NtfyConfiguration::class, $configuration);
        $this->assertSame('https://ntfy.example.com/', $configuration->server_url);
        $this->assertSame('default-topic', $configuration->topic);
    }

    /**
     * Test a Closure default receives the related and parent models.
     */
    public function test_uses_default_closure(): void
    {
        $receivedRelated = null;
        $receivedParent = null;

        config(['ntfy.model.default' => function ($related, $parent) use (&$receivedRelated, &$receivedParent) {
            $receivedRelated = $related;
            $receivedParent = $parent;

            $related->forceFill(['topic' => 'closure-topic']);

            return $related;
        }]);

        $user = User::factory()->create();

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertSame('closure-topic', $configuration->topic);
        $this->assertSame($configuration, $receivedRelated);
        $this->assertTrue($receivedParent->is($user));
    }

    /**
     * Test a Closure default receives the models positionally, regardless of parameter names.
     */
    public function test_uses_default_closure_with_arbitrary_parameter_names(): void
    {
        config(['ntfy.model.default' => fn ($configuration, $notifiable) => $configuration->forceFill([
            'topic' => 'positional-'.$notifiable->getKey(),
        ])]);

        $user = User::factory()->create();

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertSame('positional-'.$user->getKey(), $configuration->topic);
    }

    /**
     * Test a default callback returning a non-model is rejected.
     */
    public function test_rejects_default_callback_returning_non_model(): void
    {
        config(['ntfy.model.default' => fn ($related, $parent) => ['topic' => 'invalid']]);

        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return an instance of');

        $user->ntfyConfiguration;
    }

    /**
     * Test a string default is resolved through the container.
     */
    public function test_uses_default_class_method_string(): void
    {
        config(['ntfy.model.default' => DefaultNtfyConfigurationFactory::class.'@make']);

        $user = User::factory()->create();

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertInstanceOf(NtfyConfiguration::class, $configuration);
        $this->assertSame('string-'.$user->getKey(), $configuration->topic);
    }

    /**
     * Test an existing configuration takes precedence over the default.
     */
    public function test_existing_configuration_takes_precedence_over_default(): void
    {
        config(['ntfy.model.default' => [
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'default-topic',
        ]]);

        $user = User::factory()->create();

        $user->ntfyConfiguration()->create([
            'server_url' => 'https://existing.example.com/',
            'topic' => 'existing-topic',
        ]);

        $configuration = $this->app->make(ResolvesNtfyConfiguration::class)->resolveForNotifiable($user);

        $this->assertSame('existing-topic', $configuration->topic);
    }

    /**
     * Test a null default returns null when no configuration exists.
     */
    public function test_null_default_returns_null(): void
    {
        config(['ntfy.model.default' => null]);

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
