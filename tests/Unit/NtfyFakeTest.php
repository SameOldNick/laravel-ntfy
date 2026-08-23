<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Exception;
use Ntfy\Message;
use PHPUnit\Framework\AssertionFailedError;
use SameOldNick\Ntfy\DTOs\MessageResponse;
use SameOldNick\Ntfy\DTOs\ServerInfo;
use SameOldNick\Ntfy\Services\NtfyFake;
use SameOldNick\Ntfy\Tests\TestCase;

class NtfyFakeTest extends TestCase
{
    protected NtfyFake $fake;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new NtfyFake(ServerInfo::createWithoutAuth(
            url: 'https://ntfy.example.com',
        ));
    }

    /**
     * Test fake sends message and returns response.
     */
    public function test_fake_sends_message_and_returns_response(): void
    {
        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test Message');
        $message->body('Test body');

        $response = $this->fake->send($message);

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertEquals('test-topic', $response->topic());
        $this->assertEquals('Test Message', $response->title());
        $this->assertEquals('Test body', $response->message());
        $this->assertNotNull($response->id());
    }

    /**
     * Test fake tracks sent messages.
     */
    public function test_fake_tracks_sent_messages(): void
    {
        $message1 = new Message;
        $message1->topic('topic1');

        $message2 = new Message;
        $message2->topic('topic2');

        $this->fake->send($message1);
        $this->fake->send($message2);

        $sent = $this->fake->sent();

        $this->assertCount(2, $sent);
    }

    /**
     * Test fake sent without callback returns all messages.
     */
    public function test_fake_sent_without_callback_returns_all(): void
    {
        $message1 = new Message;
        $message1->topic('topic1');
        $message1->title('Message 1');

        $message2 = new Message;
        $message2->topic('topic2');
        $message2->title('Message 2');

        $this->fake->send($message1);
        $this->fake->send($message2);

        $sent = $this->fake->sent();

        $this->assertCount(2, $sent);
        $this->assertEquals('Message 1', $sent[0]->getData()['title']);
        $this->assertEquals('Message 2', $sent[1]->getData()['title']);
    }

    /**
     * Test fake sent with callback filters messages.
     */
    public function test_fake_sent_with_callback_filters(): void
    {
        $message1 = new Message;
        $message1->topic('alerts');

        $message2 = new Message;
        $message2->topic('notifications');

        $this->fake->send($message1);
        $this->fake->send($message2);

        // Test without callback first
        $all = $this->fake->sent();
        $this->assertCount(2, $all);

        // Now test with callback
        $sent = $this->fake->sent(function ($message) {
            $data = $message->getData();

            return $data['topic'] === 'notifications';
        });

        $this->assertCount(1, $sent);
    }

    /**
     * Test fake assert sent passes when message was sent.
     */
    public function test_fake_assert_sent_passes_when_sent(): void
    {
        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test');

        $this->fake->send($message);

        // Should not throw
        $this->fake->assertSent();

        $this->assertTrue(true);
    }

    /**
     * Test fake assert sent fails when no message sent.
     */
    public function test_fake_assert_sent_fails_when_not_sent(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->fake->assertSent();
    }

    /**
     * Test fake assert sent with callback.
     */
    public function test_fake_assert_sent_with_callback(): void
    {
        $message = new Message;
        $message->topic('alerts');
        $message->priority(5);

        $this->fake->send($message);

        // Should not throw
        $this->fake->assertSent(function ($message) {
            return $message->getData()['priority'] === 5;
        });

        $this->assertTrue(true);
    }

    /**
     * Test fake assert sent with failing callback.
     */
    public function test_fake_assert_sent_fails_with_bad_callback(): void
    {
        $message = new Message;
        $message->topic('notifications');
        $message->title('Test notification');

        $this->fake->send($message);

        $this->expectException(AssertionFailedError::class);

        $this->fake->assertSent(function ($message) {
            try {
                $data = $message->getData();

                return isset($data['priority']) && $data['priority'] === 5;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    /**
     * Test fake assert not sent passes when no message sent.
     */
    public function test_fake_assert_not_sent_passes_when_empty(): void
    {
        // Should not throw
        $this->fake->assertNotSent();

        $this->assertTrue(true);
    }

    /**
     * Test fake assert not sent fails when message sent.
     */
    public function test_fake_assert_not_sent_fails_when_sent(): void
    {
        $message = new Message;
        $message->topic('test');

        $this->fake->send($message);

        $this->expectException(AssertionFailedError::class);

        $this->fake->assertNotSent();
    }

    /**
     * Test fake assert not sent with failing callback.
     */
    public function test_fake_assert_not_sent_with_callback(): void
    {
        $message1 = new Message;
        $message1->topic('topic1');
        $message1->title('Message 1');

        $message2 = new Message;
        $message2->topic('topic2');
        $message2->title('Message 2');

        $this->fake->send($message1);
        $this->fake->send($message2);

        // Should not throw - no message with priority 5
        $this->fake->assertNotSent(function ($message) {
            $data = $message->getData();

            return isset($data['priority']) && $data['priority'] === 5;
        });

        $this->assertTrue(true);
    }

    /**
     * Test fake assert not sent fails with matching callback.
     */
    public function test_fake_assert_not_sent_fails_with_matching_callback(): void
    {
        $message = new Message;
        $message->topic('alerts');

        $this->fake->send($message);

        $this->expectException(AssertionFailedError::class);

        $this->fake->assertNotSent(function ($message) {
            return $message->getData()['topic'] === 'alerts';
        });
    }

    /**
     * Test fake assert sent count.
     */
    public function test_fake_assert_sent_count(): void
    {
        $message1 = new Message;
        $message1->topic('test1');

        $message2 = new Message;
        $message2->topic('test2');

        $message3 = new Message;
        $message3->topic('test3');

        $this->fake->send($message1);
        $this->fake->send($message2);
        $this->fake->send($message3);

        // Should not throw
        $this->fake->assertSentCount(3);

        $this->assertTrue(true);
    }

    /**
     * Test fake assert sent count fails on mismatch.
     */
    public function test_fake_assert_sent_count_fails_on_mismatch(): void
    {
        $message1 = new Message;
        $message1->topic('test1');

        $message2 = new Message;
        $message2->topic('test2');

        $this->fake->send($message1);
        $this->fake->send($message2);

        $this->expectException(AssertionFailedError::class);

        $this->fake->assertSentCount(5);
    }

    /**
     * Test fake assert nothing sent.
     */
    public function test_fake_assert_nothing_sent(): void
    {
        // Should not throw
        $this->fake->assertNothingSent();

        $this->assertTrue(true);
    }

    /**
     * Test fake assert nothing sent fails when sent.
     */
    public function test_fake_assert_nothing_sent_fails(): void
    {
        $message = new Message;
        $message->topic('test');
        $this->fake->send($message);

        $this->expectException(AssertionFailedError::class);

        $this->fake->assertNothingSent();
    }

    /**
     * Test fake response contains message data.
     */
    public function test_fake_response_contains_message_data(): void
    {
        $message = new Message;
        $message->topic('test-topic');
        $message->title('Test Title');
        $message->body('Test body');
        $message->priority(4);

        $response = $this->fake->send($message);

        $this->assertEquals('test-topic', $response->topic());
        $this->assertEquals('Test Title', $response->title());
        $this->assertEquals('Test body', $response->message());
        $this->assertEquals(4, $response->priority());
    }

    /**
     * Test fake response includes timestamp.
     */
    public function test_fake_response_includes_timestamp(): void
    {
        $message = new Message;
        $message->topic('test');

        $response = $this->fake->send($message);

        $this->assertNotNull($response->time());
        $this->assertIsInt($response->time());
    }

    /**
     * Test fake response includes unique ID.
     */
    public function test_fake_response_includes_unique_id(): void
    {
        $message1 = new Message;
        $message1->topic('test');

        $message2 = new Message;
        $message2->topic('test');

        $response1 = $this->fake->send($message1);
        $response2 = $this->fake->send($message2);

        $this->assertNotNull($response1->id());
        $this->assertNotNull($response2->id());
        $this->assertNotEquals($response1->id(), $response2->id());
    }

    /**
     * Test fake can be used for integration testing.
     */
    public function test_fake_can_be_used_for_integration_testing(): void
    {
        // Simulate sending multiple notifications
        $message1 = new Message;
        $message1->topic('alerts');
        $message1->title('Alert 1');
        $message1->priority(5);

        $message2 = new Message;
        $message2->topic('notifications');
        $message2->title('Notification 1');
        $message2->priority(1);

        $this->fake->send($message1);
        $this->fake->send($message2);

        // Assert messages sent
        $this->fake->assertSentCount(2);

        // Assert specific message types
        $alerts = $this->fake->sent(function ($message) {
            return $message->getData()['topic'] === 'alerts';
        });

        $this->assertCount(1, $alerts);
        $this->assertEquals(5, $alerts[0]->getData()['priority']);

        // Assert high priority messages
        $this->fake->assertSent(function ($message) {
            return $message->getData()['priority'] >= 5;
        });
    }
}
