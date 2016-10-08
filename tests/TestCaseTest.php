<?php declare(strict_types=1);

namespace ApiClients\Tests\Tools\TestUtilities;

use ApiClients\Tools\TestUtilities\TestCase;

final class TestCaseTest extends TestCase
{
    const PENTIUM = 66;

    /**
     * @var string
     */
    private $previousTemporaryDirectory = '';

    public function provideTemporaryDirectory()
    {
        for ($i = 0; $i <= self::PENTIUM; $i++) {
            yield [
                (string) mt_rand($i * $i, mt_getrandmax()),
            ];
        }
    }

    /**
     * @dataProvider provideTemporaryDirectory
     */
    public function testTemporaryDirectoryAndGetFilesInDirectory(string $int)
    {
        $this->assertNotSame($this->getTmpDir(), $this->previousTemporaryDirectory);

        $dir = $this->getTmpDir() . $this->getRandomNameSpace() . DIRECTORY_SEPARATOR;
        mkdir($dir);

        for ($i = 0; $i < self::PENTIUM; $i++) {
            $this->assertSame($i, count($this->getFilesInDirectory($this->getTmpDir())), $i);
            file_put_contents($dir . $i, $int);
        }

        $this->assertSame(self::PENTIUM, count($this->getFilesInDirectory($this->getTmpDir())));

        foreach ($this->getFilesInDirectory($this->getTmpDir()) as $file) {
            $this->assertSame($int, file_get_contents($file));
        }
    }
}
