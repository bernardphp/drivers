<?php

declare(strict_types=1);

namespace Bernard\Driver\Predis;

use Bernard\Driver\Message;
use Predis\ClientInterface;
use Predis\Command\ServerInfo;

final class Driver implements \Bernard\Driver
{
    public const QUEUE_PREFIX = 'queue:';

    public function __construct(private ClientInterface $redis)
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
        $this->redis->rpush($this->resolveKey($queueName), $message);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        [, $message] = $this->redis->blpop($this->resolveKey($queueName), $duration) ?: null;

        return new Message($message, null);
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
    }

    public function info(): array
    {
        // Temporarily change the command use to get info as earlier and newer redis
        // versions breaks it into sections.
        $commandClass = $this->redis->getProfile()->getCommandClass('info');
        $this->redis->getProfile()->defineCommand('info', ServerInfo::class);

        $info = $this->redis->info();

        $this->redis->getProfile()->defineCommand('info', $commandClass);

        return $info;
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
