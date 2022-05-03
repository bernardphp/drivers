<?php

namespace Bernard\Driver\Amqp;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class Driver implements \Bernard\Driver
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var array
     */
    private $defaultMessageProperties;

    /**
     * @param string $exchange
     */
    public function __construct(AbstractConnection $connection, $exchange, array $defaultMessageProperties = [])
    {
        $this->connection = $connection;
        $this->exchange = $exchange;
        $this->defaultMessageProperties = $defaultMessageProperties;
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
        $channel = $this->getChannel();

        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $this->exchange, $queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function countMessages($queueName)
    {
        [, $messageCount] = $this->getChannel()->queue_declare($queueName, true);

        return $messageCount;
    }

    /**
     * {@inheritdoc}
     */
    public function pushMessage($queueName, $message)
    {
        $amqpMessage = new AMQPMessage($message, $this->defaultMessageProperties);

        $this->getChannel()->basic_publish($amqpMessage, $this->exchange, $queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function popMessage($queueName, $duration = 5)
    {
        $runtime = microtime(true) + $duration;

        while (microtime(true) < $runtime) {
            $message = $this->getChannel()->basic_get($queueName);

            if ($message) {
                return [$message->body, $message->get('delivery_tag')];
            }

            // sleep for 10 ms to prevent hammering CPU
            usleep(10000);
        }

        return [null, null];
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledgeMessage($queueName, $receipt)
    {
        $this->getChannel()->basic_ack($receipt);
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
        $this->getChannel()->queue_delete($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function info()
    {
        return [];
    }

    public function __destruct()
    {
        if (null !== $this->channel) {
            $this->channel->close();
        }
    }

    /**
     * Creates a channel or returns an already created one.
     *
     * @return AMQPChannel
     */
    private function getChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }
}
