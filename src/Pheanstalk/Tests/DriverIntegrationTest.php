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

    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @var Driver
     */
    private $driver;

    protected function setUp(): void
    {
        $this->pheanstalk = new Pheanstalk($_ENV['BEANSTALKD_HOST'], $_ENV['BEANSTALKD_PORT']);

        $this->driver = new Driver($this->pheanstalk);
    }

    protected function tearDown(): void
    {
        $tubes = $this->pheanstalk->listTubes();

        foreach ($tubes as $tube) {
            while (true) {
                try {
                    $next = $this->pheanstalk->peekReady($tube);
                } catch (\Exception $e) {
                    break;
                }

                $this->pheanstalk->delete($next);
            }
        }
    }

    public function testItListsQueues(): void
    {
        $this->pheanstalk->putInTube('list', self::MESSAGE);

        $queues = $this->driver->listQueues();

        $this->assertContains('default', $queues);
        $this->assertContains('list', $queues);
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        $job = $this->pheanstalk->peekReady(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $job->getData());
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);

        $message = $this->driver->popMessage(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $message[0]);
        $this->assertInstanceOf(Job::class, $message[1]);
        $this->assertEquals(self::MESSAGE, $message[1]->getData());
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE, 1));
    }

    public function testItAcknowledgesAMessage(): void
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $job = $this->pheanstalk->reserveFromTube(self::QUEUE, 2);

        $this->driver->acknowledgeMessage(self::QUEUE, $job);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(sprintf('NOT_FOUND: Job %d does not exist.', $job->getId()));

        $this->pheanstalk->peek($job->getId());
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
}
