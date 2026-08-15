<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SplFileInfo;
use stdClass;
use WyriHaximus\TestUtilities\TestCase;

use function file_put_contents;
use function mkdir;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

final class TestCaseProbeTest extends TestCase
{
    private function skipOnWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return;
        }

        self::markTestSkipped('Windows-only test exclusion');
    }

    /** @param list<mixed> $nodes */
    private function withRmdirIterator(array $nodes, callable $callback): void
    {
        $this->setRmdirIteratorOverride($nodes);

        try {
            $callback();
        } finally {
            $this->clearRmdirIteratorOverride();
        }
    }

    private function callGetTmpDir(): string
    {
        return $this->getTmpDir();
    }

    private function setTmpDirForTesting(string $tmpDir): void
    {
        $property = new ReflectionProperty(TestCase::class, 'tmpDir');
        $property->setValue($this, $tmpDir);
    }

    #[Test]
    public function getSysTempDirReturnsAbsolutePathOnLinux(): void
    {
        $this->skipOnWindows();

        self::assertStringStartsWith('/', $this->getSysTempDir());
    }

    #[Test]
    public function temporaryDirectoryIsCreatedUnderSystemTemp(): void
    {
        $this->skipOnWindows();

        /** @var non-empty-string $sysTempDir */
        $sysTempDir = sys_get_temp_dir();

        self::assertStringStartsWith($sysTempDir, $this->getTmpDir());
    }

    #[Test]
    public function resolveBaseTempDirectoryReturnsSystemTempOnUnix(): void
    {
        $this->skipOnWindows();

        $method = new ReflectionMethod(TestCase::class, 'resolveBaseTempDirectory');
        /** @var non-empty-string $result */
        $result = $method->invoke($this);

        self::assertSame(sys_get_temp_dir(), $result);
    }

    /** @return iterable<string, array{string}> */
    public static function provideInvalidTempDirectoryCandidates(): iterable
    {
        yield 'relative path' => ['6a7fa01d394e2/tmp'];
        yield 'windows prefix on unix' => ['C:\\t\\'];
        yield 'absolute path with backslash on unix' => ['/tmp\\sub'];
        yield 'empty string' => [''];
        yield 'current directory' => ['.'];
    }

    #[Test]
    #[DataProvider('provideInvalidTempDirectoryCandidates')]
    public function absoluteTempDirectoryRejectsInvalidCandidates(string $candidate): void
    {
        $this->skipOnWindows();

        $method = new ReflectionMethod(TestCase::class, 'absoluteTempDirectory');
        /** @var non-empty-string $result */
        $result = $method->invoke($this, $candidate);

        self::assertStringStartsWith('/', $result);
        self::assertSame(sys_get_temp_dir(), $result);
    }

    #[Test]
    public function absoluteTempDirectoryAcceptsValidAbsolutePath(): void
    {
        $this->skipOnWindows();

        $method = new ReflectionMethod(TestCase::class, 'absoluteTempDirectory');
        /** @var non-empty-string $result */
        $result = $method->invoke($this, '/tmp');

        self::assertSame('/tmp', $result);
    }

    #[Test]
    public function cleanUpSwallowsRmdirFailures(): void
    {
        $path = $this->getTmpDir() . 'not-a-directory';
        file_put_contents($path, 'x');

        $property = new ReflectionProperty(TestCase::class, 'baseTmpDir');
        $property->setValue($this, $path);

        $this->cleanUpTemporaryTestEnvironment();

        self::assertFileExists($path);
    }

    #[Test]
    public function rmdirThrowsWhenFileCanNotBeDeleted(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Error deleting file:');

        $path = $this->getTmpDir() . 'directory-as-file' . DIRECTORY_SEPARATOR;
        mkdir($path);

        $node = Mockery::mock(SplFileInfo::class);
        $node->shouldReceive('getPathname')->andReturn($path);
        $node->shouldReceive('isLink')->andReturn(false);
        $node->shouldReceive('isDir')->andReturn(false);

        $this->withRmdirIterator([$node], function (): void {
            $this->rmdir($this->getTmpDir() . 'unused' . DIRECTORY_SEPARATOR);
        });
    }

    #[Test]
    public function getTmpDirThrowsWhenDirectoryCanNotBeCreated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Error creating directory:');

        $file = $this->getTmpDir() . 'not-a-directory';
        file_put_contents($file, 'x');
        $this->setTmpDirForTesting($file . DIRECTORY_SEPARATOR . 'child' . DIRECTORY_SEPARATOR);

        $this->callGetTmpDir();
    }

    #[Test]
    public function rmdirRemovesStandaloneDirectoryUnderSystemTemp(): void
    {
        $dir = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'p-a-c-t-' .
            uniqid() .
            DIRECTORY_SEPARATOR;

        mkdir($dir);

        $this->rmdir($dir);

        self::assertDirectoryDoesNotExist($dir);
    }

    #[Test]
    public function rmdirSkipsNonSplFileInfoNodes(): void
    {
        $root = $this->getTmpDir() . 'skip-non-spl' . DIRECTORY_SEPARATOR;
        mkdir($root);

        $this->withRmdirIterator([new stdClass()], function () use ($root): void {
            $this->rmdir($root);
        });

        self::assertDirectoryDoesNotExist($root);
    }

    #[Test]
    public function rmdirRemovesSymlink(): void
    {
        $target = sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'p-a-c-t-' .
            uniqid();
        file_put_contents($target, 'x');
        $link = $this->getTmpDir() . 'symlink';

        /** @phpstan-ignore ergebnis.noErrorSuppression */
        if (! @symlink($target, $link)) {
            unlink($target);
            self::markTestSkipped('Unable to create symlink on this platform');
        }

        try {
            $this->rmdir($this->getTmpDir());

            self::assertFileDoesNotExist($link);
            self::assertFileExists($target);
        } finally {
            unlink($target);
        }
    }

    #[Test]
    public function rmdirRemovesDirectoryViaRmdirWhenMarkedAsLinkAndUnlinkFails(): void
    {
        $path = $this->getTmpDir() . 'link-as-dir' . DIRECTORY_SEPARATOR;
        mkdir($path);

        $node = Mockery::mock(SplFileInfo::class);
        $node->shouldReceive('getPathname')->andReturn($path);
        $node->shouldReceive('isLink')->andReturn(true);
        $node->shouldReceive('isDir')->andReturn(false);

        $root = $this->getTmpDir() . 'link-rmdir-parent' . DIRECTORY_SEPARATOR;
        mkdir($root);

        $this->withRmdirIterator([$node], function () use ($root): void {
            $this->rmdir($root);
        });

        self::assertDirectoryDoesNotExist($path);
    }

    #[Test]
    public function rmdirThrowsWhenLinkCanNotBeDeleted(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Error deleting link:');

        $path = $this->getTmpDir() . 'missing-link-target';

        $node = Mockery::mock(SplFileInfo::class);
        $node->shouldReceive('getPathname')->andReturn($path);
        $node->shouldReceive('isLink')->andReturn(true);
        $node->shouldReceive('isDir')->andReturn(false);

        $this->withRmdirIterator([$node], function (): void {
            $this->rmdir($this->getTmpDir() . 'unused' . DIRECTORY_SEPARATOR);
        });
    }

    #[Test]
    public function rmdirThrowsWhenNestedDirectoryCanNotBeRemoved(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Error deleting directory:');

        $path = $this->getTmpDir() . 'file-not-directory';
        file_put_contents($path, 'x');

        $node = Mockery::mock(SplFileInfo::class);
        $node->shouldReceive('getPathname')->andReturn($path);
        $node->shouldReceive('isLink')->andReturn(false);
        $node->shouldReceive('isDir')->andReturn(true);

        $this->withRmdirIterator([$node], function (): void {
            $this->rmdir($this->getTmpDir() . 'unused' . DIRECTORY_SEPARATOR);
        });
    }

    #[Test]
    public function rmdirThrowsWhenRootDirectoryCanNotBeRemoved(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Error deleting directory:');

        $path = $this->getTmpDir() . 'not-a-directory';
        file_put_contents($path, 'x');

        $this->withRmdirIterator([], function () use ($path): void {
            $this->rmdir($path);
        });
    }
}
