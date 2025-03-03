<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use ComposerUnused\ComposerUnused\Configuration\PatternFilter;
use Webmozart\Glob\Glob;

return static function (Configuration $config): Configuration {
    return $config
        ->addNamedFilter(NamedFilter::fromString('ecoapm/libyear'))
        ->addNamedFilter(NamedFilter::fromString('ergebnis/composer-normalize'))
        ->addNamedFilter(NamedFilter::fromString('ergebnis/phpunit-slow-test-detector'))
        ->addNamedFilter(NamedFilter::fromString('icanhazstring/composer-unused'))
        ->addNamedFilter(NamedFilter::fromString('infection/infection'))
        ->addNamedFilter(NamedFilter::fromString('maglnet/composer-require-checker'))
        ->addNamedFilter(NamedFilter::fromString('php-coveralls/php-coveralls'))
        ->addNamedFilter(NamedFilter::fromString('php-parallel-lint/php-console-highlighter'))
        ->addNamedFilter(NamedFilter::fromString('php-parallel-lint/php-parallel-lint'))
        ->addNamedFilter(NamedFilter::fromString('phpstan/phpstan'))
        ->addNamedFilter(NamedFilter::fromString('roave/backward-compatibility-check'))
        ->addNamedFilter(NamedFilter::fromString('squizlabs/php_codesniffer'))
        ->addNamedFilter(NamedFilter::fromString('wyrihaximus/coding-standard'))
        ->addNamedFilter(NamedFilter::fromString('wyrihaximus/phpstan-rules-wrapper'));
};
