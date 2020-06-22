<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use WyriHaximus\TestUtilities\TestCase;

use function random_int;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function time;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;

/**
 * @internal
 */
final class TestCaseTest extends TestCase
{
    public const PENTIUM = 66;

    private string $previousTemporaryDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return array<int, string>
     */
    public function provideTemporaryDirectory(): iterable
    {
        for ($i = 0; $i <= self::PENTIUM; $i++) {
            yield [
                (string) random_int($i * $i, PHP_INT_MAX),
            ];
        }
    }

    public function testRecursiveDirectoryCreation(): void
    {
        static::assertFileExists($this->getTmpDir());
    }

    /**
     * @dataProvider provideTemporaryDirectory
     */
    public function testTemporaryDirectoryAndGetFilesInDirectory(string $int): void
    {
        static::assertNotSame($this->getTmpDir(), $this->previousTemporaryDirectory);

        $dir = $this->getTmpDir() . $this->getRandomNameSpace() . DIRECTORY_SEPARATOR;
        mkdir($dir);

        for ($i = 0; $i < self::PENTIUM; $i++) {
            static::assertCount($i, $this->getFilesInDirectory($this->getTmpDir()), (string) $i);
            file_put_contents($dir . $i, $int);
        }

        static::assertCount(self::PENTIUM, $this->getFilesInDirectory($this->getTmpDir()));

        foreach ($this->getFilesInDirectory($this->getTmpDir()) as $file) {
            static::assertSame($int, file_get_contents($file));
        }
    }

    /**
     * @param mixed $bool
     *
     * @dataProvider provideTrueFalse
     */
    public function testTrueFalse($bool): void
    {
        static::assertIsBool($bool);
    }

    public function testGetSysTempDir(): void
    {
        self::assertFileExists($this->getSysTempDir());
    }

    public function testWaitUntilTheNextSecond(): void
    {
        $now = time();
        static::waitUntilTheNextSecond();
        self::assertSame($now + 1, time());
    }

    public function testRmDir(): void
    {
        $tmpDir = $this->getSysTempDir() .
            DIRECTORY_SEPARATOR .
            'p-a-c-t-' .
            uniqid() .
            DIRECTORY_SEPARATOR;

        mkdir($tmpDir);

        self::assertDirectoryExists($tmpDir);
        $this->rmdir($tmpDir);
        self::assertDirectoryDoesNotExist($tmpDir);
    }
}
