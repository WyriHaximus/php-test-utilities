<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use Rector\Config;
use Rector\Configuration\RectorConfigBuilder;

final class RectorConfig
{
    public static function configure(string $packageRootPath): RectorConfigBuilder
    {
        return Config\RectorConfig::configure()
            ->withPaths([
                $packageRootPath . '/etc',
                $packageRootPath . '/src',
                $packageRootPath . '/tests',
            ])
            ->withAttributesSets(all: true)
            ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
            ->withPreparedSets(
                typeDeclarations: true,
                codeQuality: true,
            )
            ->withPhpSets();
    }
}
