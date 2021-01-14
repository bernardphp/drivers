<?php

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
    const QUEUE = 'queue';
    const MESSAGE = 'message';

    /**
     * @var PsrContext|ObjectProphecy
     */
    private $context;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->context = $this->prophesize(PsrContext::class);

        $this->driver = new Driver($this->context->reveal());
    }

    /**
     * @test
     */
    public function it_is_a_driver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $this->assertEquals([], $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->assertSame(0, $this->driver->countMessages(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_pushes_a_message_to_a_queue()
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

    /**
     * @test
     */
    public function it_pops_messages_from_a_queue()
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

    /**
     * @test
     */
    public function it_returns_an_empty_message_when_popping_messages_from_an_empty_queue()
    {
        $queue = $this->prophesize(PsrQueue::class);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->receive(5000)->willReturn(null);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE));
    }

    public function it_acknowledges_a_message()
    {
        $queue = $this->prophesize(PsrQueue::class);

        $message = $this->prophesize(PsrMessage::class);

        $consumer = $this->prophesize(PsrConsumer::class);
        $consumer->acknowledge($message)->willReturn($message);

        $this->context->createQueue(self::QUEUE)->willReturn($queue);
        $this->context->createConsumer($queue)->willReturn($consumer);

        $this->driver->acknowledgeMessage(self::QUEUE, $message);
    }

    /**
     * @test
     */
    public function it_peeks_a_queue()
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_exposes_info()
    {
        $this->assertEquals([], $this->driver->info());
    }
}
