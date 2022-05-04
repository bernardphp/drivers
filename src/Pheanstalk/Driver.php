<?php

declare(strict_types=1);

namespace Bernard\Driver\Pheanstalk;

use Bernard\Driver\Message;
use Pheanstalk\Contract\PheanstalkInterface;

final class Driver implements \Bernard\Driver
{
    public function __construct(private PheanstalkInterface $pheanstalk)
    {
    }

    public function listQueues(): array
    {
        return $this->pheanstalk->listTubes();
    }

    public function createQueue(string $queueName): void
    {
    }

    public function removeQueue(string $queueName): void
    {
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $this->pheanstalk->useTube($queueName)->put($message);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        if ($job = $this->pheanstalk->watchOnly($queueName)->reserveWithTimeout($duration)) {
            return new Message($job->getData(), $job);
        }

        return null;
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
        $this->pheanstalk->delete($receipt);
    }

    public function info(): array
    {
        return iterator_to_array($this->pheanstalk->stats());
    }

    public function countMessages(string $queueName): int
    {
        $stats = $this->pheanstalk->statsTube($queueName);

        return (int) $stats['current-jobs-ready'];
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        return [];
    }
}
