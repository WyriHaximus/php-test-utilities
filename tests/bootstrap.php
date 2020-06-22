<?php

declare(strict_types=1);

if (
    function_exists('xdebug_set_filter') &&
    defined('XDEBUG_FILTER_CODE_COVERAGE') &&
    defined('XDEBUG_PATH_WHITELIST')
) {
    xdebug_set_filter(
        XDEBUG_FILTER_CODE_COVERAGE,
        XDEBUG_PATH_WHITELIST,
        [
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src',
        ]
    );
}
