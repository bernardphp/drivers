# Amazon SQS driver

[![Latest Version](https://img.shields.io/github/release/bernardphp/sqs-driver.svg?style=flat-square)](https://github.com/bernardphp/sqs-driver/releases)

**[Amazon SQS](https://aws.amazon.com/sqs/) driver for Bernard.**


## Install

Via Composer

```shell
composer require bernard/sqs-driver
```


## Usage

```php
<?php

use Aws\Sqs\SqsClient;
use Bernard\Driver\Sqs\Driver;

$client = new SqsClient([
    'credentials' => [
        'key'     => 'your_access_key',
        'secret'  => 'your_secret_key',
    ],
    'region'  => 'us-east-1',
    'version' => '2012-11-05'
]);

$driver = new Driver($client);

// or with prefetching
$driver = new Driver($client, [], 5);

// or with aliased queue urls
$driver = new Driver($client, [
    'queue-name' => 'queue-url',
]);
```


## License

The project is licensed under the [MIT License](LICENSE).
