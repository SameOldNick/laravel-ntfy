<?php

namespace SameOldNick\Ntfy\Tests\Unit;

use Ntfy\Message;
use SameOldNick\Ntfy\Services\MessageBuilder;
use SameOldNick\Ntfy\Tests\TestCase;

class MessageBuilderTest extends TestCase
{
    /**
     * Test builder creates message instance.
     */
    public function test_builder_creates_message_instance(): void
    {
        $builder = new MessageBuilder;

        $this->assertInstanceOf(MessageBuilder::class, $builder);
    }

    /**
     * Test static make method creates builder.
     */
    public function test_static_make_creates_builder(): void
    {
        $builder = MessageBuilder::make();

        $this->assertInstanceOf(MessageBuilder::class, $builder);
    }

    /**
     * Test builder sets topic.
     */
    public function test_builder_sets_topic(): void
    {
        $message = MessageBuilder::make()
            ->topic('test-topic')
            ->build();

        $this->assertEquals('test-topic', $message->getData()['topic']);
    }

    /**
     * Test builder sets title.
     */
    public function test_builder_sets_title(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->title('Test Title')
            ->build();

        $this->assertEquals('Test Title', $message->getData()['title']);
    }

    /**
     * Test builder sets body.
     */
    public function test_builder_sets_body(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->body('Test body content')
            ->build();

        $this->assertEquals('Test body content', $message->getData()['message']);
    }

    /**
     * Test builder sets markdown body.
     */
    public function test_builder_sets_markdown_body(): void
    {
        $markdown = '# Title\n**Bold** text';

        $message = MessageBuilder::make()
            ->topic('test')
            ->markdown($markdown)
            ->build();

        $this->assertEquals($markdown, $message->getData()['markdown']);
    }

    /**
     * Test builder sets priority.
     */
    public function test_builder_sets_priority(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->priority(5)
            ->build();

        $this->assertEquals(5, $message->getData()['priority']);
    }

    /**
     * Test builder sets tags.
     */
    public function test_builder_sets_tags(): void
    {
        $tags = ['warning', 'alert'];

        $message = MessageBuilder::make()
            ->topic('test')
            ->tags($tags)
            ->build();

        $this->assertEquals($tags, $message->getData()['tags']);
    }

    /**
     * Test builder sets schedule delay.
     */
    public function test_builder_sets_schedule_delay(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->schedule('10m')
            ->build();

        $this->assertStringContainsString('10m', $message->getData()['delay']);
    }

    /**
     * Test builder sets click URL.
     */
    public function test_builder_sets_click_url(): void
    {
        $url = 'https://example.com/action';

        $message = MessageBuilder::make()
            ->topic('test')
            ->click($url)
            ->build();

        $this->assertEquals($url, $message->getData()['click']);
    }

    /**
     * Test builder sets icon URL.
     */
    public function test_builder_sets_icon_url(): void
    {
        $iconUrl = 'https://example.com/icon.png';

        $message = MessageBuilder::make()
            ->topic('test')
            ->icon($iconUrl)
            ->build();

        $this->assertEquals($iconUrl, $message->getData()['icon']);
    }

    /**
     * Test builder sets email forward.
     */
    public function test_builder_sets_email_forward(): void
    {
        $email = 'user@example.com';

        $message = MessageBuilder::make()
            ->topic('test')
            ->email($email)
            ->build();

        $this->assertEquals($email, $message->getData()['email']);
    }

    /**
     * Test builder sets attachment.
     */
    public function test_builder_sets_attachment(): void
    {
        $url = 'https://example.com/file.pdf';
        $name = 'document.pdf';

        $message = MessageBuilder::make()
            ->topic('test')
            ->attach($url, $name)
            ->build();

        $this->assertArrayHasKey('attach', $message->getData());
    }

    /**
     * Test builder disables caching.
     */
    public function test_builder_disables_caching(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->disableCaching()
            ->build();

        $this->assertEquals('no', $message->getData()['cache']);
    }

    /**
     * Test builder disables Firebase.
     */
    public function test_builder_disables_firebase(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->disableFirebase()
            ->build();

        $this->assertEquals('no', $message->getData()['firebase']);
    }

    /**
     * Test builder build returns message instance.
     */
    public function test_builder_build_returns_message(): void
    {
        $message = MessageBuilder::make()
            ->topic('test')
            ->title('Test')
            ->build();

        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * Test builder reset clears message.
     */
    public function test_builder_reset_clears_message(): void
    {
        $builder = MessageBuilder::make()
            ->topic('test')
            ->title('Test')
            ->reset();

        $this->assertInstanceOf(MessageBuilder::class, $builder);

        $message = $builder->topic('new-topic')->build();

        $this->assertEquals('new-topic', $message->getData()['topic']);
        $this->assertFalse(isset($message->getData()['title']));
    }

    /**
     * Test builder chains multiple methods.
     */
    public function test_builder_chains_multiple_methods(): void
    {
        $message = MessageBuilder::make()
            ->topic('alerts')
            ->title('Emergency Alert')
            ->body('System failure detected')
            ->priority(5)
            ->tags(['emergency', 'system'])
            ->click('https://dashboard.local/alerts')
            ->build();

        $data = $message->getData();

        $this->assertEquals('alerts', $data['topic']);
        $this->assertEquals('Emergency Alert', $data['title']);
        $this->assertEquals('System failure detected', $data['message']);
        $this->assertEquals(5, $data['priority']);
        $this->assertEquals(['emergency', 'system'], $data['tags']);
        $this->assertEquals('https://dashboard.local/alerts', $data['click']);
    }

    /**
     * Test builder allows method proxying to underlying message.
     */
    public function test_builder_proxies_methods_to_message(): void
    {
        $builder = MessageBuilder::make();

        // Test that we can call Message methods via the builder
        $result = $builder->topic('test')->build();

        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals('test', $result->getData()['topic']);
    }

    /**
     * Test builder with complex message scenario.
     */
    public function test_builder_complex_message_scenario(): void
    {
        $markdown = '## Report Generated\n\nYour daily report is ready!';
        $message = MessageBuilder::make()
            ->topic('notifications')
            ->title('Task Complete')
            ->markdown($markdown)
            ->priority(3)
            ->tags(['report', 'notification'])
            ->schedule('30m')
            ->click('https://example.local/reports/123')
            ->icon('https://example.local/icon.png')
            ->disableCaching()
            ->build();

        $data = $message->getData();

        $this->assertEquals('notifications', $data['topic']);
        $this->assertEquals('Task Complete', $data['title']);
        // Markdown is stored in the 'markdown' field
        $this->assertArrayHasKey('markdown', $data);
        $this->assertEquals($markdown, $data['markdown']);
        $this->assertEquals(3, $data['priority']);
        $this->assertEquals(['report', 'notification'], $data['tags']);
        $this->assertEquals('no', $data['cache']);
    }

    /**
     * Test builder returns self for fluent interface.
     */
    public function test_builder_returns_self_for_fluent_interface(): void
    {
        $builder = MessageBuilder::make();

        $result = $builder->topic('test');

        $this->assertSame($builder, $result);
    }
}
