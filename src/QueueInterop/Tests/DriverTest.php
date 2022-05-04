<?php

declare(strict_types=1);

namespace Bernard\Driver\QueueInterop\Tests;

use Bernard\Driver\QueueInterop\Driver;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var Context|ObjectProphecy
     */
    private $context;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(Context::class);

        $this->driver = new Driver($this->context->reveal());
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueues(): void
    {
        $this->assertEquals([], $this->driver->listQueues());
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $queue = $this->prophesize(Queue::class);

        $message = $this->prophesize(Message::class);

        $producer = $this->prophesize(Producer::class);
        $producer->send($queue, $message)->shouldBeCalled();

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createMessage(self::MESSAGE)->willReturn($message);
        $this->context->createProducer()->willReturn($producer);

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $queue = $this->prophesize(Queue::class);

        $message = $this->prophesize(Message::class);
        $message->getBody()->willReturn(self::MESSAGE);

        $consumer = $this->prophesize(Consumer::class);
        $consumer->receive(6000)->willReturn($message);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $driverMessage = $this->driver->popMessage(self::QUEUE, 6);

        $this->assertSame(self::MESSAGE, $driverMessage->message);
        $this->assertSame($message->reveal(), $driverMessage->receipt);
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $queue = $this->prophesize(Queue::class);

        $consumer = $this->prophesize(Consumer::class);
        $consumer->receive(5000)->willReturn(null);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->assertNull($this->driver->popMessage(self::QUEUE));
    }

    public function testItAcknowledgesAMessage(): void
    {
        $queue = $this->prophesize(Queue::class);

        $message = $this->prophesize(Message::class);

        $consumer = $this->prophesize(Consumer::class);
        $consumer->acknowledge($message)->shouldBeCalled();

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->driver->acknowledgeMessage(self::QUEUE, $message->reveal());
    }

    public function testItExposesInfo(): void
    {
        $this->assertEquals([], $this->driver->info());
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->assertSame(0, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPeeksAQueue(): void
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }
}
