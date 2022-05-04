<?php

declare(strict_types=1);

namespace Bernard\Driver\Amqp\Tests;

use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public const EXCHANGE = 'exchange';
    public const QUEUE = 'queue';
    public const MESSAGE = 'message';

    /**
     * @var AbstractConnection|ObjectProphecy
     */
    private $connection;

    /**
     * @var AMQPChannel|ObjectProphecy
     */
    private $channel;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->channel = $this->prophesize(AMQPChannel::class);
        $this->channel->close()->willReturn(null);

        $this->connection = $this->prophesize(AbstractConnection::class);
        $this->connection->channel()->willReturn($this->channel);

        $this->driver = new Driver($this->connection->reveal(), self::EXCHANGE);
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItListsQueues(): void
    {
        $this->assertEquals([], $this->driver->listQueues());
    }

    public function testItExposesInfo(): void
    {
        $this->assertEquals([], $this->driver->info());
    }

    public function testItPeeksAQueue(): void
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }
}
