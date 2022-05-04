<?php

declare(strict_types=1);

namespace Bernard\Driver\QueueInterop;

use Bernard\Driver\Message;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;

final class Driver implements \Bernard\Driver
{
    /**
     * @var PsrConsumer[]
     */
    private array $consumers = [];

    public function __construct(private PsrContext $context)
    {
    }

    public function listQueues(): array
    {
        return [];
    }

    public function createQueue(string $queueName): void
    {
        if ($this->context instanceof AmqpContext) {
            $this->context->declareQueue($this->createAmqpQueue($queueName));
        }
    }

    public function removeQueue(string $queueName): void
    {
        if ($this->context instanceof AmqpContext) {
            $queue = $this->createAmqpQueue($queueName);

            $this->context->deleteQueue($queue);
        }
    }

    public function pushMessage(string $queueName, string $message): void
    {
        $queue = $this->context->createQueue($queueName);
        $message = $this->context->createMessage($message);

        $this->context->createProducer()->send($queue, $message);
    }

    public function popMessage(string $queueName, int $duration = 5): ?Message
    {
        if ($message = $this->getQueueConsumer($queueName)->receive($duration * 1000)) {
            return new Message($message->getBody(), $message);
        }

        return null;
    }

    public function acknowledgeMessage(string $queueName, mixed $receipt): void
    {
        $this->getQueueConsumer($queueName)->acknowledge($receipt);
    }

    public function info(): array
    {
        return [];
    }

    public function countMessages(string $queueName): int
    {
        if ($this->context instanceof AmqpContext) {
            return $this->context->declareQueue($this->createAmqpQueue($queueName));
        }

        return 0;
    }

    public function peekQueue(string $queueName, int $index = 0, int $limit = 20): array
    {
        return [];
    }

    private function getQueueConsumer(string $queueName): PsrConsumer
    {
        if (\array_key_exists($queueName, $this->consumers) === false) {
            $queue = $this->context->createQueue($queueName);

            $this->consumers[$queueName] = $this->context->createConsumer($queue);
        }

        return $this->consumers[$queueName];
    }

    private function createAmqpQueue(string $queueName): AmqpQueue
    {
        /** @var AmqpContext $context */
        $context = $this->context;

        $queue = $context->createQueue($queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        return $queue;
    }
}
