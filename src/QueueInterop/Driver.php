<?php

namespace Bernard\Driver\QueueInterop;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpQueue;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;

final class Driver implements \Bernard\Driver
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var PsrConsumer[]
     */
    private $consumers = [];

    public function __construct(PsrContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        if ($this->context instanceof AmqpContext) {
            $this->context->declareQueue($this->createAmqpQueue($queueName));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countMessages($queueName)
    {
        if ($this->context instanceof AmqpContext) {
            return $this->context->declareQueue($this->createAmqpQueue($queueName));
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function pushMessage($queueName, $message)
    {
        $queue = $this->context->createQueue($queueName);
        $message = $this->context->createMessage($message);

        $this->context->createProducer()->send($queue, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function popMessage($queueName, $duration = 5)
    {
        if ($message = $this->getQueueConsumer($queueName)->receive($duration * 1000)) {
            return [$message->getBody(), $message];
        }

        return [null, null];
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledgeMessage($queueName, $receipt)
    {
        $this->getQueueConsumer($queueName)->acknowledge($receipt);
    }

    /**
     * {@inheritdoc}
     */
    public function peekQueue($queueName, $index = 0, $limit = 20)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function removeQueue($queueName)
    {
        if ($this->context instanceof AmqpContext) {
            $queue = $this->createAmqpQueue($queueName);

            $this->context->deleteQueue($queue);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function info()
    {
        return [];
    }

    /**
     * @param string $queueName
     *
     * @return PsrConsumer
     */
    private function getQueueConsumer($queueName)
    {
        if (\array_key_exists($queueName, $this->consumers) === false) {
            $queue = $this->context->createQueue($queueName);

            $this->consumers[$queueName] = $this->context->createConsumer($queue);
        }

        return $this->consumers[$queueName];
    }

    /**
     * @param string $queueName
     *
     * @return AmqpQueue
     */
    private function createAmqpQueue($queueName)
    {
        /** @var AmqpContext $context */
        $context = $this->context;

        $queue = $context->createQueue($queueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        return $queue;
    }
}
