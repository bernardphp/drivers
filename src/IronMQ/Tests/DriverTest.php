<?php

declare(strict_types=1);

namespace Bernard\Driver\IronMQ\Tests;

use Bernard\Driver\IronMQ\Driver;
use IronMQ\IronMQ;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var IronMQ|ObjectProphecy
     */
    private $ironmq;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->ironmq = $this->prophesize(IronMQ::class);

        $this->driver = new Driver($this->ironmq->reveal());
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueues(): void
    {
        $this->ironmq->getQueues(0, 100)->willReturn([
            (object) ['name' => 'failed'],
            (object) ['name' => self::QUEUE],
        ]);

        $queues = $this->driver->listQueues();

        $this->assertContains('failed', $queues);
        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItRemovesAQueue(): void
    {
        $this->ironmq->deleteQueue(self::QUEUE)->shouldBeCalled();

        $this->driver->removeQueue(self::QUEUE);
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $this->ironmq->postMessage(self::QUEUE, self::MESSAGE)->shouldBeCalled();

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
        ]);

        $message = $this->driver->popMessage(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals(1, $message->receipt);
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn(null);

        $this->assertNull($this->driver->popMessage(self::QUEUE));
    }

    public function testItPrefetchesMessagesFromAQueue(): void
    {
        $this->ironmq->reserveMessages(self::QUEUE, 2, IronMQ::GET_MESSAGE_TIMEOUT, 5)->willReturn([
            (object) ['body' => self::MESSAGE, 'id' => 1],
            (object) ['body' => self::MESSAGE, 'id' => 2],
        ]);

        $message = $this->driver->popMessage(self::QUEUE);
        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals(1, $message->receipt);

        $message = $this->driver->popMessage(self::QUEUE);
        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals(2, $message->receipt);
    }

    public function testItAcknowledgesAMessage(): void
    {
        $this->ironmq->deleteMessage(self::QUEUE, 'receipt')->shouldBeCalled();

        $this->driver->acknowledgeMessage(self::QUEUE, 'receipt');
    }

    public function testItExposesInfo(): void
    {
        $driver = new Driver($this->ironmq->reveal(), 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->ironmq->getQueue(self::QUEUE)->willReturn((object) ['size' => 4]);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPeeksAQueue(): void
    {
        $this->ironmq->peekMessages(self::QUEUE, 10)->willReturn([
            (object) ['body' => self::MESSAGE],
        ]);

        $this->assertEquals([self::MESSAGE], $this->driver->peekQueue(self::QUEUE, 10, 10));
    }
}
