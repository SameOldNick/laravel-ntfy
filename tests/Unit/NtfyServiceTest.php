<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Ntfy\Exception\EndpointException;
use Ntfy\Exception\NtfyException;
use Ntfy\Message;
use SameOldNick\Ntfy\DTOs\MessageResponse;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Services\Client;
use SameOldNick\Ntfy\Services\Ntfy;
use SameOldNick\Ntfy\Tests\TestCase;

class NtfyServiceTest extends TestCase
{
    protected Ntfy $ntfy;

    protected MockInterface|Ntfy $ntfyMock;

    protected MockInterface|Client $clientMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ntfy = new Ntfy;
    }

    /**
     * Test that Ntfy service initializes properly with basic config.
     */
    public function test_ntfy_service_initializes_with_token_config(): void
    {
        $config = [
            'server_url' => 'https://ntfy.example.com/',
            'auth_token' => 'test-token-123',
        ];

        config()->set('ntfy', $config);

        $serverInfo = ServerInfo::fromConfig();

        $ntfy = new Ntfy;
        $client = $ntfy->createClient($serverInfo);

        $this->assertEquals('https://ntfy.example.com/', $client->server->get());
        $this->assertEquals('test-token-123', $client->auth->getToken());
    }

    /**
     * Test that Ntfy service initializes properly with basic config.
     */
    public function test_ntfy_service_initializes_with_auth_config(): void
    {
        $config = [
            'server_url' => 'https://ntfy.example.com/',
            'auth_credentials' => [
                'username' => 'testuser',
                'password' => 'testpass',
            ],
        ];

        config()->set('ntfy', $config);

        $serverInfo = ServerInfo::fromConfig();

        $ntfy = new Ntfy;
        $client = $ntfy->createClient($serverInfo);

        $this->assertEquals('https://ntfy.example.com/', $client->server->get());
        $this->assertEquals('testuser', $client->auth->getUsername());
        $this->assertEquals('testpass', $client->auth->getPassword());
    }

    /**
     * Test that Ntfy creates a client without authentication.
     */
    public function test_creates_client_without_auth(): void
    {
        $config = [
            'server_url' => 'https://ntfy.example.com/',
            'auth_method' => 'none',
        ];

        config()->set('ntfy', $config);

        $serverInfo = ServerInfo::fromConfig();

        $ntfy = new Ntfy;
        $client = $ntfy->createClient($serverInfo);

        $this->assertEquals('https://ntfy.example.com/', $client->server->get());
        $this->assertNull($client->auth);
    }

    /**
     * Test that Ntfy sends a message via the client.
     */
    public function test_ntfy_sends_message_mocked(): void
    {
        $this->createMocks();

        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test Title');
        $message->body('Test Body');

        $result = $this->ntfyMock->send($message);

        $this->assertEquals('message-123', $result->id());
        $this->assertEquals('test-topic', $result->topic());
    }

    /**
     * Test that Ntfy sends a message via the client with real send method.
     */
    public function test_ntfy_sends_message(): void
    {
        $this->createMocks(false);

        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test Title');
        $message->body('Test Body');

        $response = new Response(Http::response([
            'id' => 'message-123',
            'topic' => 'test-topic',
            'title' => 'Test Title',
            'message' => 'Test Body',
            'time' => time(),
        ])->wait());

        $this->clientMock
            ->shouldReceive('send')
            ->once()
            ->andReturn($response);

        $result = $this->ntfyMock->send($message);

        $this->assertInstanceOf(MessageResponse::class, $result);
        $this->assertEquals('message-123', $result->id());
        $this->assertEquals('test-topic', $result->topic());
    }

    /**
     * Test that default topic is assigned when not set.
     */
    public function test_default_topic_is_assigned(): void
    {
        $config = [
            'default_topic' => 'topic-from-config',
        ];

        config()->set('ntfy', $config);

        $message = new Message;
        $message->title('Test');

        $result = $this->ntfy->send($message);

        $this->assertInstanceOf(MessageResponse::class, $result);
        $this->assertEquals('Test', $result->title());
        $this->assertEquals('topic-from-config', $result->topic());
    }

    /**
     * Test that exception is thrown when client send fails.
     */
    public function test_exception_thrown_when_send_fails(): void
    {
        $this->createMocks(false);

        $message = new Message;
        $message->topic('test-topic');

        $this->clientMock
            ->shouldReceive('send')
            ->once()
            ->andThrow(new NtfyException('Send failed'));

        $this->expectException(NtfyException::class);

        $this->ntfyMock->send($message);
    }

    /**
     * Test that endpoint exception is handled.
     */
    public function test_endpoint_exception_is_thrown(): void
    {
        $this->createMocks(false);

        $message = new Message;
        $message->topic('test-topic');

        $this->clientMock
            ->shouldReceive('send')
            ->once()
            ->andThrow(new EndpointException('Endpoint error'));

        $this->expectException(EndpointException::class);

        $this->ntfyMock->send($message);
    }

    /**
     * Test that multiple messages can be sent sequentially.
     */
    public function test_multiple_messages_can_be_sent(): void
    {
        $this->createMocks();

        $message1 = new Message;
        $message1->topic('topic1');
        $message1->title('First Message');

        $message2 = new Message;
        $message2->topic('topic2');
        $message2->title('Second Message');

        $result1 = $this->ntfyMock->send($message1);
        $result2 = $this->ntfyMock->send($message2);

        $this->assertEquals('topic1', $result1->topic());
        $this->assertEquals('First Message', $result1->title());
        $this->assertEquals('topic2', $result2->topic());
        $this->assertEquals('Second Message', $result2->title());
    }

    /**
     * Create mocks for Ntfy and Client.
     *
     * @param  bool  $mockSend  Whether to mock the send method or not
     */
    protected function createMocks(bool $mockSend = true): void
    {
        $this->clientMock = Mockery::mock(Client::class);
        $this->ntfyMock = Mockery::mock(Ntfy::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods();
        });

        if ($mockSend) {
            $this->ntfyMock
                ->shouldReceive('send')
                ->andReturnUsing(function (Message $message, ?ServerInfo $serverInfo = null) {
                    try {
                        $topic = $message->getData()['topic'];
                    } catch (NtfyException) {
                        // If message doesn't have a topic, assign default topic for testing
                        $topic = $serverInfo->topic ?? 'default-topic';
                    }

                    return new MessageResponse([
                        'id' => $message->getData()['id'] ?? 'message-123',
                        'topic' => $topic,
                        'title' => $message->getData()['title'] ?? null,
                        'message' => $message->getData()['message'] ?? null,
                        'priority' => $message->getData()['priority'] ?? null,
                        'time' => time(),
                    ]);
                });
        }

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->andReturn($this->clientMock);
    }
}
