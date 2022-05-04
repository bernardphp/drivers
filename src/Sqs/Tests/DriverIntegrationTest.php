<?php

declare(strict_types=1);

namespace Bernard\Driver\Sqs\Tests;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Bernard\Driver\Sqs\Driver;

/**
 * @group integration
 */
final class DriverIntegrationTest extends \PHPUnit\Framework\TestCase
{
    public const MESSAGE = 'message';

    private SqsClient $sqs;

    private Driver $driver;

    /**
     * List of queues to clean up after running the test suite.
     */
    private array $queues = [];

    protected function setUp(): void
    {
        $accessKey = getenv('SQS_ACCESS_KEY');
        $secretKey = getenv('SQS_SECRET_KEY');
        $region = getenv('SQS_REGION');

        if (empty($accessKey) || empty($secretKey) || empty($region)) {
            $this->markTestSkipped('Missing SQS credentials');
        }

        $this->sqs = new SqsClient([
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'region' => $region,
            'version' => '2012-11-05',
        ]);

        $this->driver = new Driver($this->sqs);
    }

    /**
     * Creates a queue name for a topic, also saves the queue name in the local cleanup queue.
     * It is necessary to use less deterministic names, because Amazon limits how queue names can be reused.
     * (Have to wait 60 seconds after a queue is deleted).
     */
    private function queueName(string $topic): string
    {
        // PHP version is added so tests can run in parallel in CI
        return $this->queues[] = sprintf('bernard_%s_%d_%s', $topic, \PHP_VERSION_ID, uniqid());
    }

    private function createQueue(string $topic): array
    {
        $queueName = $this->queueName($topic);

        try {
            $result = $this->sqs->createQueue(['QueueName' => $queueName]);
        } catch (SqsException $e) {
            if ($e->getAwsErrorCode() == 'AWS.SimpleQueueService.QueueDeletedRecently') {
                $this->fail('Test executed too soon! Please try again later (with at least 60s delay)!');
            }

            throw $e;
        }

        return [$queueName, $result['QueueUrl']];
    }

    protected function tearDown(): void
    {
        foreach ($this->queues as $queue) {
            $result = $this->sqs->getQueueUrl(['QueueName' => $queue]);

            if ($result) {
                $this->sqs->deleteQueue(['QueueUrl' => $result->get('QueueUrl')]);
            }
        }
    }

    public function testItListsQueues(): void
    {
        $queue = $this->createQueue('list');

        $retries = 0;
        $retryLimit = 60;

        // Wait for the queue to be created
        while ($retries < $retryLimit) {
            $queues = $this->driver->listQueues();

            if (\in_array($queue[0], $queues, true)) {
                break;
            }

            ++$retries;

            sleep(1);
        }

        $this->assertLessThan($retryLimit, $retries, 'Failed asserting queue creation within the retry limit');
    }

    public function testItCreatesAQueue(): void
    {
        $queue = $this->queueName('create');

        $this->driver->createQueue($queue);

        $result = $this->sqs->getQueueUrl(['QueueName' => $queue]);
        $this->assertContains($queue, $result->get('QueueUrl'));
    }

    public function testItRemovesAQueue(): void
    {
        $queue = $this->createQueue('remove');
        $this->queues = [];

        $this->driver->removeQueue($queue[0]);

        try {
            $this->sqs->getQueueUrl(['QueueName' => $queue[0]]);
        } catch (SqsException $e) {
            $this->assertEquals($e->getAwsErrorCode(), 'AWS.SimpleQueueService.NonExistentQueue');
        }
    }

    public function testItPushesAMessageToAQueue(): void
    {
        $queue = $this->createQueue('push');

        $this->driver->pushMessage($queue[0], self::MESSAGE);

        $result = $this->sqs->receiveMessage([
            'QueueUrl' => $queue[1],
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => 2,
        ]);

        $messages = $result->get('Messages');

        $this->assertCount(1, $messages);
        $this->assertEquals(self::MESSAGE, $messages[0]['Body']);
    }

    public function testItPopsMessagesFromAQueue(): void
    {
        $queue = $this->createQueue('pop');

        $this->sqs->sendMessage([
            'QueueUrl' => $queue[1],
            'MessageBody' => self::MESSAGE,
        ]);

        $message = $this->driver->popMessage($queue[0], 2);

        $this->assertEquals(self::MESSAGE, $message[0]);
        $this->assertNotEmpty($message[1]);
    }

    public function testItReturnsAnEmptyMessageWhenPoppingMessagesFromAnEmptyQueue(): void
    {
        $queue = $this->createQueue('pop_empty');

        $this->assertEquals([null, null], $this->driver->popMessage($queue[0], 1));
    }

    public function testItAcknowledgesAMessage(): void
    {
        $queue = $this->createQueue('ack');

        $this->sqs->sendMessage([
            'QueueUrl' => $queue[1],
            'MessageBody' => self::MESSAGE,
        ]);

        $result = $this->sqs->receiveMessage([
            'QueueUrl' => $queue[1],
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds' => 5,
        ]);

        $messages = $result->get('Messages');

        $this->assertCount(1, $messages);

        $this->driver->acknowledgeMessage($queue[0], $messages[0]['ReceiptHandle']);

        $result = $this->sqs->getQueueAttributes([
            'QueueUrl' => $queue[1],
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $this->assertEquals(0, $result['Attributes']['ApproximateNumberOfMessages']);
    }

    public function testItCountsTheNumberOfMessagesInAQueue(): void
    {
        $queue = $this->createQueue('count');

        $this->sqs->sendMessageBatch([
            'QueueUrl' => $queue[1],
            'Entries' => [
                [
                    'Id' => '1',
                    'MessageBody' => self::MESSAGE,
                ],
                [
                    'Id' => '2',
                    'MessageBody' => self::MESSAGE,
                ],
            ],
        ]);

        $this->assertEquals(2, $this->driver->countMessages($queue[0]));
    }
}
