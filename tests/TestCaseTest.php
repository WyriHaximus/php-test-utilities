<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function file_get_contents;
use function file_put_contents;
use function func_get_args;
use function mkdir;
use function random_int;
use function realpath;
use function symlink;
use function sys_get_temp_dir;
use function time;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;
use const PHP_OS_FAMILY;

final class TestCaseTest extends TestCase
{
    public const int PENTIUM = 66;

    private string $previousTemporaryDirectory = '';

    /** @return iterable<array<int, string>> */
    public static function provideTemporaryDirectory(): iterable
    {
        for ($i = 0; $i <= self::PENTIUM; $i++) {
            /**
             * Resolves: Parameter #1 $min (int<min, 4356>) of function random_int expects lower number than parameter #2 $max (int<1, max>).
             */
            yield [(string) random_int($i * $i, PHP_INT_MAX)];
        }
    }

    #[Test]
    public function recursiveDirectoryCreation(): void
    {
        self::assertFileExists($this->getTmpDir());
    }

    #[Test]
    #[DataProvider('provideTemporaryDirectory')]
    public function testTemporaryDirectoryAndGetFilesInDirectory(string $int): void
    {
        /** @var non-empty-string $sysTempDir */
        $sysTempDir = sys_get_temp_dir();

        self::assertStringStartsWith($sysTempDir, $this->getTmpDir());
        self::assertNotSame($this->getTmpDir(), $this->previousTemporaryDirectory);

        $dir = $this->getTmpDir() . $this->getRandomNameSpace() . DIRECTORY_SEPARATOR;
        mkdir($dir);

        for ($i = 0; $i < self::PENTIUM; $i++) {
            self::assertCount($i, $this->getFilesInDirectory($this->getTmpDir()), (string) $i);
            file_put_contents($dir . $i, $int);
        }

        self::assertCount(self::PENTIUM, $this->getFilesInDirectory($this->getTmpDir()));

        foreach ($this->getFilesInDirectory($this->getTmpDir()) as $file) {
            self::assertSame($int, file_get_contents($file));
        }
    }

    #[Test]
    #[DataProvider('provideTrueFalse')]
    public function trueOrFalse(bool $bool): void
    {
        self::assertCount(1, func_get_args());
    }

    #[Test]
    public function testTrueAndFalse(): void
    {
        self::assertSame(
            ['true' => [true], 'false' => [false]],
            [...self::provideTrueFalse()],
        );
    }

    #[Test]
    public function successGetSysTempDir(): void
    {
        self::assertDirectoryExists($this->getSysTempDir());
    }

    #[Test]
    public function successWaitUntilTheNextSecond(): void
    {
        $now = time();
        self::waitUntilTheNextSecond();
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

    #[Test]
    public function rmdirRemovesDirectoryContainingSymlinkToDirectory(): void
    {
        $root     = $this->getTmpDir();
        $includes = $root . 'reference' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
        $target   = $includes . 'target' . DIRECTORY_SEPARATOR;

        mkdir($target . 'nested', 0777, true);
        file_put_contents($target . 'nested' . DIRECTORY_SEPARATOR . 'Stub.mk', "x\n");

        $linkTarget = $target;
        if (PHP_OS_FAMILY === 'Windows') {
            $linkTarget = realpath($target);
        }

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (! @symlink($linkTarget, $includes . 'linked-target')) {
            self::markTestSkipped('Unable to create symlink on this platform');
        }

        $this->rmdir($includes);

        self::assertDirectoryDoesNotExist($includes);
    }
}
