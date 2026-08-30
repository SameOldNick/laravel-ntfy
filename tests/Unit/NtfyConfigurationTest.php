<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Illuminate\Support\Facades\DB;
use SameOldNick\Ntfy\Models\NtfyConfiguration;
use SameOldNick\Ntfy\Tests\TestCase;
use Workbench\App\Models\User;

class NtfyConfigurationTest extends TestCase
{
    /**
     * Test auth method is none without credentials.
     */
    public function test_auth_method_is_none_without_credentials(): void
    {
        $config = new NtfyConfiguration([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
        ]);

        $this->assertSame('none', $config->auth_method);
    }

    /**
     * Test auth method is token with a token.
     */
    public function test_auth_method_is_token_with_token(): void
    {
        $config = new NtfyConfiguration([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
            'auth_token' => 'tk_test',
        ]);

        $this->assertSame('token', $config->auth_method);
    }

    /**
     * Test auth method is login with username/password and takes precedence over token.
     */
    public function test_auth_method_is_login_and_precedes_token(): void
    {
        $config = new NtfyConfiguration([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
            'username' => 'user',
            'password' => 'pass',
            'auth_token' => 'tk_test',
        ]);

        $this->assertSame('login', $config->auth_method);
    }

    /**
     * Test credentials are encrypted at rest and decrypted on access.
     */
    public function test_credentials_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $config = $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
            'auth_token' => 'tk_super_secret',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $raw = DB::table('ntfy_configurations')->where('id', $config->id)->first();

        $this->assertStringNotContainsString('tk_super_secret', $raw->auth_token);
        $this->assertStringNotContainsString('user', $raw->username);
        $this->assertStringNotContainsString('pass', $raw->password);

        $fresh = $config->fresh();

        $this->assertSame('tk_super_secret', $fresh->auth_token);
        $this->assertSame('user', $fresh->username);
        $this->assertSame('pass', $fresh->password);
    }

    /**
     * Test credentials are hidden from serialization.
     */
    public function test_credentials_are_hidden_from_serialization(): void
    {
        $user = User::factory()->create();

        $config = $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
            'auth_token' => 'tk_super_secret',
            'username' => 'user',
            'password' => 'pass',
        ]);

        $array = $config->fresh()->toArray();

        $this->assertArrayNotHasKey('auth_token', $array);
        $this->assertArrayNotHasKey('username', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    /**
     * Test auth method is appended to serialization.
     */
    public function test_auth_method_is_appended_to_serialization(): void
    {
        $user = User::factory()->create();

        $config = $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
            'auth_token' => 'tk_super_secret',
        ]);

        $array = $config->fresh()->toArray();

        $this->assertArrayHasKey('auth_method', $array);
        $this->assertSame('token', $array['auth_method']);
    }

    /**
     * Test notifiable relationship returns the owning model.
     */
    public function test_notifiable_relationship_returns_owner(): void
    {
        $user = User::factory()->create();

        $config = $user->ntfyConfiguration()->create([
            'server_url' => 'https://ntfy.example.com/',
            'topic' => 'alerts',
        ]);

        $this->assertTrue($config->notifiable->is($user));
    }
}
