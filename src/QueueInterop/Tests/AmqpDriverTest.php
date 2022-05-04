<?php

declare(strict_types=1);

namespace Bernard\Driver\QueueInterop\Tests;

use Bernard\Driver\QueueInterop\Driver;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Prophecy\Prophecy\ObjectProphecy;

final class AmqpDriverTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';

    /**
     * @var AmqpContext|ObjectProphecy
     */
    private $context;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(AmqpContext::class);

        $this->driver = new Driver($this->context->reveal());
    }

    public function testItCreatesAQueue(): void
    {
        $queue = $this->prophesize(AmqpQueue::class);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE)->shouldBeCalled();

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->declareQueue($queue)->shouldBeCalled();

        $this->driver->createQueue(self::QUEUE);
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $queue = $this->prophesize(AmqpQueue::class);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->declareQueue($queue)->willReturn(123);

        $this->assertSame(123, $this->driver->countMessages(self::QUEUE));
    }

    public function testItRemovesAQueue(): void
    {
        $queue = $this->prophesize(AmqpQueue::class);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE)->shouldBeCalled();

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->deleteQueue($queue)->shouldBeCalled();

        $this->driver->removeQueue(self::QUEUE);
    }
}
