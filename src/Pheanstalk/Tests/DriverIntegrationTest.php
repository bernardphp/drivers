<?php

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
    const QUEUE = 'queue';
    const MESSAGE = 'message';

    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->pheanstalk = new Pheanstalk($_ENV['BEANSTALKD_HOST'], $_ENV['BEANSTALKD_PORT']);

        $this->driver = new Driver($this->pheanstalk);
    }

    public function tearDown() : void
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

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $this->pheanstalk->putInTube('list', self::MESSAGE);

        $queues = $this->driver->listQueues();

        $this->assertContains('default', $queues);
        $this->assertContains('list', $queues);
    }

    /**
     * @test
     */
    public function it_counts_the_number_of_messages_in_a_queue()
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);

        $this->assertEquals(4, $this->driver->countMessages(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_pushes_a_message_to_a_queue()
    {
        $this->driver->pushMessage(self::QUEUE, self::MESSAGE);

        $job = $this->pheanstalk->peekReady(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $job->getData());
    }

    /**
     * @test
     */
    public function it_pops_messages_from_a_queue()
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);

        $message = $this->driver->popMessage(self::QUEUE);

        $this->assertEquals(self::MESSAGE, $message[0]);
        $this->assertInstanceOf(Job::class, $message[1]);
        $this->assertEquals(self::MESSAGE, $message[1]->getData());
    }

    /**
     * @test
     */
    public function it_returns_an_empty_message_when_popping_messages_from_an_empty_queue()
    {
        $this->assertEquals([null, null], $this->driver->popMessage(self::QUEUE, 1));
    }

    /**
     * @test
     */
    public function it_acknowledges_a_message()
    {
        $this->pheanstalk->putInTube(self::QUEUE, self::MESSAGE);
        $job = $this->pheanstalk->reserveFromTube(self::QUEUE, 2);

        $this->driver->acknowledgeMessage(self::QUEUE, $job);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage(sprintf('NOT_FOUND: Job %d does not exist.', $job->getId()));

        $this->pheanstalk->peek($job->getId());
    }

    /**
     * @test
     */
    public function it_exposes_info()
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
