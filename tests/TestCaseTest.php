<?php declare(strict_types=1);

namespace ApiClients\Tests\Tools\TestUtilities;

use ApiClients\Tools\TestUtilities\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use function React\Promise\resolve;
use function React\Promise\Timer\timeout;
use React\Promise\Timer\TimeoutException;

final class TestCaseTest extends TestCase
{
    const PENTIUM = 66;

    /**
     * @var string
     */
    private $previousTemporaryDirectory = '';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function provideTemporaryDirectory()
    {
        for ($i = 0; $i <= self::PENTIUM; $i++) {
            yield [
                (string) random_int($i * $i, PHP_INT_MAX),
            ];
        }
    }

    public function provideEventLoop()
    {
        yield [null];
        yield [Factory::create()];
        yield [new StreamSelectLoop()];
    }

    public function testRecursiveDirectoryCreation()
    {
        static::assertFileExists($this->getTmpDir());
    }

    /**
     * @dataProvider provideTemporaryDirectory
     */
    public function testTemporaryDirectoryAndGetFilesInDirectory(string $int)
    {
        static::assertNotSame($this->getTmpDir(), $this->previousTemporaryDirectory);

        $dir = $this->getTmpDir() . $this->getRandomNameSpace() . DIRECTORY_SEPARATOR;
        mkdir($dir);

        for ($i = 0; $i < self::PENTIUM; $i++) {
            static::assertCount($i, $this->getFilesInDirectory($this->getTmpDir()), (string)$i);
            file_put_contents($dir . $i, $int);
        }

        static::assertCount(self::PENTIUM, $this->getFilesInDirectory($this->getTmpDir()));

        foreach ($this->getFilesInDirectory($this->getTmpDir()) as $file) {
            static::assertSame($int, file_get_contents($file));
        }
    }

    /**
     * @dataProvider provideEventLoop
     */
    public function testAwait(LoopInterface $loop = null)
    {
        $value = time();
        static::assertSame($value, $this->await(resolve($value), $loop));
    }

    /**
     * @dataProvider provideEventLoop
     */
    public function testAwaitAll(LoopInterface $loop = null)
    {
        $value = time();
        static::assertSame([$value, $value], $this->awaitAll([resolve($value), resolve($value)], $loop));
    }

    /**
     * @dataProvider provideEventLoop
     */
    public function testAwaitAny(LoopInterface $loop = null)
    {
        $value = time();
        static::assertSame($value, $this->awaitAny([resolve($value), resolve($value)], $loop));
    }

    /**
     * @dataProvider provideTrueFalse
     */
    public function testTrueFalse(bool $bool)
    {
        static::assertInternalType('bool', $bool);
    }

    /**
     * @dataProvider provideEventLoop
     */
    public function testAwaitTimeout(LoopInterface $loop = null)
    {
        self::expectException(TimeoutException::class);

        $this->await((new Deferred())->promise(), $loop, 0.1);
    }

    public function testGetSysTempDir()
    {
        self::assertFileExists($this->getSysTempDir());
    }
}
