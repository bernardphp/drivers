# Pheanstalk driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/pheanstalk-driver.svg?style=flat-square)](https://github.com/bernardphp/pheanstalk-driver/releases)

**[Pheanstalk](https://github.com/pda/pheanstalk) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/pheanstalk-driver
```


## Usage

```php
<?php

use Bernard\Driver\Pheanstalk\Driver;
use Pheanstalk\Pheanstalk;

$pheanstalk = new Pheanstalk('localhost');
$driver = new Driver($pheanstalk);
```


## License

The project is licensed under the [MIT License](LICENSE).
