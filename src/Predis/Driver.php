<?php

declare(strict_types=1);

namespace Bernard\Driver\Predis;

use Predis\ClientInterface;
use Predis\Command\ServerInfo;

/**
 * Implements a Driver for use with https://github.com/nrk/predis.
 */
final class Driver implements \Bernard\Driver
{
    public const QUEUE_PREFIX = 'queue:';

    private $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues()
    {
        return $this->redis->sMembers('queues');
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName): void
    {
        $this->redis->sAdd('queues', $queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function countMessages($queueName)
    {
        return $this->redis->lLen($this->resolveKey($queueName));
    }

    /**
     * {@inheritdoc}
     */
    public function pushMessage($queueName, $message): void
    {
        $this->redis->rpush($this->resolveKey($queueName), $message);
    }

    /**
     * {@inheritdoc}
     */
    public function popMessage($queueName, $duration = 5)
    {
        [, $message] = $this->redis->blpop($this->resolveKey($queueName), $duration) ?: null;

        return [$message, null];
    }

    /**
     * {@inheritdoc}
     */
    public function peekQueue($queueName, $index = 0, $limit = 20)
    {
        $limit += $index - 1;

        return $this->redis->lRange($this->resolveKey($queueName), $index, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledgeMessage($queueName, $receipt): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function removeQueue($queueName): void
    {
        $this->redis->sRem('queues', $queueName);
        $this->redis->del($this->resolveKey($queueName));
    }

    /**
     * {@inheritdoc}
     */
    public function info()
    {
        // Temporarily change the command use to get info as earlier and newer redis
        // versions breaks it into sections.
        $commandClass = $this->redis->getProfile()->getCommandClass('info');
        $this->redis->getProfile()->defineCommand('info', ServerInfo::class);

        $info = $this->redis->info();

        $this->redis->getProfile()->defineCommand('info', $commandClass);

        return $info;
    }

    /**
     * Transform the queueName into a key.
     *
     * @param string $queueName
     *
     * @return string
     */
    private function resolveKey($queueName)
    {
        return self::QUEUE_PREFIX.$queueName;
    }
}
