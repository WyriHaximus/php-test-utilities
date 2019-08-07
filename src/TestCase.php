<?php declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function Safe\unlink;
use function Safe\substr;
use function Safe\mkdir;
use function Safe\rmdir;

abstract class TestCase extends PHPUnitTestCase
{
    const DEFAULT_AWAIT_TIMEOUT = 60;
    const WIN_START = 0;
    const WIN_END = 3;
    const USLEEP = 50;
    const DEFAULT_MODE = 0777;

    /**
     * @var string
     */
    private $baseTmpDir;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var string
     */
    private $tmpNamespace;

    protected function setUp(): void
    {
        $this->baseTmpDir = $this->getSysTempDir() .
            \DIRECTORY_SEPARATOR .
            'p-a-c-t-' .
            \uniqid() .
            \DIRECTORY_SEPARATOR;
        $this->tmpDir = $this->baseTmpDir .
            \uniqid() .
            \DIRECTORY_SEPARATOR;
        ;

        $this->tmpNamespace = \uniqid('PACTN');
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->baseTmpDir)) {
            $this->rmdir($this->baseTmpDir);
        }
    }

    /**
     * @return array<int, array<int, bool>>
     */
    public function provideTrueFalse(): array
    {
        return [
            [
                true,
            ],
            [
                false,
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getSysTempDir(): string
    {
        if (\strtoupper(substr(\PHP_OS, self::WIN_START, self::WIN_END)) === 'WIN') {
            return 'C:\\t\\';
        }

        return \sys_get_temp_dir();
    }

    /**
     * @param string $dir
     */
    protected function rmdir(string $dir): void
    {
        $directory = new FilesystemIterator($dir);

        /** @var \SplFileInfo $node */
        foreach ($directory as $node) {
            if (\is_dir($node->getPathname())) {
                $this->rmdir($node->getPathname());
                continue;
            }

            if (\is_file($node->getPathname())) {
                unlink($node->getPathname());
                continue;
            }
        }

        rmdir($dir);
    }

    /**
     * @return string
     */
    protected function getTmpDir(): string
    {
        if (!\file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, self::DEFAULT_MODE, true);
        }

        return $this->tmpDir;
    }

    /**
     * @return string
     */
    protected function getRandomNameSpace(): string
    {
        return $this->tmpNamespace;
    }

    /**
     * @param  string $path
     * @return string[]
     */
    protected function getFilesInDirectory(string $path): array
    {
        $files = [];

        $directory = new RecursiveDirectoryIterator($path);
        $directory = new RecursiveIteratorIterator($directory);

        foreach ($directory as $node) {
            if (!\is_file($node->getPathname())) {
                continue;
            }

            $files[] = $node->getPathname();
        }

        return $files;
    }

    protected static function waitUntilTheNextSecond(): void
    {
        $now = \time();
        do {
            // @codeCoverageIgnoreStart
            \usleep(self::USLEEP);
            // @codeCoverageIgnoreEnd
        } while ($now === \time());
    }
}
