<?php

namespace Bernard\Driver\Redis\Tests;

use Bernard\Driver\Redis\Driver;

/**
 * @group    integration
 * @requires extension redis
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var Driver
     */
    private $driver;

    protected function setUp(): void
    {
        $this->redis = new \Redis();
        $this->redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        $this->redis->setOption(\Redis::OPT_PREFIX, 'bernard:');

        $this->driver = new Driver($this->redis);
    }

    protected function tearDown(): void
    {
        $queues = $this->redis->sMembers('queues');

        foreach ($queues as $queue) {
            $this->redis->del('queue:'.$queue);
        }

        $this->redis->del('queues');
    }

    public function testItListsQueues()
    {
        $queues = [
            'failed',
            self::QUEUE,
        ];

        foreach ($queues as $queue) {
            $this->redis->sAdd('queues', $queue);
        }

        $queues = $this->driver->listQueues();

        $this->assertContains('failed', $queues);
        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItCreatesAQueue()
    {
        $this->driver->createQueue(self::QUEUE);

        $queues = $this->redis->sMembers('queues');

        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItCountsTheNumberOfMessagesInAQueue()
    {
        $this->redis->sAdd('queues', self::QUEUE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPushesAMessageToAQueue()
    {
        $this->redis->sAdd('queues', self::QUEUE);

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        $message = $this->redis->blPop(['queue:'.self::QUEUE], 5);

        $this->assertEquals(['bernard:queue:'.self::QUEUE, self::MESSAGE], $message);
    }

    public function testItPopsMessagesFromAQueue()
    {
        $this->redis->sAdd('queues', self::QUEUE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);

        $this->assertEquals([self::MESSAGE, null], $this->driver->popMessage(self::QUEUE));
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue()
    {
        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE, 1));
    }

    public function testItPeeksAQueue()
    {
        $this->redis->sAdd('queues', self::QUEUE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE.'1');
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE.'2');
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE.'3');
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE.'4');
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE.'5');

        $this->assertEquals([self::MESSAGE.'5'], $this->driver->peekQueue(self::QUEUE, 4, 10));
        $this->assertEquals(
            [
                self::MESSAGE.'1',
                self::MESSAGE.'2',
                self::MESSAGE.'3',
                self::MESSAGE.'4',
                self::MESSAGE.'5',
            ],
            $this->driver->peekQueue(self::QUEUE)
        );
    }

    public function testItRemovesAQueue()
    {
        $this->redis->sAdd('queues', self::QUEUE);
        $this->redis->rPush('queue:'.self::QUEUE, self::MESSAGE);

        $this->driver->removeQueue(self::QUEUE);

        $this->assertFalse($this->redis->get('queue:'.self::QUEUE));
        $this->assertNotContains(self::QUEUE, $this->redis->sMembers('queues'));
    }
}
