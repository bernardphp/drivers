# Predis driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/predis-driver.svg?style=flat-square)](https://github.com/bernardphp/predis-driver/releases)

**[Redis](https://github.com/nrk/predis) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/predis-driver
```


## Usage

```php
<?php

use Bernard\Driver\Predis\Driver;
use Predis\Client;

$predis = new Client(
    'tcp://localhost',
    [
        'prefix' => 'bernard:',
    ]
);

$driver = new Driver($redis);
```


## License

The project is licensed under the [MIT License](LICENSE).
