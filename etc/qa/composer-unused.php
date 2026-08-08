<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static fn (Configuration $config): Configuration => $config
    ->addNamedFilter(NamedFilter::fromString('wyrihaximus/qa-tooling-wrapper'))
    ->addNamedFilter(NamedFilter::fromString('wyrihaximus/phpstan-rules-wrapper'))
    ->setAdditionalFilesFor('wyrihaximus/test-utilities', ['etc/qa/rector.php']);
