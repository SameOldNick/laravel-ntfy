<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Carbon\CarbonInterface;
use SameOldNick\Ntfy\DTOs\MessageResponse;
use SameOldNick\Ntfy\Tests\TestCase;

class MessageResponseTest extends TestCase
{
    /**
     * Test message response stores response data.
     */
    public function test_message_response_stores_response_data(): void
    {
        $data = [
            'id' => 'msg-123',
            'topic' => 'test-topic',
            'title' => 'Test Title',
            'message' => 'Test message',
            'priority' => 3,
            'time' => (string) time(),
        ];

        $response = new MessageResponse($data);

        $this->assertInstanceOf(MessageResponse::class, $response);
    }

    /**
     * Test message response id method.
     */
    public function test_message_response_id_method(): void
    {
        $response = new MessageResponse(['id' => 'msg-unique-123']);

        $this->assertEquals('msg-unique-123', $response->id());
    }

    /**
     * Test message response id returns null when not set.
     */
    public function test_message_response_id_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->id());
    }

    /**
     * Test message response time method.
     */
    public function test_message_response_time_method(): void
    {
        $timestamp = time();
        $response = new MessageResponse(['time' => (string) $timestamp]);

        $this->assertEquals($timestamp, $response->time());
    }

    /**
     * Test message response time returns null when not set.
     */
    public function test_message_response_time_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->time());
    }

    /**
     * Test message response time is converted to int.
     */
    public function test_message_response_time_is_int(): void
    {
        $response = new MessageResponse(['time' => '1234567890']);

        $this->assertIsInt($response->time());
        $this->assertEquals(1234567890, $response->time());
    }

    /**
     * Test message response datetime method.
     */
    public function test_message_response_datetime_method(): void
    {
        $timestamp = time();
        $response = new MessageResponse(['time' => (string) $timestamp]);

        $datetime = $response->dateTime();

        $this->assertInstanceOf(CarbonInterface::class, $datetime);
        $this->assertEquals($timestamp, $datetime->getTimestamp());
    }

    /**
     * Test message response datetime returns null when time not set.
     */
    public function test_message_response_datetime_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->dateTime());
    }

    /**
     * Test message response topic method.
     */
    public function test_message_response_topic_method(): void
    {
        $response = new MessageResponse(['topic' => 'alerts']);

        $this->assertEquals('alerts', $response->topic());
    }

    /**
     * Test message response topic returns null when not set.
     */
    public function test_message_response_topic_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->topic());
    }

    /**
     * Test message response message method.
     */
    public function test_message_response_message_method(): void
    {
        $response = new MessageResponse(['message' => 'This is the message body']);

        $this->assertEquals('This is the message body', $response->message());
    }

    /**
     * Test message response message returns null when not set.
     */
    public function test_message_response_message_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->message());
    }

    /**
     * Test message response title method.
     */
    public function test_message_response_title_method(): void
    {
        $response = new MessageResponse(['title' => 'Alert Title']);

        $this->assertEquals('Alert Title', $response->title());
    }

    /**
     * Test message response title returns null when not set.
     */
    public function test_message_response_title_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->title());
    }

    /**
     * Test message response priority method.
     */
    public function test_message_response_priority_method(): void
    {
        $response = new MessageResponse(['priority' => 5]);

        $this->assertEquals(5, $response->priority());
    }

    /**
     * Test message response priority returns null when not set.
     */
    public function test_message_response_priority_returns_null(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->priority());
    }

    /**
     * Test message response get response data method.
     */
    public function test_message_response_get_response_data(): void
    {
        $data = [
            'id' => 'msg-456',
            'topic' => 'notifications',
            'title' => 'Title',
            'message' => 'Message',
            'priority' => 2,
            'time' => (string) time(),
        ];

        $response = new MessageResponse($data);

        $this->assertEquals($data, $response->getResponseData());
    }

    /**
     * Test message response with complete data.
     */
    public function test_message_response_with_complete_data(): void
    {
        $timestamp = time();
        $data = [
            'id' => 'msg-complete',
            'topic' => 'events',
            'title' => 'Event Occurred',
            'message' => 'Something important happened',
            'priority' => 4,
            'time' => (string) $timestamp,
        ];

        $response = new MessageResponse($data);

        $this->assertEquals('msg-complete', $response->id());
        $this->assertEquals('events', $response->topic());
        $this->assertEquals('Event Occurred', $response->title());
        $this->assertEquals('Something important happened', $response->message());
        $this->assertEquals(4, $response->priority());
        $this->assertEquals($timestamp, $response->time());
        $this->assertInstanceOf(CarbonInterface::class, $response->dateTime());
    }

    /**
     * Test message response with partial data.
     */
    public function test_message_response_with_partial_data(): void
    {
        $response = new MessageResponse([
            'id' => 'msg-partial',
            'message' => 'Partial message',
        ]);

        $this->assertEquals('msg-partial', $response->id());
        $this->assertEquals('Partial message', $response->message());
        $this->assertNull($response->topic());
        $this->assertNull($response->title());
        $this->assertNull($response->priority());
        $this->assertNull($response->time());
    }

    /**
     * Test message response with empty data.
     */
    public function test_message_response_with_empty_data(): void
    {
        $response = new MessageResponse([]);

        $this->assertNull($response->id());
        $this->assertNull($response->topic());
        $this->assertNull($response->title());
        $this->assertNull($response->message());
        $this->assertNull($response->priority());
        $this->assertNull($response->time());
        $this->assertNull($response->dateTime());
    }

    /**
     * Test message response priority is integer.
     */
    public function test_message_response_priority_is_integer(): void
    {
        $response = new MessageResponse(['priority' => 3]);

        $this->assertIsInt($response->priority());
    }

    /**
     * Test message response creates different datetime instances.
     */
    public function test_message_response_datetime_creates_new_instances(): void
    {
        $response = new MessageResponse(['time' => (string) time()]);

        $datetime1 = $response->dateTime();
        $datetime2 = $response->dateTime();

        $this->assertNotSame($datetime1, $datetime2);
        $this->assertEquals($datetime1, $datetime2);
    }

    /**
     * Test message response handles string time correctly.
     */
    public function test_message_response_handles_string_time(): void
    {
        $timestamp = 1700000000;
        $response = new MessageResponse(['time' => (string) $timestamp]);

        $this->assertEquals($timestamp, $response->time());
        $this->assertInstanceOf(CarbonInterface::class, $response->dateTime());
        $this->assertEquals($timestamp, $response->dateTime()->getTimestamp());
    }

    /**
     * Test message response with extra fields.
     */
    public function test_message_response_with_extra_fields(): void
    {
        $response = new MessageResponse([
            'id' => 'msg-extra',
            'topic' => 'test',
            'custom_field' => 'custom_value',
            'another_field' => 12345,
        ]);

        // Should work fine with extra fields
        $this->assertEquals('msg-extra', $response->id());
        $this->assertEquals('test', $response->topic());

        // Extra fields should be in response data
        $data = $response->getResponseData();
        $this->assertEquals('custom_value', $data['custom_field']);
        $this->assertEquals(12345, $data['another_field']);
    }
}
