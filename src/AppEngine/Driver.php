<?php

declare(strict_types=1);

namespace Bernard\Driver\AppEngine;

use Bernard\Driver\Message;
use google\appengine\api\taskqueue\PushTask;

/**
 * Google App Engine driver. Many features are not supported.
 * It takes a list of array('name' => 'endpoint') to route messages to the
 * correct place.
 */
final class Driver implements \Bernard\Driver
{
    public function __construct(private array $queueMap)
    {
    }

    public function listQueues(): array
    {
        return array_flip($this->queueMap);
    }

    public function createQueue(string $queueName): void
    {
    }

    public function removeQueue(string $queueName): void
    {
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $task = new PushTask($this->resolveEndpoint($queueName), compact('message'));
        $task->add($queueName);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        return null;
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
    }

    public function info(): array
    {
        return [];
    }

    public function countMessages(string $queueName): int
    {
        return 0;
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        return [];
    }

    private function resolveEndpoint(string $queueName): string
    {
        if (isset($this->queueMap[$queueName])) {
            return $this->queueMap[$queueName];
        }

        return '/_ah/queue/'.$queueName;
    }
}
