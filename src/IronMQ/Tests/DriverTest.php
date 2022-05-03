<?php

namespace Bernard\Driver\IronMQ\Tests;

use Bernard\Driver\IronMQ\Driver;
use IronMQ\IronMQ;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var IronMQ|ObjectProphecy
     */
    private $ironmq;

    /**
     * @var Driver
     */
    private $driver;

    protected function setUp(): void
    {
        $this->ironmq = $this->prophesize(IronMQ::class);

        $this->driver = new Driver($this->ironmq->reveal());
    }

    public function testItIsADriver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueues()
    {
        $this->ironmq->getQueues(0, 100)->willReturn([
            (object) ['name' => 'failed'],
            (object) ['name' => self::QUEUE],
        ]);

        $queues = $this->driver->listQueues();

        $this->assertContains('failed', $queues);
        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItCountsTheNumberOfMessagesInAQueue()
    {
        $this->ironmq->getQueue(self::QUEUE)->willReturn((object) ['size' => 4]);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPushesAMessageToAQueue()
    {
        $this->ironmq->postMessage(self::QUEUE, self::MESSAGE)->shouldBeCalled();

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);
    }

    public function testItPopsMessagesFromAQueue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
        ]);

        $this->assertEquals([self::MESSAGE, 1], $this->driver->popMessage(self::QUEUE));
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn(null);

        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE));
    }

    public function testItPrefetchesMessagesFromAQueue()
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
            (object) ['body' => self::MESSAGE, 'id' => 2],
        ]);

        $this->assertEquals([self::MESSAGE, 1], $this->driver->popMessage(self::QUEUE));
        $this->assertEquals([self::MESSAGE, 2], $this->driver->popMessage(self::QUEUE));
    }

    public function testItAcknowledgesAMessage()
    {
        $this->ironmq->deleteMessage(self::QUEUE, 'receipt')->shouldBeCalled();

        $this->driver->acknowledgeMessage(self::QUEUE, 'receipt');
    }

    public function testItPeeksAQueue()
    {
        $this->ironmq->peekMessages(self::QUEUE, 10)->willReturn([
            (object) ['body' => self::MESSAGE],
        ]);

        $this->assertEquals([self::MESSAGE], $this->driver->peekQueue(self::QUEUE, 10, 10));
    }

    public function testItRemovesAQueue()
    {
        $this->ironmq->deleteQueue(self::QUEUE)->shouldBeCalled();

        $this->driver->removeQueue(self::QUEUE);
    }

    public function testItExposesInfo()
    {
        $driver = new Driver($this->ironmq->reveal(), 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }
}
