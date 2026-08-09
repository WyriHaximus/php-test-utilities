<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use FilesystemIterator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function is_file;
use function is_link;
use function mkdir;
use function rmdir;
use function strtoupper;
use function substr;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function unlink;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;

abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    public const string WINDOWS_TEMP_DIR_PREFIX = 'C:\\t\\';
    public const int WIN_START                  = 0;
    public const int WIN_END                    = 2;
    public const int USLEEP                     = 50;
    public const int DEFAULT_MODE               = 0777;

    private string $baseTmpDir;

    private string $tmpDir;

    private string $tmpNamespace;

    #[Before]
    final protected function initializeTemporaryTestEnvironment(): void
    {
        $this->baseTmpDir = $this->getSysTempDir() .
            DIRECTORY_SEPARATOR .
            'w-h-p-t-u-' .
            uniqid() .
            DIRECTORY_SEPARATOR;
        $this->tmpDir     = $this->baseTmpDir .
            uniqid() .
            DIRECTORY_SEPARATOR;

        $this->tmpNamespace = uniqid('WHPTU');
    }

    #[After]
    final protected function cleanUpTemporaryTestEnvironment(): void
    {
        if (! file_exists($this->baseTmpDir)) {
            return;
        }

        $this->rmdir($this->baseTmpDir);
    }

    /** @return iterable<array<bool>> */
    final public static function provideTrueFalse(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
    }

    final protected function getSysTempDir(): string
    {
        if (strtoupper(substr(PHP_OS, self::WIN_START, self::WIN_END)) === 'WIN') {
            return self::WINDOWS_TEMP_DIR_PREFIX;
        }

        return sys_get_temp_dir();
    }

    final protected function rmdir(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $node) {
            if (! $node instanceof SplFileInfo) {
                continue;
            }

            $path = $node->getPathname();

            if ($node->isLink() || is_link($path)) {
                /** @phpstan-ignore ergebnis.noErrorSuppression */
                if (@unlink($path)) {
                    continue;
                }

                /** @phpstan-ignore ergebnis.noErrorSuppression */
                if (@rmdir($path)) {
                    continue;
                }

                throw ErrorExceptionFactory::create('Error deleting link: ' . $path);
            }

            if ($node->isDir()) {
                /** @phpstan-ignore ergebnis.noErrorSuppression */
                if (! @rmdir($path)) {
                    throw ErrorExceptionFactory::create('Error deleting directory: ' . $path);
                }

                continue;
            }

            /** @phpstan-ignore ergebnis.noErrorSuppression */
            if (! @unlink($path)) {
                throw ErrorExceptionFactory::create('Error deleting file: ' . $path);
            }
        }

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (! @rmdir($dir)) {
            throw ErrorExceptionFactory::create('Error deleting directory: ' . $dir);
        }
    }

    final protected function getTmpDir(): string
    {
        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (! file_exists($this->tmpDir) && ! @mkdir($this->tmpDir, self::DEFAULT_MODE, true)) {
            throw ErrorExceptionFactory::create('Error creating directory: ' . $this->tmpDir);
        }

        return $this->tmpDir;
    }

    final protected function getRandomNameSpace(): string
    {
        return $this->tmpNamespace;
    }

    /** @return list<string> */
    final protected function getFilesInDirectory(string $path): array
    {
        $files = [];

        /** @var iterable<SplFileInfo> $directory */
        $directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($directory as $node) {
            if (! is_file($node->getPathname())) {
                continue;
            }

            $files[] = $node->getPathname();
        }

        return $files;
    }

    final protected static function waitUntilTheNextSecond(): void
    {
        $now = time();
        do {
            // @codeCoverageIgnoreStart
            usleep(self::USLEEP);
            // @codeCoverageIgnoreEnd
        } while ($now === time());
    }
}
