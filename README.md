# Bernard Drivers

[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/bernardphp/drivers/CI?style=flat-square)](https://github.com/bernardphp/drivers/actions?query=workflow%3ACI)
[![Total Downloads](https://img.shields.io/packagist/dt/bernard/drivers.svg?style=flat-square)](https://packagist.org/packages/bernard/drivers)

**Official Bernard drivers.**


## Install

Via Composer

```shell
composer require bernard/drivers
```


## Drivers

- [Amazon SQS](src/Sqs)
- [AMQP](src/Amqp)
- [Google App Engine](src/AppEngine)
- [Iron MQ](src/IronMQ)
- [Pheanstalk](src/Pheanstalk)
- [Predis](src/Predis)
- [Queue Interop](src/QueueInterop)
- [Redis](src/Redis)


## Development

When all coding is done, please run the test suite:

```shell
composer test
```

To run the integration test suite as well: First, start the services using Docker Compose:

```shell
docker-compose up -d
```

Wait for them to start. Then execute the integration test suite:

```shell
composer test-integration
```

For the best developer experience, install [Nix](https://builtwithnix.org/) and [direnv](https://direnv.net/).

Alternatively, install PHP (and the required extensions) and Composer manually or using a package manager.


## License

The project is licensed under the [MIT License](LICENSE).
