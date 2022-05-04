<?php

declare(strict_types=1);

namespace Bernard\Driver\Amqp;

use Bernard\Driver\Message;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class Driver implements \Bernard\Driver
{
    private ?AMQPChannel $channel = null;

    public function __construct(
        private AbstractConnection $connection,
        private string $exchange,
        private array $defaultMessageProperties = [],
    ) {
    }

    public function listQueues(): array
    {
        return [];
    }

    public function createQueue(string $queueName): void
    {
        $channel = $this->getChannel();

        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $this->exchange, $queueName);
    }

    public function removeQueue(string $queueName): void
    {
        $this->getChannel()->queue_delete($queueName);
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $amqpMessage = new AMQPMessage($message, $this->defaultMessageProperties);

        $this->getChannel()->basic_publish($amqpMessage, $this->exchange, $queueName);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        $runtime = microtime(true) + $duration;

        while (microtime(true) < $runtime) {
            $message = $this->getChannel()->basic_get($queueName);

            if ($message) {
                return new Message($message->body, $message->getDeliveryTag());
            }

            // sleep for 10 ms to prevent hammering CPU
            usleep(10000);
        }

        return null;
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
        $this->getChannel()->basic_ack($receipt);
    }

    public function __destruct()
    {
        if ($this->channel !== null) {
            $this->channel->close();
        }
    }

    public function info(): array
    {
        return [];
    }

    public function countMessages(string $queueName): int
    {
        [, $messageCount] = $this->getChannel()->queue_declare($queueName, true);

        return $messageCount;
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        return [];
    }

    /**
     * Creates a channel or returns an already created one.
     */
    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }
}
