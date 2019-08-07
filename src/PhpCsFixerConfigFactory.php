<?php declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Finder;
use WyriHaximus\CsFixerConfig\PhpCsFixerConfig;

final class PhpCsFixerConfigFactory
{
    private const USE_CACHE = false;

    /**
     * @param string[] $paths
     */
    public static function create(array $paths): ConfigInterface
    {
        /** @var \Traversable $finder */
        $finder = Finder::create()
            ->in($paths)
            ->append($paths);

        return PhpCsFixerConfig::create()
            ->setFinder($finder)
            ->setUsingCache(self::USE_CACHE)
            ;
    }
}
