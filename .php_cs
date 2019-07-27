<?php declare(strict_types=1);

use WyriHaximus\TestUtilities\PhpCsFixerConfigFactory;

return (function ()
{
    $paths = [
        __DIR__ . DIRECTORY_SEPARATOR . 'src',
        __DIR__ . DIRECTORY_SEPARATOR . 'tests',
    ];

    return PhpCsFixerConfigFactory::create($paths);
})();
