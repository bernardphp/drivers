<?php

declare(strict_types=1);

namespace Bernard\Driver\AppEngine\Tests;

use Bernard\Driver\AppEngine\Driver;
use google\appengine\api\taskqueue\PushTask;

final class DriverTest extends \PHPUnit\Framework\TestCase
{
    private Driver $driver;

    public static function setUpBeforeClass(): void
    {
        // Very ugly hack! But AppEngine SDK isn't available outside appengine
        // environment.
        class_alias(\Bernard\Driver\AppEngine\Tests\Fixtures\PushTask::class, 'google\appengine\api\taskqueue\PushTask');
    }

    protected function setUp(): void
    {
        $this->driver = new Driver([
            'send-newsletter' => '/url_endpoint',
        ]);
    }

    protected function tearDown(): void
    {
        PushTask::$messages = [];
    }

    public function testItQueuesPushTask(): void
    {
        $this->driver->pushMessage('send-newsletter', 'message');

        $message = new PushTask('/url_endpoint', ['message' => 'message']);
        $this->assertEquals($message, PushTask::$messages['send-newsletter'][0]);
    }

    public function testItUsesDefaultEndpointWhenAliasArentThere(): void
    {
        $this->driver->pushMessage('import-users', 'message');
        $this->driver->pushMessage('calculate-reports', 'message');

        $messages = [
            new PushTask('/_ah/queue/import-users', ['message' => 'message']),
            new PushTask('/_ah/queue/calculate-reports', ['message' => 'message']),
        ];

        $this->assertEquals($messages[0], PushTask::$messages['import-users'][0]);
        $this->assertEquals($messages[1], PushTask::$messages['calculate-reports'][0]);
    }

    public function testListQueues(): void
    {
        $this->assertEquals(['/url_endpoint' => 'send-newsletter'], $this->driver->listQueues());
    }
}
