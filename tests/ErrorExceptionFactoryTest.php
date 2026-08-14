<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\TestUtilities;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use WyriHaximus\TestUtilities\ErrorExceptionFactory;
use WyriHaximus\TestUtilities\TestCase;

use function file_get_contents;

final class ErrorExceptionFactoryTest extends TestCase
{
    #[Test]
    public function createWithoutPreviousError(): void
    {
        $exception = ErrorExceptionFactory::create('Something went wrong');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertSame('Something went wrong', $exception->getMessage());
    }

    #[Test]
    public function createWithPreviousError(): void
    {
        /** @phpstan-ignore ergebnis.noErrorSuppression */
        @file_get_contents($this->getTmpDir() . 'does-not-exist');

        $exception = ErrorExceptionFactory::create('Something went wrong');

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertStringStartsWith('Something went wrong with error: ', $exception->getMessage());
    }
}
