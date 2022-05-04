<?php

declare(strict_types=1);

namespace Bernard\Driver\Predis\Tests;

use Bernard\Driver\Predis\Driver;
use Predis\Client;

/**
 * @group integration
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    private Client $redis;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->redis = new Client(
            sprintf('tcp://%s:%s', $_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']),
            [
                'prefix' => 'bernard:',
            ]
        );

        $this->driver = new Driver($this->redis);
    }

    protected function tearDown(): void
    {
        $queues = $this->redis->smembers('queues');

        foreach ($queues as $queue) {
            $this->redis->del(['queue:'.$queue]);
        }

        $this->redis->del(['queues']);
    }

    public function testItListsQueues(): void
    {
        $queues = [
            'failed',
            self::QUEUE,
        ];

        foreach ($queues as $queue) {
            $this->redis->sadd('queues', [$queue]);
        }

        $queues = $this->driver->listQueues();

        $this->assertContains('failed', $queues);
        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItCreatesAQueue(): void
    {
        $this->driver->createQueue(self::QUEUE);

        $queues = $this->redis->smembers('queues');

        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItRemovesAQueue(): void
    {
        $this->redis->sadd('queues', [self::QUEUE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);

        $this->driver->removeQueue(self::QUEUE);

        $this->assertNull($this->redis->get('queue:'.self::QUEUE));
        $this->assertNotContains(self::QUEUE, $this->redis->smembers('queues'));
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $this->redis->sadd('queues', [self::QUEUE]);

        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        $message = $this->redis->blPop(['queue:'.self::QUEUE], 5);

        $this->assertEquals(['bernard:queue:'.self::QUEUE, self::MESSAGE], $message);
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $this->redis->sadd('queues', [self::QUEUE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);

        $message = $this->driver->popMessage(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertNull($message->receipt);
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $this->assertNull($this->driver->popMessage(self::QUEUE, 1));
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->redis->sadd('queues', [self::QUEUE]);

        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE]);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPeeksAQueue(): void
    {
        $this->redis->sadd('queues', [self::QUEUE]);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE.'1']);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE.'2']);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE.'3']);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE.'4']);
        $this->redis->rpush('queue:'.self::QUEUE, [self::MESSAGE.'5']);

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
}
