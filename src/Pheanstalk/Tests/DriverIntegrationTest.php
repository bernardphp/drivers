<?php

declare(strict_types=1);

namespace Bernard\Driver\Pheanstalk\Tests;

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

/**
 * @group integration
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    private Pheanstalk $pheanstalk;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->pheanstalk = Pheanstalk::create($_ENV['BEANSTALKD_HOST'], (int) $_ENV['BEANSTALKD_PORT']);

        $this->driver = new Driver($this->pheanstalk);
    }

    protected function tearDown(): void
    {
        $tubes = $this->pheanstalk->listTubes();

        foreach ($tubes as $tube) {
            while (true) {
                try {
                    $next = $this->pheanstalk->peekReady($tube);
                } catch (\Exception) {
                    break;
                }

                if ($next === null) {
                    break;
                }

                $this->pheanstalk->delete($next);
            }
        }
    }

    public function testItListsQueues(): void
    {
        $this->pheanstalk->useTube('list')->put(self::MESSAGE);

        $queues = $this->driver->listQueues();

        $this->assertContains('default', $queues);
        $this->assertContains('list', $queues);
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        $job = $this->pheanstalk->peekReady(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $job->getData());
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);

        $message = $this->driver->popMessage(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertInstanceOf(Job::class, $message->receipt);
        $this->assertEquals(self::MESSAGE, $message->receipt->getData());
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $this->assertNull($this->driver->popMessage(self::QUEUE, 1));
    }

    public function testItAcknowledgesAMessage(): void
    {
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);
        $job = $this->pheanstalk->watchOnly(self::QUEUE)->reserveWithTimeout(2);

        $this->driver->acknowledgeMessage(self::QUEUE, $job);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(sprintf('NOT_FOUND: Job %d does not exist.', $job->getId()));

        $this->pheanstalk->peek($job);
    }

    public function testItExposesInfo(): void
    {
        $info = $this->driver->info();

        // Some known pheanstalk metrics
        $this->assertArrayHasKey('current-jobs-urgent', $info);
        $this->assertArrayHasKey('current-jobs-ready', $info);
        $this->assertArrayHasKey('current-jobs-reserved', $info);
        $this->assertArrayHasKey('current-jobs-delayed', $info);
        $this->assertArrayHasKey('current-jobs-buried', $info);
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);
        $this->pheanstalk->useTube(self::QUEUE)->put(self::MESSAGE);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }
}
