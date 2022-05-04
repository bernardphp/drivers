<?php

declare(strict_types=1);

namespace Bernard\Driver\Pheanstalk\Tests;

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\Contract\PheanstalkInterface;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var PheanstalkInterface|ObjectProphecy
     */
    private $pheanstalk;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->pheanstalk = $this->prophesize(PheanstalkInterface::class);

        $this->driver = new Driver($this->pheanstalk->reveal());
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }

    public function testItPeeksAQueue(): void
    {
        $this->assertEquals([], $this->driver->peekQueue('my-queue2'));
    }
}
