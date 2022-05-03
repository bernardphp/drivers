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

    protected function setUp(): void
    {
        $this->pheanstalk = $this->prophesize(PheanstalkInterface::class);

        $this->driver = new Driver($this->pheanstalk->reveal());
    }

    public function testItIsADriver()
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItPeeksAQueue()
    {
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }
}
