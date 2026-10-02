<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Enums\AuthMethod;
use SameOldNick\Ntfy\ServiceProvider;
use SameOldNick\Ntfy\Tests\TestCase;

class ServerInfoTest extends TestCase
{
    /**
     * Test from config maps all global settings.
     */
    public function test_from_config_maps_all_global_settings(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
            'auth_username' => 'testuser',
            'auth_password' => 'testpass',
            'auth_token' => 'tk_test',
            'topic' => 'alerts',
            'http' => ['timeout' => 5],
        ]);

        $info = ServerInfo::fromConfig();

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertSame('testuser', $info->authUsername);
        $this->assertSame('testpass', $info->authPassword);
        $this->assertSame('tk_test', $info->authToken);
        $this->assertSame('alerts', $info->topic);
        $this->assertSame(['timeout' => 5], $info->httpOptions);
    }

    /**
     * Test from config defaults optional settings to null.
     */
    public function test_from_config_defaults_optional_settings_to_null(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
        ]);

        $info = ServerInfo::fromConfig();

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertNull($info->authUsername);
        $this->assertNull($info->authPassword);
        $this->assertNull($info->authToken);
        $this->assertNull($info->topic);
        $this->assertNull($info->httpOptions);
    }

    /**
     * Test from config returns a ServerInfo instance.
     */
    public function test_from_config_returns_server_info_instance(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
        ]);

        $this->assertInstanceOf(ServerInfo::class, ServerInfo::fromConfig());
    }

    /**
     * Test from array maps all keys.
     */
    public function test_from_array_maps_all_keys(): void
    {
        $info = ServerInfo::fromArray([
            'server_url' => 'https://ntfy.example.com/',
            'auth_username' => 'testuser',
            'auth_password' => 'testpass',
            'auth_token' => 'tk_test',
            'topic' => 'alerts',
            'http' => ['timeout' => 5],
        ]);

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertSame('testuser', $info->authUsername);
        $this->assertSame('testpass', $info->authPassword);
        $this->assertSame('tk_test', $info->authToken);
        $this->assertSame('alerts', $info->topic);
        $this->assertSame(['timeout' => 5], $info->httpOptions);
    }

    /**
     * Test from array defaults optional keys to null.
     */
    public function test_from_array_defaults_optional_keys_to_null(): void
    {
        $info = ServerInfo::fromArray([
            'server_url' => 'https://ntfy.example.com/',
        ]);

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertNull($info->authUsername);
        $this->assertNull($info->authPassword);
        $this->assertNull($info->authToken);
        $this->assertNull($info->topic);
        $this->assertNull($info->httpOptions);
    }

    /**
     * Test from array maps the http key to http options.
     */
    public function test_from_array_maps_http_key_to_http_options(): void
    {
        $info = ServerInfo::fromArray([
            'server_url' => 'https://ntfy.example.com/',
            'http' => ['verify_ssl' => false],
        ]);

        $this->assertSame(['verify_ssl' => false], $info->httpOptions);
    }

    /**
     * Test create with auth sets username and password.
     */
    public function test_create_with_auth_sets_username_and_password(): void
    {
        $info = ServerInfo::createWithAuth('https://ntfy.example.com/', 'testuser', 'testpass');

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertSame('testuser', $info->authUsername);
        $this->assertSame('testpass', $info->authPassword);
        $this->assertNull($info->authToken);
    }

    /**
     * Test create with auth stores optional topic and http options.
     */
    public function test_create_with_auth_stores_topic_and_http_options(): void
    {
        $info = ServerInfo::createWithAuth(
            'https://ntfy.example.com/',
            'testuser',
            'testpass',
            'alerts',
            ['timeout' => 5],
        );

        $this->assertSame('alerts', $info->topic);
        $this->assertSame(['timeout' => 5], $info->httpOptions);
    }

    /**
     * Test create with token sets the token.
     */
    public function test_create_with_token_sets_token(): void
    {
        $info = ServerInfo::createWithToken('https://ntfy.example.com/', 'tk_test');

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertSame('tk_test', $info->authToken);
        $this->assertNull($info->authUsername);
        $this->assertNull($info->authPassword);
    }

    /**
     * Test create with token stores optional topic and http options.
     */
    public function test_create_with_token_stores_topic_and_http_options(): void
    {
        $info = ServerInfo::createWithToken(
            'https://ntfy.example.com/',
            'tk_test',
            'alerts',
            ['timeout' => 5],
        );

        $this->assertSame('alerts', $info->topic);
        $this->assertSame(['timeout' => 5], $info->httpOptions);
    }

    /**
     * Test create without auth sets no credentials.
     */
    public function test_create_without_auth_sets_no_credentials(): void
    {
        $info = ServerInfo::createWithoutAuth('https://ntfy.example.com/');

        $this->assertSame('https://ntfy.example.com/', $info->url);
        $this->assertNull($info->authUsername);
        $this->assertNull($info->authPassword);
        $this->assertNull($info->authToken);
    }

    /**
     * Test create without auth stores optional topic and http options.
     */
    public function test_create_without_auth_stores_topic_and_http_options(): void
    {
        $info = ServerInfo::createWithoutAuth(
            'https://ntfy.example.com/',
            'alerts',
            ['timeout' => 5],
        );

        $this->assertSame('alerts', $info->topic);
        $this->assertSame(['timeout' => 5], $info->httpOptions);
    }

    /**
     * Test has url is true for an https url.
     */
    public function test_has_url_is_true_for_https_url(): void
    {
        $info = ServerInfo::createWithoutAuth('https://ntfy.example.com/');

        $this->assertTrue($info->hasUrl());
    }

    /**
     * Test has url is true for an http url.
     */
    public function test_has_url_is_true_for_http_url(): void
    {
        $info = ServerInfo::createWithoutAuth('http://ntfy.example.com/');

        $this->assertTrue($info->hasUrl());
    }

    /**
     * Test has url is true for a host with a port.
     */
    public function test_has_url_is_true_for_host_with_port(): void
    {
        $info = ServerInfo::createWithoutAuth('http://localhost:8080');

        $this->assertTrue($info->hasUrl());
    }

    /**
     * Test has url is false for an unsupported scheme.
     */
    public function test_has_url_is_false_for_unsupported_scheme(): void
    {
        $info = ServerInfo::createWithoutAuth('ftp://ntfy.example.com/');

        $this->assertFalse($info->hasUrl());
    }

    /**
     * Test has url is false when the scheme is missing.
     */
    public function test_has_url_is_false_without_scheme(): void
    {
        $info = ServerInfo::createWithoutAuth('ntfy.example.com');

        $this->assertFalse($info->hasUrl());
    }

    /**
     * Test has url is false for an empty url.
     */
    public function test_has_url_is_false_for_empty_url(): void
    {
        $info = ServerInfo::createWithoutAuth('');

        $this->assertFalse($info->hasUrl());
    }

    /**
     * Test has auth is false without credentials.
     */
    public function test_has_auth_is_false_without_credentials(): void
    {
        $info = ServerInfo::createWithoutAuth('https://ntfy.example.com/');

        $this->assertFalse($info->hasAuth());
    }

    /**
     * Test has auth is true with a username and password.
     */
    public function test_has_auth_is_true_with_username_and_password(): void
    {
        $info = ServerInfo::createWithAuth('https://ntfy.example.com/', 'testuser', 'testpass');

        $this->assertTrue($info->hasAuth());
    }

    /**
     * Test has auth is true with a username only.
     */
    public function test_has_auth_is_true_with_username_only(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: 'testuser',
        );

        $this->assertTrue($info->hasAuth());
    }

    /**
     * Test has auth is true with a password only.
     */
    public function test_has_auth_is_true_with_password_only(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authPassword: 'testpass',
        );

        $this->assertTrue($info->hasAuth());
    }

    /**
     * Test has auth is true with a token.
     */
    public function test_has_auth_is_true_with_token(): void
    {
        $info = ServerInfo::createWithToken('https://ntfy.example.com/', 'tk_test');

        $this->assertTrue($info->hasAuth());
    }

    /**
     * Test has auth is false when credentials are empty strings.
     */
    public function test_has_auth_is_false_for_empty_string_credentials(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: '',
            authPassword: '',
        );

        $this->assertFalse($info->hasAuth());
    }

    /**
     * Test get auth method is none without credentials.
     */
    public function test_get_auth_method_is_none_without_credentials(): void
    {
        $info = ServerInfo::createWithoutAuth('https://ntfy.example.com/');

        $this->assertSame(AuthMethod::None, $info->getAuthMethod());
    }

    /**
     * Test get auth method is username and password with both credentials.
     */
    public function test_get_auth_method_is_username_password_with_username_and_password(): void
    {
        $info = ServerInfo::createWithAuth('https://ntfy.example.com/', 'testuser', 'testpass');

        $this->assertSame(AuthMethod::Login, $info->getAuthMethod());
    }

    /**
     * Test get auth method is username and password with a username only.
     */
    public function test_get_auth_method_is_username_password_with_username_only(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: 'testuser',
        );

        $this->assertSame(AuthMethod::Login, $info->getAuthMethod());
    }

    /**
     * Test get auth method is username and password with a password only.
     */
    public function test_get_auth_method_is_username_password_with_password_only(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authPassword: 'testpass',
        );

        $this->assertSame(AuthMethod::Login, $info->getAuthMethod());
    }

    /**
     * Test get auth method is token with a token.
     */
    public function test_get_auth_method_is_token_with_token(): void
    {
        $info = ServerInfo::createWithToken('https://ntfy.example.com/', 'tk_test');

        $this->assertSame(AuthMethod::Token, $info->getAuthMethod());
    }

    /**
     * Test get auth method prefers username and password over a token.
     */
    public function test_get_auth_method_prefers_username_password_over_token(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: 'testuser',
            authPassword: 'testpass',
            authToken: 'tk_test',
        );

        $this->assertSame(AuthMethod::Login, $info->getAuthMethod());
    }

    /**
     * Test get auth method prefers a username alone over a token.
     */
    public function test_get_auth_method_prefers_username_only_over_token(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: 'testuser',
            authToken: 'tk_test',
        );

        $this->assertSame(AuthMethod::Login, $info->getAuthMethod());
    }

    /**
     * Test get auth method from config with username and password.
     */
    public function test_get_auth_method_from_config_with_username_password(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
            'auth_username' => 'testuser',
            'auth_password' => 'testpass',
        ]);

        $this->assertSame(AuthMethod::Login, ServerInfo::fromConfig()->getAuthMethod());
    }

    /**
     * Test get auth method from config with a token.
     */
    public function test_get_auth_method_from_config_with_token(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
            'auth_token' => 'tk_test',
        ]);

        $this->assertSame(AuthMethod::Token, ServerInfo::fromConfig()->getAuthMethod());
    }

    /**
     * Test get auth method from config without credentials.
     */
    public function test_get_auth_method_from_config_without_credentials(): void
    {
        config()->set('ntfy.global', [
            'server_url' => 'https://ntfy.example.com/',
        ]);

        $this->assertSame(AuthMethod::None, ServerInfo::fromConfig()->getAuthMethod());
    }

    /**
     * Test get auth method is none for empty string credentials.
     *
     * getAuthMethod() uses empty() checks, so empty credentials do not
     * select an authentication method.
     */
    public function test_get_auth_method_is_none_for_empty_string_credentials(): void
    {
        $info = new ServerInfo(
            url: 'https://ntfy.example.com/',
            authUsername: '',
            authPassword: '',
        );

        $this->assertSame(AuthMethod::None, $info->getAuthMethod());
    }

    /**
     * Test get auth method is none for an empty string token.
     *
     * getAuthMethod() uses empty() checks, so an empty token does not
     * select token authentication.
     */
    public function test_get_auth_method_is_none_for_empty_string_token(): void
    {
        $info = ServerInfo::createWithToken('https://ntfy.example.com/', '');

        $this->assertSame(AuthMethod::None, $info->getAuthMethod());
    }

    /**
     * Test from config resolves the package defaults without published config.
     *
     * The service provider merges config/ntfy.php, so fromConfig() must work in
     * an application that has not published the config file.
     */
    public function test_from_config_resolves_package_defaults_without_published_config(): void
    {
        config()->set('ntfy', []);

        (new ServiceProvider($this->app))->register();

        $info = ServerInfo::fromConfig();

        $this->assertSame(config('ntfy.global.server_url'), $info->url);
        $this->assertTrue($info->hasUrl());
        $this->assertFalse($info->hasAuth());
        $this->assertSame(AuthMethod::None, $info->getAuthMethod());
    }
}
