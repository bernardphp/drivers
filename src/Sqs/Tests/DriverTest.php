<?php

namespace Bernard\Driver\Sqs\Tests;

use Aws\ResultInterface;
use Aws\Sqs\SqsClient;
use Bernard\Driver\Sqs\Driver;
use Prophecy\Prophecy\ObjectProphecy;

class DriverTest extends \PHPUnit\Framework\TestCase
{
    public const QUEUE = 'queue';
    public const URL = 'url';
    public const MESSAGE = 'message';

    /**
     * @var SqsClient|ObjectProphecy
     */
    private $sqs;

    /**
     * @var Driver
     */
    private $driver;

    protected function setUp(): void
    {
        $this->sqs = $this->prophesize(SqsClient::class);

        $this->driver = new Driver($this->sqs->reveal(), [self::QUEUE => self::URL]);
    }

    public function testItIsADriver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueuesFromTheInternalCache()
    {
        $result = $this->prophesize(ResultInterface::class);
        $result->get('QueueUrls')->willReturn(null);

        $this->sqs->listQueues()->willReturn($result);
        $this->sqs->createQueue(['QueueName' => self::QUEUE]);

        $queues = $this->driver->listQueues();

        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItPrefetchesMessagesFromAQueue()
    {
        $result = $this->prophesize(ResultInterface::class);
        $result->get('Messages')->willReturn([
            ['Body' => self::MESSAGE, 'ReceiptHandle' => '1'],
            ['Body' => self::MESSAGE, 'ReceiptHandle' => '2'],
        ]);

        $this->sqs->receiveMessage([
            'QueueUrl' => self::URL,
            'MaxNumberOfMessages' => 10,
            'WaitTimeSeconds' => 5,
        ])->willReturn($result);

        $driver = new Driver($this->sqs->reveal(), [self::QUEUE => self::URL], 10);

        $message = $driver->popMessage(self::QUEUE);

        $this->assertEquals([self::MESSAGE, '1'], $message);

        $message = $driver->popMessage(self::QUEUE, 10);

        $this->assertEquals([self::MESSAGE, '2'], $message);
    }

    public function testItPeeksAQueue()
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }

    public function testItExposesInfo()
    {
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }

    public function testItExposesPrefetchInfo()
    {
        $driver = new Driver($this->sqs->reveal(), [self::QUEUE => 'url'], 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
    }
}
