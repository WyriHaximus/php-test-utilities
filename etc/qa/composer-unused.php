<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

const UNUSED_PACKAGES = [
    'ergebnis/composer-normalize',
    'icanhazstring/composer-unused',
    'jakobbuis/simple-slow-test-reporter',
    'maglnet/composer-require-checker',
    'nunomaduro/collision',
    'orklah/psalm-insane-comparison',
    'php-coveralls/php-coveralls',
    'php-parallel-lint/php-console-highlighter',
    'php-parallel-lint/php-parallel-lint',
    'php-standard-library/psalm-plugin',
    'phpstan/phpstan',
    'psalm/plugin-phpunit',
    'roave/backward-compatibility-check',
    'roave/infection-static-analysis-plugin',
    'wyrihaximus/coding-standard',
    'wyrihaximus/phpstan-rules-wrapper'
];

return static function (Configuration $config): Configuration {
    foreach (UNUSED_PACKAGES as $package) {
        $config = $config->addNamedFilter(NamedFilter::fromString($package));
    }

    return $config;
};
