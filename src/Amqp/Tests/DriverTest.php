<?php

namespace Bernard\Driver\Amqp\Tests;

use PhpAmqpLib\Channel\AMQPChannel;
use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Connection\AbstractConnection;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    const EXCHANGE = 'exchange';
    const QUEUE = 'queue';
    const MESSAGE = 'message';

    /**
     * @var AbstractConnection|ObjectProphecy
     */
    private $amqp;

    /**
     * @var AMQPChannel|ObjectProphecy
     */
    private $channel;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->channel = $this->prophesize(AMQPChannel::class);
        $this->channel->close()->willReturn(null);

        $this->amqp = $this->prophesize(AbstractConnection::class);
        $this->amqp->channel()->willReturn($this->channel);

        $this->driver = new Driver($this->amqp->reveal(), self::EXCHANGE);
    }

    /**
     * @test
     */
    public function it_is_a_driver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    /**
     * @test
     */
    public function it_lists_queues()
    {
        $this->assertEquals([], $this->driver->listQueues());
    }

    /**
     * @test
     */
    public function it_peeks_a_queue()
    {
        $this->assertEquals([], $this->driver->peekQueue(self::QUEUE));
    }

    /**
     * @test
     */
    public function it_exposes_info()
    {
        $this->assertEquals([], $this->driver->info());
    }
}
