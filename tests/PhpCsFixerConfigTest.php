<?php declare(strict_types=1);

namespace ApiClients\Tests\Tools\TestUtilities;

use ApiClients\Tools\TestUtilities\PhpCsFixerConfig;
use ApiClients\Tools\TestUtilities\TestCase;
use PhpCsFixer\Config;

final class PhpCsFixerConfigTest extends TestCase
{
    public function testCreate()
    {
        self::assertInstanceOf(Config::class, PhpCsFixerConfig::create());
    }
}
