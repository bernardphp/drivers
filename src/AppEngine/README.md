# App Engine driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/appengine-driver.svg?style=flat-square)](https://github.com/bernardphp/appengine-driver/releases)

**[Google App Engine](https://cloud.google.com/appengine) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/appengine-driver
```


## Usage

```php
<?php

use Bernard\Driver\AppEngine\Driver;

$driver = new Driver(['name' => 'endpoint']);
```


## License

The project is licensed under the [MIT License](LICENSE).
