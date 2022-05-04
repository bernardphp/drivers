<?php

declare(strict_types=1);

namespace Bernard\Driver\Redis;

use Bernard\Driver\Message;

final class Driver implements \Bernard\Driver
{
    public const QUEUE_PREFIX = 'queue:';

    public function __construct(private \Redis $redis)
    {
    }

    public function listQueues(): array
    {
        return $this->redis->sMembers('queues');
    }

    public function createQueue(string $queueName): void
    {
        $this->redis->sAdd('queues', $queueName);
    }

    public function removeQueue(string $queueName): void
    {
        $this->redis->sRem('queues', $queueName);
        $this->redis->del($this->resolveKey($queueName));
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $this->redis->rPush($this->resolveKey($queueName), $message);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        // When PhpRedis is set up with an Redis::OPT_PREFIX
        // it does set the prefix to the key and to the timeout value something like:
        // "BLPOP" "bernard:queue:my-queue" "bernard:5"
        //
        // To set the resolved key in an array seems fixing this issue. We get:
        // "BLPOP" "bernard:queue:my-queue" "5"
        //
        // see https://github.com/nicolasff/phpredis/issues/158
        [, $message] = $this->redis->blPop([$this->resolveKey($queueName)], $duration) ?: null;

        return new Message($message);
    }

    public function acknowledgeMessage($queueName, $receipt): void
    {
    }

    public function info(): array
    {
        return $this->redis->info();
    }

    public function countMessages(string $queueName): int
    {
        return $this->redis->lLen($this->resolveKey($queueName));
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        $limit += $index - 1;

        return $this->redis->lRange($this->resolveKey($queueName), $index, $limit);
    }

    /**
     * Transform the queueName into a key.
     */
    private function resolveKey(string $queueName): string
    {
        return self::QUEUE_PREFIX.$queueName;
    }
}
