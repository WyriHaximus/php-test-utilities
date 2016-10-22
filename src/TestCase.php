<?php declare(strict_types=1);

namespace ApiClients\Tools\TestUtilities;

use FilesystemIterator;
use PHPUnit_Framework_TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function Clue\React\Block\await;
use function Clue\React\Block\awaitAll;
use function Clue\React\Block\awaitAny;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $this->baseTmpDir = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'php-api-clients-tests-' .
            uniqid() .
            DIRECTORY_SEPARATOR;
        $this->tmpDir = $this->baseTmpDir .
            uniqid() .
            DIRECTORY_SEPARATOR;
        ;

        mkdir($this->tmpDir, 0777, true);
        $this->tmpNamespace = uniqid('PACTN');
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->rmdir($this->baseTmpDir);
    }

    protected function rmdir($dir)
    {
        $directory = new FilesystemIterator($dir);

        foreach ($directory as $node) {
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

    protected function getTmpDir(): string
    {
        return $this->tmpDir;
    }

    protected function getRandomNameSpace(): string
    {
        return $this->tmpNamespace;
    }

    protected function getFilesInDirectory(string $path): array
    {
        $files = [];

        $directory = new RecursiveDirectoryIterator($path);
        $directory = new RecursiveIteratorIterator($directory);

        foreach ($directory as $node) {
            if (!is_file($node->getPathname())) {
                continue;
            }

            $files[] = $node->getPathname();
        }

        return $files;
    }

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

    protected function await(PromiseInterface $promise, LoopInterface $loop = null)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return await($promise, $loop);
    }

    protected function awaitAll(array $promises, LoopInterface $loop = null)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return awaitAll($promises, $loop);
    }

    protected function awaitAny(array $promises, LoopInterface $loop = null)
    {
        if (!($loop instanceof LoopInterface)) {
            $loop = Factory::create();
        }

        return awaitAny($promises, $loop);
    }
}
