<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function assert;
use function file_exists;
use function is_dir;
use function is_file;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\substr;
use function Safe\unlink;
use function strtoupper;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;

abstract class TestCase extends PHPUnitTestCase
{
    use ProphecyTrait;

    public const WINDOWS_TEMP_DIR_PREFIX = 'C:\\t\\';
    public const DEFAULT_AWAIT_TIMEOUT   = 60;
    public const WIN_START               = 0;
    public const WIN_END                 = 2;
    public const USLEEP                  = 50;
    public const DEFAULT_MODE            = 0777;

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

    /**
     * @return array<int, array<int, bool>>
     */
    final public function provideTrueFalse(): array
    {
        return [
            [true],
            [false],
        ];
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
            assert($node instanceof SplFileInfo);
            if (is_dir($node->getPathname())) {
                $this->rmdir($node->getPathname());
                continue;
            }

            if (is_file($node->getPathname())) {
                unlink($node->getPathname());
                continue;
            }
        }

        rmdir($dir);
    }

    final protected function getTmpDir(): string
    {
        if (! file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, self::DEFAULT_MODE, true);
        }

        return $this->tmpDir;
    }

    final protected function getRandomNameSpace(): string
    {
        return $this->tmpNamespace;
    }

    /**
     * @return string[]
     */
    final protected function getFilesInDirectory(string $path): array
    {
        $files = [];

        $directory = new RecursiveDirectoryIterator($path);
        $directory = new RecursiveIteratorIterator($directory);

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
