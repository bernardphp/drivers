# IronMQ driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/ironmq-driver.svg?style=flat-square)](https://github.com/bernardphp/ironmq-driver/releases)

**[Iron MQ](http://www.iron.io/mq) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/ironmq-driver
```


## Usage

```php
<?php

use Bernard\Driver\IronMQ\Driver;
use IronMQ\IronMQ;

$connection = new IronMQ([
    'token'      => 'your-ironmq-token',
    'project_id' => 'your-ironmq-project-id',
]);
$driver = new Driver($connection);
```


## License

The project is licensed under the [MIT License](LICENSE).
