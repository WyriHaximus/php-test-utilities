<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use Rector\Config;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\ReplaceTestFunctionPrefixWithAttributeRector;

use function file_exists;

final class RectorConfig
{
    public static function configure(string $packageRootPath): RectorConfigBuilder
    {
        return Config\RectorConfig::configure()
            ->withPaths([
                ...self::getPaths($packageRootPath),
            ])
            ->withAttributesSets(all: true)
            ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
            ->withPreparedSets(
                codeQuality: true,
                typeDeclarations: true,
            )
            ->withPhpSets()
            ->withRules([
                ReplaceTestFunctionPrefixWithAttributeRector::class,
            ])->withSkip([
                RemoveExtraParametersRector::class,
            ]);
    }

    /** @return iterable<string> */
    private static function getPaths(string $packageRootPath): iterable
    {
        foreach (
            [
                '/etc',
                '/src',
                '/tests',
            ] as $path
        ) {
            if (! file_exists($packageRootPath . $path)) {
                continue;
            }

            yield $packageRootPath . $path;
        }
    }
}
