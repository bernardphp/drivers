<?php

namespace Bernard\Driver\Pheanstalk\Tests;

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\PheanstalkInterface;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PheanstalkInterface|ObjectProphecy
     */
    private $pheanstalk;

    /**
     * @var Driver
     */
    private $driver;

    public function setUp() : void
    {
        $this->pheanstalk = $this->prophesize(PheanstalkInterface::class);

        $this->driver = new Driver($this->pheanstalk->reveal());
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
    public function it_peeks_a_queue()
    {
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }
}
