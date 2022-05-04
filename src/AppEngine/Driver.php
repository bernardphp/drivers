<?php

declare(strict_types=1);

namespace Bernard\Driver\AppEngine;

use google\appengine\api\taskqueue\PushTask;

/**
 * Simple driver for google AppEngine. Many features are not supported.
 * It takes a list of array('name' => 'endpoint') to route messages to the
 * correct place.
 */
final class Driver implements \Bernard\Driver
{
    private $queueMap;

    public function __construct(array $queueMap)
    {
        $this->queueMap = $queueMap;
    }

    /**
     * {@inheritdoc}
     */
    public function listQueues()
    {
        return array_flip($this->queueMap);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function countMessages($queueName): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function pushMessage($queueName, $message): void
    {
        $task = new PushTask($this->resolveEndpoint($queueName), compact('message'));
        $task->add($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function popMessage($queueName, $duration = 5): void
    {
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
    public function info()
    {
        return [];
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    private function resolveEndpoint($queueName)
    {
        if (isset($this->queueMap[$queueName])) {
            return $this->queueMap[$queueName];
        }

        return '/_ah/queue/'.$queueName;
    }
}
