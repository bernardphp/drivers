<?php

namespace Bernard\Driver\Redis\Tests;

use Bernard\Driver\Redis\Driver;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @requires extension redis
 */
final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Redis|ObjectProphecy
     */
    private $redis;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->redis = $this->prophesize(\Redis::class);

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
