# AMQP driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/amqp-driver.svg?style=flat-square)](https://github.com/bernardphp/amqp-driver/releases)

**[AMQP](https://github.com/php-amqplib/php-amqplib) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/amqp-driver
```


## Usage

```php
<?php

use Bernard\Driver\Amqp\Driver;
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$driver = new Driver($connection, 'exchange');
```


## License

The project is licensed under the [MIT License](LICENSE).
