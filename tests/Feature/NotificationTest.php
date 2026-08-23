<?php

namespace SameOldNick\Ntfy\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use SameOldNick\Ntfy\Services\MessageBuilder;
use SameOldNick\Ntfy\Tests\Fixtures\TestNotification;
use SameOldNick\Ntfy\Tests\TestCase;
use Workbench\App\Models\User;

class NotificationTest extends TestCase
{
    /**
     * Test that a user is notified with a basic notification.
     */
    public function test_sends_basic_notification_to_user(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        $notification = new TestNotification(
            title: 'Test Notification',
            message: 'This is a test notification.',
        );

        $user->notify($notification);

        Notification::assertSentTo(
            [$user], TestNotification::class,
        );
    }

    /**
     * Test that a user is notified with a custom notification.
     */
    public function test_sends_custom_notification_to_user(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        $notification = new TestNotification(
            title: 'Test Notification',
            message: 'This is a test notification.',
            topic: 'test-topic',
            priority: 3,
            tags: ['test', 'notification'],
            withMessageBuilder: function (MessageBuilder $builder) {
                return $builder->title('Overridden Title');
            },
        );

        $user->notify($notification);

        Notification::assertSentTo(
            [$user], function (TestNotification $notification, $channels) {
                return $notification->title === 'Test Notification'
                    && $notification->message === 'This is a test notification.'
                    && $notification->topic === 'test-topic'
                    && $notification->priority === 3
                    && $notification->tags === ['test', 'notification']
                    && is_callable($notification->withMessageBuilder);
            },
        );
    }

    /**
     * Test that the notification is routed to the user's ntfy configuration and sent via HTTP.
     */
    public function test_routes_notification_to_user_http_success(): void
    {
        Http::fake([
            // Stub a JSON response for GitHub endpoints...
            'ntfy.fakeuser.com/*' => function (Request $request) {
                return Http::response([
                    'id' => 'msg-123',
                    'topic' => 'test-topic',
                    'title' => 'Test Notification',
                    'message' => 'This is a test notification.',
                    'priority' => 3,
                    'time' => time(),
                ], 200);
            },

            // Stub a string response for all other endpoints...
            '*' => function (Request $request) {
                return Http::response('Not Found', 404);
            },
        ]);

        $user = $this->createTestUser([
            'server_url' => 'https://ntfy.fakeuser.com/',
            'auth_token' => 'test-token-123',
            'topic' => 'test-topic',
        ]);

        $notification = new TestNotification(
            title: 'Test Notification',
            message: 'This is a test notification.',
        );

        $user->notifyNow($notification);

        Http::assertSent(function (Request $request, Response $response) {
            return $response->status() === 200 &&
                    $response->json('id') === 'msg-123';
        });
    }

    /**
     * Test that the notification is routed to the user's ntfy configuration and fails via HTTP.
     */
    public function test_routes_notification_to_user_http_failure(): void
    {
        Http::fake([
            // Stub a JSON response for GitHub endpoints...
            'ntfy.fakeuser.com/*' => function (Request $request) {
                return Http::response('Internal Server Error', 500);
            },

            // Stub a string response for all other endpoints...
            '*' => function (Request $request) {
                return Http::response('Not Found', 404);
            },
        ]);

        $user = $this->createTestUser([
            'server_url' => 'https://ntfy.fakeuser.com/',
            'auth_token' => 'test-token-123',
            'topic' => 'test-topic',
        ]);

        $notification = new TestNotification(
            title: 'Test Notification',
            message: 'This is a test notification.',
        );

        $user->notifyNow($notification);

        Http::assertSent(function (Request $request, Response $response) {
            return $response->status() === 500 &&
                    $response->body() === 'Internal Server Error';
        });
    }

    /**
     * Create a test user with ntfy configuration.
     */
    protected function createTestUser(?array $ntfyConfiguration = null): User
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->ntfyConfiguration()->create($ntfyConfiguration ?? [
            'server_url' => 'https://ntfy.example.com/',
            'auth_token' => 'test-token-123',
            'topic' => 'test-topic',
        ]);

        return $user;
    }
}
