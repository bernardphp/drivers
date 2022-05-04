<?php

declare(strict_types=1);

namespace Bernard\Driver\Amqp\Tests;

use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group integration
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public const EXCHANGE = 'exchange';
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    private AMQPStreamConnection $connection;

    private AMQPChannel $channel;

    private Driver $driver;

    /**
     * Skip cleaning up the queue (eg. cleanup is part of the test).
     */
    private bool $skipCleanup = false;

    protected function setUp(): void
    {
        $this->connection = new AMQPStreamConnection($_ENV['RABBITMQ_HOST'], $_ENV['RABBITMQ_PORT'], 'guest', 'guest');

        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare(self::EXCHANGE, 'direct', false, true, false);
        $this->channel->queue_declare(self::QUEUE, false, true, false, false);
        $this->channel->queue_bind(self::QUEUE, self::EXCHANGE, self::QUEUE);

        $this->driver = new Driver($this->connection, self::EXCHANGE);
    }

    protected function tearDown(): void
    {
        if (!$this->channel) {
            $this->channel = $this->amqp->channel();
        }

        if (!$this->skipCleanup) {
            $this->channel->queue_delete(self::QUEUE);
        }

        $this->channel->close();

        $this->connection->close();
    }

    /**
     * Publishes a simple test message to the queue.
     */
    private function publish(string $queue = self::QUEUE, string $message = self::MESSAGE): void
    {
        $this->channel->basic_publish(new AMQPMessage($message), self::EXCHANGE, $queue);
    }

    public function testItCreatesAQueue(): void
    {
        $queue = 'other-queue';

        $this->driver->createQueue($queue);

        $this->publish($queue);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get($queue);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
    }

    public function testItRemovesAQueue(): void
    {
        $this->skipCleanup = true;

        $this->driver->removeQueue(self::QUEUE);

        $this->publish();

        $this->expectException(AMQPProtocolException::class);
        $this->expectExceptionMessage(sprintf("NOT_FOUND - no queue '%s' in vhost '/'", self::QUEUE));

        $this->channel->basic_get(self::QUEUE);
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get(self::QUEUE);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
    }

    public function testItPushesAMessageToAQueueWithProperties(): void
    {
        $properties = ['content_type' => 'text'];

        $driver = new Driver($this->connection, self::EXCHANGE, $properties);

        $driver->pushMessage(self::QUEUE, self::MESSAGE);

        // TODO: find out why things are slow on travis
        sleep(1);

        /** @var AMQPMessage $message */
        $message = $this->channel->basic_get(self::QUEUE);

        $this->assertInstanceOf(AMQPMessage::class, $message);
        $this->assertEquals(self::MESSAGE, $message->body);
        $this->assertEquals($properties, $message->get_properties());
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $this->publish();

        // TODO: find out why things are slow on travis
        sleep(1);

        $message = $this->driver->popMessage(self::QUEUE);

        // The queue is always recreated, so the delivery tag is always 1
        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals('1', $message->receipt);
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $this->assertNull($this->driver->popMessage(self::QUEUE, 1));
    }

    public function testItAcknowledgesAMessage(): void
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

        $this->driver->acknowledgeMessage(self::QUEUE, $message->getDeliveryTag());

        // One message remained in the queue
        $result = $this->channel->queue_purge(self::QUEUE);
        $this->assertEquals(1, $result);
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $count = 3;

        for ($i = 0; $i < $count; ++$i) {
            $this->publish();
        }

        // TODO: find out why things are slow on travis
        sleep(1);

        $this->assertEquals($count, $this->driver->countMessages(self::QUEUE));
    }
}
