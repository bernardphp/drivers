# Redis driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/redis-driver.svg?style=flat-square)](https://github.com/bernardphp/redis-driver/releases)

**[Redis](https://github.com/nicolasff/phpredis) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/redis-driver
```


## Usage

```php
<?php

use Bernard\Driver\Redis\Driver;

$redis = new Redis();
$redis->connect('localhost', 6379);
$redis->setOption(Redis::OPT_PREFIX, 'bernard:');

$driver = new Driver($redis);
```


## License

The project is licensed under the [MIT License](LICENSE).
