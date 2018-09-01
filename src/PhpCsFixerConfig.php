<?php declare(strict_types=1);

namespace ApiClients\Tools\TestUtilities;

use ApiClients\Tools\CsFixerConfig\PhpCsFixerConfig as CSFC;
use PhpCsFixer\Config;

final class PhpCsFixerConfig
{
    /**
     * @return Config
     * @deprecated use ApiClients\Tools\CsFixerConfig\PhpCsFixerConfig directly
     */
    public static function create(array $extraRules = []): Config
    {
        return CSFC::create($extraRules);
    }
}
