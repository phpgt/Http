PSR-7 HTTP message implementation.
==================================

HTTP messages are the foundation of web development. Web browsers and HTTP clients such as cURL create HTTP request messages that are sent to a web server, which provides an HTTP response message. Server-side code receives an HTTP request message, and returns an HTTP response message.

Whatever tool kit a PHP web application is built upon, HTTP messages always behave in the same way. PSR-7 is a set of PHP interfaces defined by the [PHP Framework Interop Group][fig] in order to produce code that can be shared between application implementations.

This repository is an implementation of the PSR-7 interfaces for use within PHP.Gt/WebEngine, but can be used in any other PHP web application thanks to the interoperability of PSR-7.

***

<a href="https://github.com/PhpGt/Http/actions" target="_blank">
	<img src="https://badge.status.php.gt/http-build.svg" alt="PHP.Gt/Http build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Http" target="_blank">
	<img src="https://badge.status.php.gt/http-quality.svg" alt="PHP.Gt/Http code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Http" target="_blank">
	<img src="https://badge.status.php.gt/http-coverage.svg" alt="PHP.Gt/Http code coverage" />
</a>
<a href="https://packagist.PhpGt/packages/PhpGt/Http" target="_blank">
	<img src="https://badge.status.php.gt/http-version.svg" alt="PHP.Gt/Http latest release" />
</a>
<a href="http://www.php.gt/Http" target="_blank">
	<img src="https://badge.status.php.gt/http-docs.svg" alt="PHP.Gt/Http documentation" />
</a>

[fig]: https://www.php-fig.org/psr/psr-7/

### Proudly sponsored by

[JetBrains Open Source sponsorship program](https://www.jetbrains.com/community/opensource/)

[![JetBrains logo.](https://resources.jetbrains.com/storage/products/company/brand/logos/jetbrains.svg)](https://www.jetbrains.com/community/opensource/)
