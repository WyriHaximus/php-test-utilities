<?php declare(strict_types=1);

namespace ApiClients\Tools\TestUtilities;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;
use function Clue\React\Block\awaitAny;

abstract class TestCase extends PHPUnitTestCase
{
    const DEFAULT_AWAIT_TIMEOUT = 60;

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
        parent::setUp();

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
        parent::tearDown();

        if (\file_exists($this->baseTmpDir)) {
            $this->rmdir($this->baseTmpDir);
        }
    }

    /**
     * @return array
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
        if (\strtoupper(\substr(\PHP_OS, 0, 3)) === 'WIN') {
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
                \unlink($node->getPathname());
                continue;
            }
        }

        \rmdir($dir);
    }

    /**
     * @return string
     */
    protected function getTmpDir(): string
    {
        if (!\file_exists($this->tmpDir)) {
            \mkdir($this->tmpDir, 0777, true);
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
     * @return array
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

    /**
     * @param  PromiseInterface   $promise
     * @param  LoopInterface|null $loop
     * @return mixed
     */
    protected function await(PromiseInterface $promise, LoopInterface $loop = null, float $timeout = self::DEFAULT_AWAIT_TIMEOUT)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return await($promise, $loop, $timeout);
    }

    /**
     * @param  array              $promises
     * @param  LoopInterface|null $loop
     * @return array
     */
    protected function awaitAll(array $promises, LoopInterface $loop = null, float $timeout = self::DEFAULT_AWAIT_TIMEOUT)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return awaitAll($promises, $loop, $timeout);
    }

    /**
     * @param  array              $promises
     * @param  LoopInterface|null $loop
     * @return mixed
     */
    protected function awaitAny(array $promises, LoopInterface $loop = null, float $timeout = self::DEFAULT_AWAIT_TIMEOUT)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return awaitAny($promises, $loop, $timeout);
    }
}
