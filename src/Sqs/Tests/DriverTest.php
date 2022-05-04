<?php

declare(strict_types=1);

namespace Bernard\Driver\Sqs\Tests;

use Aws\ResultInterface;
use Aws\Sqs\SqsClient;
use Bernard\Driver\Sqs\Driver;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public const QUEUE = 'queue';
    public const URL = 'url';
    public const MESSAGE = 'message';

    /**
     * @var SqsClient|ObjectProphecy
     */
    private $sqs;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->sqs = $this->prophesize(SqsClient::class);

        $this->driver = new Driver($this->sqs->reveal(), [self::QUEUE => self::URL]);
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueuesFromTheInternalCache(): void
    {
        $result = $this->prophesize(ResultInterface::class);
        $result->get('QueueUrls')->willReturn(null);

        $this->sqs->listQueues()->willReturn($result);
        $this->sqs->createQueue(['QueueName' => self::QUEUE]);

        $queues = $this->driver->listQueues();

        $this->assertContains(self::QUEUE, $queues);
    }

    public function testItPrefetchesMessagesFromAQueue(): void
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

        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals('1', $message->receipt);

        $message = $driver->popMessage(self::QUEUE, 10);

        $this->assertEquals(self::MESSAGE, $message->message);
        $this->assertEquals('2', $message->receipt);
    }

    public function testItExposesInfo(): void
    {
        $this->assertEquals(['prefetch' => 2], $this->driver->info());
    }

    public function testItExposesPrefetchInfo(): void
    {
        $driver = new Driver($this->sqs->reveal(), [self::QUEUE => 'url'], 10);

        $this->assertEquals(['prefetch' => 10], $driver->info());
    }

    public function testItPeeksAQueue(): void
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }
}
