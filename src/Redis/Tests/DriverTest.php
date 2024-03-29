<?php

declare(strict_types=1);

namespace Bernard\Driver\Redis\Tests;

use Bernard\Driver\Redis\Driver;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @requires extension redis
 */
final class DriverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var \Redis|ObjectProphecy
     */
    private $redis;

    private Driver $driver;

    protected function setUp(): void
    {
        $this->redis = $this->prophesize(\Redis::class);

        $this->driver = new Driver($this->redis->reveal());
    }

    public function testItIsADriver(): void
    {
        $this->assertInstanceOf(\Bernard\Driver::class, $this->driver);
    }
}
