<?php

namespace Bernard\Driver\Amqp\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group integration
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    const EXCHANGE = 'exchange';
    const QUEUE = 'queue';
    const MESSAGE = 'message';

    /**
     * @var AMQPStreamConnection
     */
    private $amqp;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var Driver
     */
    private $driver;

    /**
     * Skip cleaning up the queue (eg. cleanup is part of the test).
     *
     * @var bool
     */
    private $skipCleanup = false;

    public function setUp() : void
    {
        $this->skipCleanup = false;

        $this->amqp = new AMQPStreamConnection($_ENV['RABBITMQ_HOST'], $_ENV['RABBITMQ_PORT'], 'guest', 'guest');

        $this->channel = $this->amqp->channel();

        $this->channel->exchange_declare(self::EXCHANGE, 'direct', false, true, false);
        $this->channel->queue_declare(self::QUEUE, false, true, false, false);
        $this->channel->queue_bind(self::QUEUE, self::EXCHANGE, self::QUEUE);

        $this->driver = new Driver($this->amqp, self::EXCHANGE);
    }

    public function tearDown() : void
    {
        if (!$this->channel) {
            $this->channel = $this->amqp->channel();
        }

        if (!$this->skipCleanup) {
            $this->channel->queue_delete(self::QUEUE);
        }

        $this->channel->close();
    }

    /**
     * Publishes a simple test message to the queue.
     *
     * @param string $queue
     * @param string $message
     */
    private function publish($queue = self::QUEUE, $message = self::MESSAGE)
    {
        $this->channel->basic_publish(new AMQPMessage($message), self::EXCHANGE, $queue);
    }

    /**
     * @test
     */
    public function it_creates_a_queue()
    {
        $queue = 'other-queue';

        $this->driver->createQueue($queue);

        $this->publish($queue);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get($queue);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $count = 3;

        for ($i = 0; $i < $count; ++$i) {
            $this->publish();
        }

        // TODO: find out why things are slow on travis
        sleep(1);

        $this->assertEquals($count, $this->driver->countMessages(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_pushes_a_message_to_a_queue()
    {
        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get(self::QUEUE);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
    }

    /**
     * @test
     */
    public function it_pushes_a_message_to_a_queue_with_properties()
    {
        $properties = ['content_type' => 'text'];

        $driver = new Driver($this->amqp, self::EXCHANGE, $properties);

        $driver->pushMessage(self::QUEUE, self::MESSAGE);

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get(self::QUEUE);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
        $this->assertEquals($properties, $message->get_properties());
    }

    /**
     * @test
     */
    public function it_pops_messages_from_a_queue()
    {
        $this->publish();

        // TODO: find out why things are slow on travis
        sleep(1);

        // The queue is always recreated, so the delivery tag is always 1
        $this->assertEquals([self::MESSAGE, '1'], $this->driver->popMessage(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_message_when_popping_messages_from_an_empty_queue()
    {
        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE, 1));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->publish();

        // Publish an extra message
        $this->publish();

        // TODO: find out why things are slow on travis
        sleep(1);

        // Do not ack the message automatically
        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get(self::QUEUE, true);

        $this->assertInstanceOf(AMQPMessage::class, $message);

        $this->driver->acknowledgeMessage(self::QUEUE, $message->delivery_info['delivery_tag']);

        // One message remained in the queue
        $result = $this->channel->queue_purge(self::QUEUE);
        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->skipCleanup = true;

        $this->driver->removeQueue(self::QUEUE);

        $this->publish();

        $this->expectException(AMQPProtocolException::class);
        $this->expectExceptionMessage(sprintf("NOT_FOUND - no queue '%s' in vhost '/'", self::QUEUE));

        $this->channel->basic_get(self::QUEUE);
    }
}
