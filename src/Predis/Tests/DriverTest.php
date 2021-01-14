<?php

namespace Bernard\Driver\Predis\Tests;

use Bernard\Driver\Predis\Driver;
use Predis\ClientInterface;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ClientInterface|ObjectProphecy
     */
    private $redis;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->redis = $this->prophesize(ClientInterface::class);

        $this->driver = new Driver($this->redis->reveal());
    }

    /**
     * @test
     */
    public function it_is_a_driver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }
}
