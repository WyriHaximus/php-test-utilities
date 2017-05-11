<?php declare(strict_types=1);

use ApiClients\Tools\TestUtilities\PhpCsFixerConfig;

return PhpCsFixerConfig::create()->setFinder(
    PhpCsFixer\Finder::create()
    ->in(__DIR__)
)->setUsingCache(false);
