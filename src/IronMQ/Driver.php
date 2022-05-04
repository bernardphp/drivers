<?php

declare(strict_types=1);

namespace Bernard\Driver\IronMQ;

use Bernard\Driver\AbstractPrefetchDriver;
use Bernard\Driver\Message;
use IronMQ\IronMQ;

final class Driver extends AbstractPrefetchDriver
{
    public function __construct(private IronMQ $ironmq, ?int $prefetch = null)
    {
        parent::__construct($prefetch);
    }

    public function listQueues(): array
    {
        $queueNames = [];
        $page = 0;

        while ($queues = $this->ironmq->getQueues($page, 100)) {
            $queueNames += $this->pluck($queues, 'name');

            // If we get 100 results the probability of another page is high.
            if (\count($queues) < 100) {
                break;
            }

            ++$page;
        }

        return $queueNames;
    }

    public function createQueue(string $queueName): void
    {
    }

    public function removeQueue(string $queueName): void
    {
        $this->ironmq->deleteQueue($queueName);
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $this->ironmq->postMessage($queueName, $message);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        if ($message = $this->cache->pop($queueName)) {
            return $message;
        }

        $messages = $this->ironmq->reserveMessages($queueName, $this->prefetch, IronMQ::GET_MESSAGE_TIMEOUT, $duration);

        if (!$messages) {
            return null;
        }

        foreach ($messages as $message) {
            $this->cache->push($queueName, new Message($message->body, $message->id));
        }

        return $this->cache->pop($queueName);
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
        $this->ironmq->deleteMessage($queueName, $receipt);
    }

    public function info(): array
    {
        return [
            'prefetch' => $this->prefetch,
        ];
    }

    public function countMessages(string $queueName): int
    {
        if ($info = $this->ironmq->getQueue($queueName)) {
            return $info->size;
        }

        return 0;
    }

    /**
     * IronMQ does not support an offset when peeking messages.
     */
    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        if ($messages = $this->ironmq->peekMessages($queueName, $limit)) {
            return $this->pluck($messages, 'body');
        }

        return [];
    }

    /**
     * The missing array_pluck but for objects array.
     */
    private function pluck(array $objects, string $property): array
    {
        $function = fn ($object) => $object->$property;

        return array_map($function, $objects);
    }
}
