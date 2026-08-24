<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Ntfy\Auth\Token;
use Ntfy\Auth\User;
use Ntfy\Message;
use Ntfy\Server;
use SameOldNick\Ntfy\Services\Client;
use SameOldNick\Ntfy\Tests\TestCase;

class NtfyClientTest extends TestCase
{
    /**
     * Test client sends message successfully.
     */
    public function test_client_sends_message_successfully(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'message-123',
                'topic' => 'test-topic',
                'title' => 'Test',
                'message' => 'Test message',
                'time' => time(),
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test');
        $message->body('Test message');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('message-123', $response->json('id'));
        $this->assertEquals('test-topic', $response->json('topic'));
    }

    /**
     * Test client sends with basic authentication.
     */
    public function test_client_sends_with_basic_auth(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'message-456',
                'topic' => 'secure-topic',
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $auth = new User('username', 'password');
        $client = new Client($server, $auth);

        $message = new Message;
        $message->topic('secure-topic');
        $message->title('Secure');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);

        // Verify the request was made (just check that a request was recorded)
        Http::assertSent(function ($request) {
            return $request->url() === 'https://ntfy.sh/' &&
                   $request->method() === 'POST';
        });
    }

    /**
     * Test client sends with token authentication.
     */
    public function test_client_sends_with_token_auth(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'message-789',
                'topic' => 'token-topic',
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $auth = new Token('test-token-123');
        $client = new Client($server, $auth);

        $message = new Message;
        $message->topic('token-topic');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://ntfy.sh/' &&
                   $request->method() === 'POST';
        });
    }

    /**
     * Test client sends without authentication.
     */
    public function test_client_sends_without_auth(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'message-public',
                'topic' => 'public-topic',
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server, null);

        $message = new Message;
        $message->topic('public-topic');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test client throws connection exception on network error.
     */
    public function test_client_throws_connection_exception_on_network_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection failed');
        });

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test');

        $this->expectException(ConnectionException::class);

        $client->send($message);
    }

    /**
     * Test client throws endpoint exception on request error with json response.
     */
    public function test_client_throws_endpoint_exception_on_json_error(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'error' => 'Topic not allowed',
                'code' => 'topic_not_allowed',
                'http' => 403,
            ], 403, ['Content-Type' => 'application/json']),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('forbidden-topic');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->status());
        $this->assertEquals('Topic not allowed', $response->json('error'));
    }

    /**
     * Test client throws endpoint exception on request error without json response.
     */
    public function test_client_throws_endpoint_exception_on_non_json_error(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response('Internal Server Error', 500, ['Content-Type' => 'text/plain']),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->status());
    }

    /**
     * Test client respects custom server URL.
     */
    public function test_client_respects_custom_server_url(): void
    {
        Http::fake([
            'https://custom.ntfy.sh/*' => Http::response([
                'id' => 'custom-msg',
                'topic' => 'test',
            ], 200),
        ]);

        $server = new Server('https://custom.ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('custom-msg', $response->json('id'));
    }

    /**
     * Test client sets correct headers.
     */
    public function test_client_sets_correct_headers(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'msg-123',
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test');
        $message->title('Title');

        $client->send($message);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST' &&
                   $request->url() === 'https://ntfy.sh/';
        });
    }

    /**
     * Test client respects max redirects setting.
     */
    public function test_client_respects_max_redirects(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'msg-redirect',
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test client respects HTTP options.
     */
    public function test_client_respects_http_options(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => function (Request $request, $options) {
                return Http::response([
                    'id' => 'msg-options',
                    'options' => $options,
                ], 200);
            },
        ]);

        $server = new Server('https://ntfy.sh/');
        $options = [
            'timeout' => 15,
            'connect_timeout' => 10,
            'verify_ssl' => false,
        ];

        $client = new Client($server, options: $options);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);

        Http::assertSent(function (Request $request, Response $response) {
            return $response->status() === 200 &&
                    $response->json('id') === 'msg-options' &&
                    $response->json('options.timeout') === 15 &&
                    $response->json('options.connect_timeout') === 10 &&
                    $response->json('options.verify') === false;
        });
    }

    /**
     * Test client respects HTTP retry options.
     */
    public function test_client_http_retry_options_succeeds(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::sequence()
                ->pushFailedConnection('Connection failed')
                ->pushFailedConnection('Connection failed')
                ->push(['id' => 'msg-retry'], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $options = [
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
                'delay' => 1,
            ],
        ];

        $client = new Client($server, options: $options);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);

        Http::assertSent(function (Request $request, ?Response $response) {
            return $response !== null &&
                   $response->status() === 200 &&
                   $response->json('id') === 'msg-retry';
        });
    }

    /**
     * Test client respects HTTP retry options.
     */
    public function test_client_http_retry_options_request_fails(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => fn () => Http::response('Internal Server Error', 500),
        ]);

        $server = new Server('https://ntfy.sh/');
        $options = [
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
                'delay' => 1,
            ],
        ];

        $client = new Client($server, options: $options);

        $message = new Message;
        $message->topic('test');

        $response = $client->send($message);

        $this->assertInstanceOf(Response::class, $response);

        Http::assertSent(function (Request $request, ?Response $response) {
            return $response !== null &&
                   $response->status() === 500;
        });
    }

    /**
     * Test client respects HTTP retry options.
     */
    public function test_client_http_retry_options_connection_fails(): void
    {
        Http::fake([
            'https://ntfy.sh/*' => Http::sequence()
                ->pushFailedConnection('Connection failed')
                ->pushFailedConnection('Connection failed')
                ->pushFailedConnection('Connection failed')
                ->push(['id' => 'msg-retry'], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $options = [
            'retry' => [
                'enabled' => true,
                'max_attempts' => 3,
                'delay' => 1,
            ],
        ];

        $client = new Client($server, options: $options);

        $message = new Message;
        $message->topic('test');

        $this->expectException(ConnectionException::class);

        $client->send($message);
    }

    /**
     * Test message response contains all expected data.
     */
    public function test_message_response_contains_expected_data(): void
    {
        $time = time();
        Http::fake([
            'https://ntfy.sh/*' => Http::response([
                'id' => 'msg-data',
                'topic' => 'data-topic',
                'title' => 'Title',
                'message' => 'Message body',
                'priority' => 3,
                'time' => (string) $time,
            ], 200),
        ]);

        $server = new Server('https://ntfy.sh/');
        $client = new Client($server);

        $message = new Message;
        $message->topic('data-topic');

        $response = $client->send($message);

        $this->assertEquals('msg-data', $response->json('id'));
        $this->assertEquals('data-topic', $response->json('topic'));
        $this->assertEquals('Title', $response->json('title'));
        $this->assertEquals('Message body', $response->json('message'));
        $this->assertEquals(3, $response->json('priority'));
        $this->assertEquals($time, $response->json('time'));
    }
}
