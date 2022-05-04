<?php

declare(strict_types=1);

namespace Bernard\Driver\Sqs;

use Aws\Sqs\Exception\SqsException;
use Aws\Sqs\SqsClient;
use Bernard\Driver\AbstractPrefetchDriver;
use Bernard\Driver\Message;

/**
 * Implements a Driver for use with AWS SQS client API: https://aws.amazon.com/sqs/.
 */
final class Driver extends AbstractPrefetchDriver
{
    public const AWS_SQS_FIFO_SUFFIX = '.fifo';
    public const AWS_SQS_EXCEPTION_BAD_REQUEST = 400;
    public const AWS_SQS_EXCEPTION_NOT_FOUND = 404;

    public function __construct(private SqsClient $sqs, private array $queueUrls = [], ?int $prefetch = null)
    {
        parent::__construct($prefetch);
    }

    public function listQueues(): array
    {
        $result = $this->sqs->listQueues();

        // TODO: drop this as it can easily get inconsistent?
        if (!$queueUrls = $result->get('QueueUrls')) {
            return array_keys($this->queueUrls);
        }

        foreach ($queueUrls as $queueUrl) {
            if (\in_array($queueUrl, $this->queueUrls)) {
                continue;
            }

            $queueName = current(array_reverse(explode('/', $queueUrl)));
            $this->queueUrls[$queueName] = $queueUrl;
        }

        return array_keys($this->queueUrls);
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#createqueue
     *
     * @throws SqsException
     */
    public function createQueue(string $queueName): void
    {
        if ($this->queueExists($queueName)) {
            return;
        }

        $parameters = [
            'QueueName' => $queueName,
        ];

        if ($this->isFifoQueue($queueName)) {
            $parameters['Attributes'] = [
                'FifoQueue' => 'true',
            ];
        }

        $result = $this->sqs->createQueue($parameters);

        $this->queueUrls[$queueName] = $result['QueueUrl'];
    }

    /**
     * @throws SqsException
     */
    private function queueExists(string $queueName): bool
    {
        try {
            $this->resolveUrl($queueName);

            return true;
        } catch (\InvalidArgumentException $exception) {
            return false;
        } catch (SqsException $exception) {
            if ($previousException = $exception->getPrevious()) {
                switch ($previousException->getCode()) {
                    case self::AWS_SQS_EXCEPTION_BAD_REQUEST:
                    case self::AWS_SQS_EXCEPTION_NOT_FOUND:
                        return false;
                }
            }

            throw $exception;
        }
    }

    private function isFifoQueue(string $queueName): bool
    {
        return $this->endsWith($queueName, self::AWS_SQS_FIFO_SUFFIX);
    }

    private function endsWith(string $haystack, string $needle): bool
    {
        $length = \strlen($needle);
        if ($length === 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }

    /**
     * @see http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#deletequeue
     */
    public function removeQueue(string $queueName): void
    {
        $queueUrl = $this->resolveUrl($queueName);

        $this->sqs->deleteQueue([
            'QueueUrl' => $queueUrl,
        ]);
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $queueUrl = $this->resolveUrl($queueName);

        $parameters = [
            'QueueUrl' => $queueUrl,
            'MessageBody' => $message,
        ];

        if ($this->isFifoQueue($queueName)) {
            $parameters['MessageGroupId'] = __METHOD__;
            $parameters['MessageDeduplicationId'] = md5($message);
        }

        $this->sqs->sendMessage($parameters);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        if ($message = $this->cache->pop($queueName)) {
            return $message;
        }

        $queueUrl = $this->resolveUrl($queueName);

        $result = $this->sqs->receiveMessage([
            'QueueUrl' => $queueUrl,
            'MaxNumberOfMessages' => $this->prefetch,
            'WaitTimeSeconds' => $duration,
        ]);

        if (!$result || !$messages = $result->get('Messages')) {
            return null;
        }

        foreach ($messages as $message) {
            $this->cache->push($queueName, new Message($message['Body'], $message['ReceiptHandle']));
        }

        return $this->cache->pop($queueName);
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
        $queueUrl = $this->resolveUrl($queueName);

        $this->sqs->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receipt,
        ]);
    }

    public function info(): array
    {
        return [
            'prefetch' => $this->prefetch,
        ];
    }

    public function countMessages(string $queueName): int
    {
        $queueUrl = $this->resolveUrl($queueName);

        $result = $this->sqs->getQueueAttributes([
            'QueueUrl' => $queueUrl,
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        if (isset($result['Attributes']['ApproximateNumberOfMessages'])) {
            return (int) $result['Attributes']['ApproximateNumberOfMessages'];
        }

        return 0;
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        return [];
    }

    /**
     * AWS works with queue URLs rather than queue names. Returns either queue URL (if queue exists) for given name or null if not.
     *
     * @throws SqsException
     */
    private function resolveUrl(string $queueName): mixed
    {
        if (isset($this->queueUrls[$queueName])) {
            return $this->queueUrls[$queueName];
        }

        $result = $this->sqs->getQueueUrl(['QueueName' => $queueName]);

        if ($result && $queueUrl = $result->get('QueueUrl')) {
            return $this->queueUrls[$queueName] = $queueUrl;
        }

        throw new \InvalidArgumentException('Queue "'.$queueName.'" cannot be resolved to an url.');
    }
}
