# Monolog syslog3164 handler
[![Latest Stable Version](https://poser.pugx.org/Dalee/monolog-syslog3164/version)](https://packagist.org/packages/dalee/monolog-syslog3164)
[![Build Status](https://travis-ci.org/Dalee/monolog-syslog3164.svg?branch=master)](https://travis-ci.org/Dalee/monolog-syslog3164)
[![codecov](https://codecov.io/gh/Dalee/monolog-syslog3164/branch/master/graph/badge.svg)](https://codecov.io/gh/Dalee/monolog-syslog3164)

## Getting started

```bash
$ composer require dalee/monolog-syslog3164
```

## Example

```php
use Monolog\Logger;
use Dalee\Monolog\Handler\Syslog3164Handler;

$logger = new Logger('main');
$handler = new Syslog3164Handler('127.0.0.1', 9010, Syslog3164Handler::FACILITY_UUCP);
$handler->setTag('switchman')->setHostname('app.local');
$logger->pushHandler($handler);
$logger->debug('Error occurred', [
	'system' => 'customer-service',
	'kind' => 'error',
	'payload' => [
		'code' => 5194,
		'message' => 'Error sending report'
	]
]);
```

By default `Syslog3164Handler` is constructed with `127.0.0.1:514`, debug level and bubbling. The default facility is `user`.

The output for above is:

```
<15>Oct  4 23:10:59 app.local php: Error occurred {"system":"customer-service","kind":"error","payload":{"code":5194,"message":"Error sending report"}}
```

## Notices

According to RFC3164, the total length of the packet must be 1024 or less. In most cases that's not what you want so
it's disabled by default but you can use `setStrictSize` to turn it on.

The code consists of a single really simple file so there is no separated documentation.

## Links

https://github.com/Seldaek/monolog

https://www.ietf.org/rfc/rfc3164.txt
