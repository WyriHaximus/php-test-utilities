<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities;

use RuntimeException;

use function error_get_last;
use function is_array;

final class ErrorExceptionFactory
{
    public static function create(string $message): RuntimeException
    {
        $error = error_get_last();

        return new RuntimeException($message . (is_array($error) ? ' with error: ' . $error['message'] : ''));
    }
}
