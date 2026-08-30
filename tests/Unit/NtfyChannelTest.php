<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Ntfy\Message;
use Ntfy\Server;
use SameOldNick\Ntfy\Channels\NtfyChannel;
use SameOldNick\Ntfy\Concerns\NtfyNotifiable;
use SameOldNick\Ntfy\Contracts\NtfyNotification;
use SameOldNick\Ntfy\DTOs\MessageWithAttachment;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Models\NtfyConfiguration;
use SameOldNick\Ntfy\Services\Client;
use SameOldNick\Ntfy\Services\MessageBuilder;
use SameOldNick\Ntfy\Services\Ntfy;
use SameOldNick\Ntfy\Tests\TestCase;

class NtfyChannelTest extends TestCase
{
    protected NtfyChannel $channel;

    protected MockInterface|Ntfy $ntfyMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ntfyMock = Mockery::mock(Ntfy::class)->makePartial();
        $this->channel = new NtfyChannel($this->ntfyMock);
    }

    /**
     * Test channel is disabled when not configured.
     */
    public function test_channel_is_disabled_when_not_configured(): void
    {
        config(['ntfy.enabled' => false]);

        $this->assertFalse($this->channel->isEnabled());
    }

    /**
     * Test channel is enabled when configured.
     */
    public function test_channel_is_enabled_when_configured(): void
    {
        config(['ntfy.enabled' => true]);

        $this->assertTrue($this->channel->isEnabled());
    }

    /**
     * Test channel sends notification when enabled.
     */
    public function test_channel_sends_notification_when_enabled(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'test-topic',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('test-topic');
                $message->title('Test');
                $message->body('Test message');

                return $message;
            }
        };

        $response = new Response(Http::response([
            'id' => 'msg-123',
            'topic' => 'test-topic',
        ])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel skips sending when disabled.
     */
    public function test_channel_skips_sending_when_disabled(): void
    {
        config(['ntfy.enabled' => false]);

        $notifiable = new class {};

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                return new Message;
            }
        };

        $this->ntfyMock
            ->shouldNotReceive('send');

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel sets topic from notifiable routing.
     */
    public function test_channel_sets_topic_from_notifiable_routing(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'custom-topic-from-notifiable',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->title('Test');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://ntfy.sh/' &&
                       $serverInfo->topic === 'custom-topic-from-notifiable';
            }))
            ->andReturn(
                Mockery::mock(new Client(new Server('https://ntfy.sh/')))
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof Message &&
                               $message->getData()['title'] === 'Test' &&
                               $message->getData()['topic'] === 'custom-topic-from-notifiable';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $actualResponse = $this->channel->send($notifiable, $notification);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals('msg-456', $actualResponse->json('id'));
    }

    /**
     * Test channel attaches content.
     */
    public function test_channel_attaches_content(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'custom-topic-from-notifiable',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message|MessageWithAttachment
            {
                return MessageBuilder::make()
                    ->title('Test')
                    ->attachContent('Sample attachment content')
                    ->build();
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://ntfy.sh/' &&
                       $serverInfo->topic === 'custom-topic-from-notifiable';
            }))
            ->andReturn(
                Mockery::mock(new Client(new Server('https://ntfy.sh/')))
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof MessageWithAttachment &&
                               $message->message->getData()['title'] === 'Test' &&
                               $message->message->getData()['topic'] === 'custom-topic-from-notifiable' &&
                               $message->content === 'Sample attachment content';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $actualResponse = $this->channel->send($notifiable, $notification);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals('msg-456', $actualResponse->json('id'));
    }

    /**
     * Test channel attaches storage file.
     */
    public function test_channel_attaches_storage_file(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'custom-topic-from-notifiable',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message|MessageWithAttachment
            {
                return MessageBuilder::make()
                    ->title('Test')
                    ->attachStorage('test-attachment.txt', 'local')
                    ->build();
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://ntfy.sh/' &&
                       $serverInfo->topic === 'custom-topic-from-notifiable';
            }))
            ->andReturn(
                Mockery::mock(new Client(new Server('https://ntfy.sh/')))
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof MessageWithAttachment &&
                               $message->message->getData()['title'] === 'Test' &&
                               $message->message->getData()['topic'] === 'custom-topic-from-notifiable' &&
                               $message->path === 'test-attachment.txt' &&
                               $message->disk === 'local';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $actualResponse = $this->channel->send($notifiable, $notification);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals('msg-456', $actualResponse->json('id'));
    }

    /**
     * Test channel sets server info from notifiable routing.
     */
    public function test_channel_sets_server_info_from_notifiable_routing(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithToken(
                    url: 'https://custom.ntfy.sh/',
                    token: 'custom-token',
                    topic: 'custom-topic',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->title('Test');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->passthru();

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://custom.ntfy.sh/' &&
                       $serverInfo->authToken === 'custom-token' &&
                       $serverInfo->topic === 'custom-topic';
            }))
            ->andReturn(
                Mockery::mock(Client::class)
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof Message &&
                               $message->getData()['title'] === 'Test' &&
                               $message->getData()['topic'] === 'custom-topic';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel routes to ntfy notifiable with token auth.
     */
    public function test_channel_routes_to_ntfy_notifable_token(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class extends Model
        {
            use NtfyNotifiable;

            public function routeNotificationFor($channel, $notification)
            {
                return match ($channel) {
                    'ntfy' => $this->resolveNtfyRoute(),
                    default => null,
                };
            }
        };

        $notifiable->setRelation('ntfyConfiguration', new NtfyConfiguration([
            'server_url' => 'https://custom.ntfy.sh/',
            'auth_token' => 'custom-token',
            'topic' => 'custom-topic',
        ]));

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->title('Test');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->passthru();

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://custom.ntfy.sh/' &&
                       $serverInfo->authToken === 'custom-token' &&
                       $serverInfo->topic === 'custom-topic';
            }))
            ->andReturn(
                Mockery::mock(Client::class)
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof Message &&
                               $message->getData()['title'] === 'Test' &&
                               $message->getData()['topic'] === 'custom-topic';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel routes to ntfy notifiable with username and password auth.
     */
    public function test_channel_routes_to_ntfy_notifable_username_password(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class extends Model
        {
            use NtfyNotifiable;

            public function routeNotificationFor($channel, $notification)
            {
                return match ($channel) {
                    'ntfy' => $this->resolveNtfyRoute(),
                    default => null,
                };
            }
        };

        $notifiable->setRelation('ntfyConfiguration', new NtfyConfiguration([
            'server_url' => 'https://custom.ntfy.sh/',
            'username' => 'custom-username',
            'password' => 'custom-password',
            'topic' => 'custom-topic',
        ]));

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->title('Test');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->passthru();

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://custom.ntfy.sh/' &&
                       $serverInfo->authUsername === 'custom-username' &&
                       $serverInfo->authPassword === 'custom-password' &&
                       $serverInfo->topic === 'custom-topic';
            }))
            ->andReturn(
                Mockery::mock(Client::class)
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof Message &&
                               $message->getData()['title'] === 'Test' &&
                               $message->getData()['topic'] === 'custom-topic';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel routes to notifiable with no auth.
     */
    public function test_channel_routes_to_ntfy_notifable_no_auth(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class extends Model
        {
            use NtfyNotifiable;

            public function routeNotificationFor($channel, $notification)
            {
                return match ($channel) {
                    'ntfy' => $this->resolveNtfyRoute(),
                    default => null,
                };
            }
        };

        $notifiable->setRelation('ntfyConfiguration', new NtfyConfiguration([
            'server_url' => 'https://custom.ntfy.sh/',
            'topic' => 'custom-topic',
        ]));

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->title('Test');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-456'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->passthru();

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://custom.ntfy.sh/' &&
                       empty($serverInfo->authUsername) &&
                       empty($serverInfo->authPassword) &&
                       empty($serverInfo->authToken) &&
                       $serverInfo->topic === 'custom-topic';
            }))
            ->andReturn(
                Mockery::mock(Client::class)
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        return $message instanceof Message &&
                               $message->getData()['title'] === 'Test' &&
                               $message->getData()['topic'] === 'custom-topic';
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel uses toNtfy method when available.
     */
    public function test_channel_uses_to_ntfy_method(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'test-topic',
                );
            }
        };

        $notification = new class extends Notification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('topic-from-method');
                $message->title('Method Title');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-789'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel throws exception when notification is invalid.
     */
    public function test_channel_throws_exception_when_notification_invalid(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class {};

        $notification = new class extends Notification {};

        $this->ntfyMock
            ->shouldReceive('send')
            ->never();

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel works with NtfyNotification interface.
     */
    public function test_channel_works_with_ntfy_notification_interface(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'interface-topic',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('interface-topic');
                $message->title('Interface Message');

                return $message;
            }
        };

        $response = new Response(Http::response([
            'id' => 'msg-interface',
            'topic' => 'interface-topic',
        ])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel handles notifiable without routing method.
     */
    public function test_channel_handles_notifiable_without_routing_method(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class {};

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('fallback-topic');
                $message->title('No routing');

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-no-routing'])->wait());

        // Won't send because routeNotificationFor doesn't return a ServerInfo instance
        $this->ntfyMock
            ->shouldReceive('send')
            ->never();

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel sends message with all properties.
     */
    public function test_channel_sends_complete_message(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'events',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('internal-topic');
                $message->title('Important Event');
                $message->body('Something happened');
                $message->priority(5);
                $message->tags(['event', 'alert']);

                return $message;
            }
        };

        $response = new Response(Http::response(['id' => 'msg-complete'])->wait());

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->passthru();

        $this->ntfyMock
            ->shouldReceive('createClient')
            ->once()
            ->with(Mockery::on(function ($serverInfo) {
                return $serverInfo instanceof ServerInfo &&
                       $serverInfo->url === 'https://ntfy.sh/' &&
                       $serverInfo->topic === 'events';
            }))
            ->andReturn(
                Mockery::mock(Client::class)
                    ->makePartial()
                    ->shouldReceive('send')
                    ->once()
                    ->with(Mockery::on(function ($message) {
                        $data = $message->getData();

                        return $message instanceof Message &&
                               $data['topic'] === 'events' && // Should use routing
                               $data['title'] === 'Important Event' &&
                               $data['message'] === 'Something happened' &&
                               $data['priority'] === 5 &&
                               $data['tags'] === ['event', 'alert'];
                    }))
                    ->andReturn($response)
                    ->getMock(),
            );

        $this->channel->send($notifiable, $notification);
    }

    /**
     * Test channel passes notifiable to toNtfy method.
     */
    public function test_channel_passes_notifiable_to_to_ntfy(): void
    {
        config(['ntfy.enabled' => true]);

        $notifiable = new class
        {
            public $id = 123;

            public function routeNotificationFor($channel, $notification)
            {
                return ServerInfo::createWithoutAuth(
                    url: 'https://ntfy.sh/',
                    topic: 'topic',
                );
            }
        };

        $notification = new class extends Notification implements NtfyNotification
        {
            public function toNtfy(object $notifiable): Message
            {
                $message = new Message;
                $message->topic('notifications');
                $message->body("User {$notifiable->id} triggered notification");

                return $message;
            }
        };

        $response = Http::response([
            'id' => 'msg-user-123',
            'topic' => 'notifications',
            'message' => 'User 123 triggered notification',
        ], 200);

        $this->ntfyMock
            ->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message->getData()['message'], 'User 123');
            }), Mockery::any())
            ->andReturn(new Response($response->wait()));

        $this->channel->send($notifiable, $notification);
    }
}
