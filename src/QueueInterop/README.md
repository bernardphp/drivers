# Interop driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/queue-interop-driver.svg?style=flat-square)](https://github.com/bernardphp/queue-interop-driver/releases)

**[Queue Interop](https://github.com/queue-interop) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/queue-interop-driver
```


## Usage

```php
<?php

use Bernard\Driver\QueueInterop\Driver;

//$context = queue interop context
$driver = new Driver($context);
```


## License

The project is licensed under the [MIT License](LICENSE).
