<?php

namespace Bernard\Driver\IronMQ\Tests;

use Bernard\Driver\IronMQ\Driver;
use IronMQ\IronMQ;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    const QUEUE = 'queue';
    const MESSAGE = 'message';

    /**
     * @var IronMQ|ObjectProphecy
     */
    private $ironmq;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->ironmq = $this->prophesize(IronMQ::class);

        $this->driver = new Driver($this->ironmq->reveal());
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
        $this->ironmq->getQueues(0, 100)->willReturn([
            (object) ['name' => 'failed'],
            (object) ['name' => self::QUEUE],
        ]);

        $queues = $this->driver->listQueues();

        $this->assertContains('failed', $queues);
        $this->assertContains(self::QUEUE, $queues);
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->ironmq->getQueue(self::QUEUE)->willReturn((object) ['size' => 4]);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_pushes_a_message_to_a_queue()
    {
        $this->ironmq->postMessage(self::QUEUE, self::MESSAGE)->shouldBeCalled();

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);
    }

    /**
     * @test
     */
    public function it_pops_messages_from_a_queue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
        ]);

        $this->assertEquals([self::MESSAGE, 1], $this->driver->popMessage(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_message_when_popping_messages_from_an_empty_queue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn(null);

        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_prefetches_messages_from_a_queue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
            (object) ['body' => self::MESSAGE, 'id' => 2],
        ]);

        $this->assertEquals([self::MESSAGE, 1], $this->driver->popMessage(self::QUEUE));
        $this->assertEquals([self::MESSAGE, 2], $this->driver->popMessage(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->ironmq->deleteMessage(self::QUEUE, 'receipt')->shouldBeCalled();

        $this->driver->acknowledgeMessage(self::QUEUE, 'receipt');
    }

    /**
     * @test
     */
    public function it_peeks_a_queue()
    {
        $this->ironmq->peekMessages(self::QUEUE, 10)->willReturn([
            (object) ['body' => self::MESSAGE],
        ]);

        $this->assertEquals([self::MESSAGE], $this->driver->peekQueue(self::QUEUE, 10, 10));
    }

    /**
     * @test
     */
    public function it_removes_a_queue()
    {
        $this->ironmq->deleteQueue(self::QUEUE)->shouldBeCalled();

        $this->driver->removeQueue(self::QUEUE);
    }

    /**
     * @test
     */
    public function it_exposes_info()
    {
        $driver = new Driver($this->ironmq->reveal(), 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }
}
