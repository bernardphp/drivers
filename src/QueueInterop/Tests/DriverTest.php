<?php

declare(strict_types=1);

namespace Bernard\Driver\QueueInterop\Tests;

use Bernard\Driver\QueueInterop\Driver;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var PsrContext|ObjectProphecy
     */
    private $context;

    /**
     * @var Driver
     */
    private $driver;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(PsrContext::class);

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

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->assertSame(0, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $queue = $this->prophesize(PsrQueue::class);

        $message = $this->prophesize(PsrMessage::class);

        $producer = $this->prophesize(PsrProducer::class);
        $producer->send($queue, $message)->shouldBeCalled();

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createMessage(self::MESSAGE)->willReturn($message);
        $this->context->createProducer()->willReturn($producer);

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $queue = $this->prophesize(PsrQueue::class);

        $message = $this->prophesize(PsrMessage::class);
        $message->getBody()->willReturn(self::MESSAGE);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->receive(6789)->willReturn($message);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->assertSame(
            [self::MESSAGE, $message->reveal()],
            $this->driver->popMessage(self::QUEUE, 6.789)
        );
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $queue = $this->prophesize(PsrQueue::class);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->receive(5000)->willReturn(null);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE));
    }

    public function it_acknowledges_a_message(): void
    {
        $queue = $this->prophesize(PsrQueue::class);

        $message = $this->prophesize(PsrMessage::class);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->acknowledge($message)->willReturn($message);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->driver->acknowledgeMessage(self::QUEUE, $message);
    }

    public function testItPeeksAQueue(): void
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }

    public function testItExposesInfo(): void
    {
        $this->assertEquals([], $this->driver->info());
    }
}
