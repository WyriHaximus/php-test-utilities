<?php declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use function Safe\sort;
use function Safe\glob;
use SplFileInfo;
use WyriHaximus\TestUtilities\PhpCsFixerConfigFactory;
use WyriHaximus\TestUtilities\TestCase;

/**
 * @internal
 */
final class PhpCsFixerConfigFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $paths = [__DIR__];
        $config = PhpCsFixerConfigFactory::create($paths);
        /** @var \Traversable $finder */
        $finder = $config->getFinder();
        $keys = \array_filter(\array_keys(\array_map(function (SplFileInfo $fileInfo) {
            return $fileInfo->getFilename();
        }, \iterator_to_array($finder))), 'is_string');
        sort($keys);
        /** @var array $files */
        $files = glob(__DIR__ . '/*');
        sort($files);
        self::assertSame($files, $keys);
    }
}
