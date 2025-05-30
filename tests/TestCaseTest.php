<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function func_get_args;
use function random_int;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function str_starts_with;
use function strtoupper;
use function substr;
use function sys_get_temp_dir;
use function time;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;
use const PHP_OS;

final class TestCaseTest extends TestCase
{
    public const int PENTIUM = 66;

    private string $previousTemporaryDirectory = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @return array<int, string> */
    public static function provideTemporaryDirectory(): iterable
    {
        for ($i = 0; $i <= self::PENTIUM; $i++) {
            /**
             * Resolves: Parameter #1 $min (int<min, 4356>) of function random_int expects lower number than parameter #2 $max (int<1, max>).
             *
             * @phpstan-ignore-next-line
             */
            yield [(string) random_int($i * $i, PHP_INT_MAX)];
        }
    }

    #[Test]
    public function recursiveDirectoryCreation(): void
    {
        static::assertFileExists($this->getTmpDir());
    }

    #[Test]
    #[DataProvider('provideTemporaryDirectory')]
    public function testTemporaryDirectoryAndGetFilesInDirectory(string $int): void
    {
        static::assertTrue(strtoupper(substr(PHP_OS, TestCase::WIN_START, TestCase::WIN_END)) === 'WIN' ? str_starts_with(TestCase::WINDOWS_TEMP_DIR_PREFIX, sys_get_temp_dir()) : str_starts_with($this->getTmpDir(), sys_get_temp_dir()));
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

    #[Test]
    #[DataProvider('provideTrueFalse')]
    public function trueOrFalse(bool $bool): void
    {
        static::assertCount(1, func_get_args());
    }

    #[Test]
    public function testTrueAndFalse(): void
    {
        static::assertSame(
            ['true' => [true], 'false' => [false]],
            [...self::provideTrueFalse()],
        );
    }

    #[Test]
    public function successGetSysTempDir(): void
    {
        self::assertFileExists($this->getSysTempDir());
    }

    #[Test]
    public function successWaitUntilTheNextSecond(): void
    {
        $now = time();
        static::waitUntilTheNextSecond();
        self::assertSame($now + 1, time());
    }

    #[Test]
    public function successRmDir(): void
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
