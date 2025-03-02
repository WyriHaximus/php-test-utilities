<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use FilesystemIterator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_exists;
use function is_dir;
use function is_file;
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

    protected function setUp(): void
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

    protected function tearDown(): void
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
        $directory = new FilesystemIterator($dir);

        foreach ($directory as $node) {
            if (!$node instanceof SplFileInfo) {
                continue;
            }

            if (is_dir($node->getPathname())) {
                $this->rmdir($node->getPathname());
                continue;
            }

            if (! is_file($node->getPathname())) {
                continue;
            }

            if (unlink($node->getPathname()) !== true) {
                throw ErrorExceptionFactory::create('Error deleting file: ' . $node->getPathname());
            }
        }

        if (@rmdir($dir) !== true) {
            throw ErrorExceptionFactory::create('Error deleting directory: ' . $dir);
        }
    }

    final protected function getTmpDir(): string
    {
        if (! file_exists($this->tmpDir)) {
            if (@mkdir($this->tmpDir, self::DEFAULT_MODE, true) !== true) {
                throw ErrorExceptionFactory::create('Error creating directory: ' . $this->tmpDir);
            }
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
