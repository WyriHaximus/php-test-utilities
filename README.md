# Test utilities

Test utilities for api-clients packages.

![Continuous Integration](https://github.com/wyrihaximus/php-test-utilities/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/wyrihaximus/test-utilities/v/stable.png)](https://packagist.org/packages/wyrihaximus/test-utilities)
[![Total Downloads](https://poser.pugx.org/wyrihaximus/test-utilities/downloads.png)](https://packagist.org/packages/wyrihaximus/test-utilities/stats)
[![Type Coverage](https://shepherd.dev/github/WyriHaximus/php-test-utilities/coverage.svg)](https://shepherd.dev/github/WyriHaximus/php-test-utilities)
[![License](https://poser.pugx.org/wyrihaximus/test-utilities/license.png)](https://packagist.org/packages/wyrihaximus/test-utilities)

# Installation

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/test-utilities
```

When [wyrihaximus/makefiles](https://github.com/WyriHaximus/makefiles) is present, this package registers as a Composer plugin and adds `make on-install-or-update || true` to your `composer.json` scripts on install and update.

# Usage

## PHPStan

This package provides a PHPStan extension so your test classes may extend `WyriHaximus\TestUtilities\TestCase`.

When [phpstan/extension-installer](https://github.com/phpstan/extension-installer) is enabled, the extension is registered automatically—no manual configuration required.

<details>
<summary>Manual configuration</summary>

If extension-installer is not available, include the extension from your PHPStan configuration:

```neon
includes:
    - vendor/wyrihaximus/test-utilities/extension.neon
```

The following PHPStan extensions are provided through [`wyrihaximus/phpstan-rules-wrapper`](https://github.com/WyriHaximus/phpstan-rules-wrapper):

<!-- included-tooling:wyrihaximus/phpstan-rules-wrapper:start -->
* [`ergebnis/phpstan-rules`](https://packagist.org/packages/ergebnis/phpstan-rules)
* [`phpstan/extension-installer`](https://packagist.org/packages/phpstan/extension-installer)
* [`phpstan/phpstan`](https://packagist.org/packages/phpstan/phpstan)
* [`phpstan/phpstan-deprecation-rules`](https://packagist.org/packages/phpstan/phpstan-deprecation-rules)
* [`phpstan/phpstan-mockery`](https://packagist.org/packages/phpstan/phpstan-mockery)
* [`phpstan/phpstan-phpunit`](https://packagist.org/packages/phpstan/phpstan-phpunit)
* [`phpstan/phpstan-strict-rules`](https://packagist.org/packages/phpstan/phpstan-strict-rules)
* [`shipmonk/dead-code-detector`](https://packagist.org/packages/shipmonk/dead-code-detector)
* [`shipmonk/phpstan-rules`](https://packagist.org/packages/shipmonk/phpstan-rules)
* [`staabm/phpstan-psr3`](https://packagist.org/packages/staabm/phpstan-psr3)
* [`symplify/phpstan-extensions`](https://packagist.org/packages/symplify/phpstan-extensions)
* [`tomasvotruba/type-coverage`](https://packagist.org/packages/tomasvotruba/type-coverage)
* [`wyrihaximus/phpstan-no-safe`](https://packagist.org/packages/wyrihaximus/phpstan-no-safe)
* [`yamadashy/phpstan-friendly-formatter`](https://packagist.org/packages/yamadashy/phpstan-friendly-formatter)
<!-- included-tooling:wyrihaximus/phpstan-rules-wrapper:end -->

</details>

## PHPUnit

Extend `WyriHaximus\TestUtilities\TestCase` in your tests for temporary directories, unique namespaces, and helpers suited to file-storage tests.

Before each test, `initializeTemporaryTestEnvironment()` creates a unique temporary directory and namespace. After each test, `cleanUpTemporaryTestEnvironment()` removes them.

Mockery integration is included, along with helpers such as:

- `getTmpDir()` — a unique temporary directory for each test
- `getRandomNameSpace()` — a unique namespace string for each test
- `getFilesInDirectory()` — list files in a directory recursively
- `rmdir()` — recursively remove a directory, including symlinks
- `provideTrueFalse()` — a data provider for boolean values (don't ask)
- `waitUntilTheNextSecond()` — wait until the next second boundary

## Rector

Rector configuration is provided through [`wyrihaximus/rector-config`](https://github.com/WyriHaximus/rectorphp-config), which pulls in [`rector/rector`](https://packagist.org/packages/rector/rector) and applies defaults for `etc`, `examples`, `src`, and `tests`. It also converts supported docblock annotations to attributes.

Create `etc/qa/rector.php`:

```php
<?php

declare(strict_types=1);

use WyriHaximus\RectorPHP\RectorConfig;

return RectorConfig::configure(dirname(__DIR__, 2));
```

# Included tooling

Most QA tooling is bundled through [`wyrihaximus/qa-tooling-wrapper`](https://github.com/WyriHaximus/php-qa-tooling-wrapper):

<!-- included-tooling:wyrihaximus/qa-tooling-wrapper:start -->
* [`ergebnis/composer-normalize`](https://packagist.org/packages/ergebnis/composer-normalize)
* [`ergebnis/phpunit-slow-test-detector`](https://packagist.org/packages/ergebnis/phpunit-slow-test-detector)
* [`icanhazstring/composer-unused`](https://packagist.org/packages/icanhazstring/composer-unused)
* [`infection/infection`](https://packagist.org/packages/infection/infection)
* [`maglnet/composer-require-checker`](https://packagist.org/packages/maglnet/composer-require-checker)
* [`mockery/mockery`](https://packagist.org/packages/mockery/mockery)
* [`php-parallel-lint/php-console-highlighter`](https://packagist.org/packages/php-parallel-lint/php-console-highlighter)
* [`php-parallel-lint/php-parallel-lint`](https://packagist.org/packages/php-parallel-lint/php-parallel-lint)
* [`phpstan/phpstan`](https://packagist.org/packages/phpstan/phpstan)
* [`phpunit/phpunit`](https://packagist.org/packages/phpunit/phpunit)
* [`rector/rector`](https://packagist.org/packages/rector/rector)
* [`roave/backward-compatibility-check`](https://packagist.org/packages/roave/backward-compatibility-check)
* [`shipmonk/coverage-guard`](https://packagist.org/packages/shipmonk/coverage-guard)
* [`squizlabs/php_codesniffer`](https://packagist.org/packages/squizlabs/php_codesniffer)
* [`wyrihaximus/coding-standard`](https://packagist.org/packages/wyrihaximus/coding-standard)
<!-- included-tooling:wyrihaximus/qa-tooling-wrapper:end -->

# License

The MIT License (MIT)

Copyright (c) 2026 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
