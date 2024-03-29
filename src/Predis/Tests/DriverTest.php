<?php

declare(strict_types=1);

namespace Bernard\Driver\Predis\Tests;

use Bernard\Driver\Predis\Driver;
use Predis\ClientInterface;
use Prophecy\Prophecy\ObjectProphecy;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var ClientInterface|ObjectProphecy
     */
    private $redis;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->redis = $this->prophesize(ClientInterface::class);

        $this->driver = new Driver($this->redis->reveal());
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }
}
