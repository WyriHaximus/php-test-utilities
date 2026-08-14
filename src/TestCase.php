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
use Throwable;

use function file_exists;
use function in_array;
use function is_file;
use function is_link;
use function mkdir;
use function preg_match;
use function rmdir;
use function str_contains;
use function str_starts_with;
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

    /** @var list<mixed>|null */
    private array|null $rmdirIteratorOverride = null;

    /** @infection-ignore-all */
    #[Before]
    final protected function initializeTemporaryTestEnvironment(): void
    {
        $sysTempDir = $this->resolveBaseTempDirectory();

        $this->baseTmpDir = $sysTempDir .
            DIRECTORY_SEPARATOR .
            'w-h-p-t-u-' .
            uniqid() .
            DIRECTORY_SEPARATOR;
        $this->tmpDir     = $this->baseTmpDir .
            uniqid() .
            DIRECTORY_SEPARATOR;

        $this->tmpNamespace = uniqid('WHPTU');
    }

    /** @infection-ignore-all */
    #[After]
    final protected function cleanUpTemporaryTestEnvironment(): void
    {
        if (! file_exists($this->baseTmpDir)) {
            return;
        }

        try {
            $this->rmdir($this->baseTmpDir);
        } catch (Throwable) {
        }
    }

    /** @return iterable<array<bool>> */
    final public static function provideTrueFalse(): iterable
    {
        yield 'true' => [true];
        yield 'false' => [false];
    }

    final protected function getSysTempDir(): string
    {
        return sys_get_temp_dir();
    }

    /**
     * @return non-empty-string
     *
     * @infection-ignore-all
     */
    private function resolveBaseTempDirectory(): string
    {
        return $this->absoluteTempDirectory(sys_get_temp_dir());
    }

    /**
     * @return non-empty-string
     *
     * @infection-ignore-all
     */
    private function absoluteTempDirectory(string $directory): string
    {
        /** @var non-empty-string $fallback */
        $fallback = sys_get_temp_dir();

        if (strtoupper(substr(PHP_OS, self::WIN_START, self::WIN_END)) === 'WIN' && DIRECTORY_SEPARATOR !== '\\') {
            // @codeCoverageIgnoreStart
            return $fallback;
            // @codeCoverageIgnoreEnd
        }

        if (in_array($directory, ['', '.', self::WINDOWS_TEMP_DIR_PREFIX], true)) {
            // @codeCoverageIgnoreStart
            return $fallback;
            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        if (DIRECTORY_SEPARATOR === '/') {
            if (! str_starts_with($directory, '/')) {
                return $fallback;
            }

            if (str_contains($directory, '\\')) {
                return $fallback;
            }
        } elseif (preg_match('/^(?:[A-Za-z]:[\\\\\\/]|\\\\\\\\)/', $directory) !== 1) {
            return $fallback;
        }

        // @codeCoverageIgnoreEnd

        return $directory;
    }

    final protected function rmdir(string $dir): void
    {
        foreach ($this->createRmdirIterator($dir) as $node) {
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

    /** @return iterable<mixed> */
    private function createRmdirIterator(string $dir): iterable
    {
        if ($this->rmdirIteratorOverride !== null) {
            return $this->rmdirIteratorOverride;
        }

        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
    }

    /** @param list<mixed> $override */
    final protected function setRmdirIteratorOverride(array $override): void
    {
        $this->rmdirIteratorOverride = $override;
    }

    final protected function clearRmdirIteratorOverride(): void
    {
        $this->rmdirIteratorOverride = null;
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
