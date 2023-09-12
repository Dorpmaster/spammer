<?php

declare(strict_types=1);

const LOG_LEVEL_ERROR = 0;
const LOG_LEVEL_DEBUG = 10;

function log_to_console(string $message, int $log_level = LOG_LEVEL_DEBUG): void
{
    if (defined('LOG_LEVEL') && $log_level > constant('LOG_LEVEL')) {
        return;
    }

    $level = match ($log_level) {
        LOG_LEVEL_ERROR => 'ERROR',
        LOG_LEVEL_DEBUG => 'DEBUG',
        default => 'INFO',
    };
    echo sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), $level, $message) . PHP_EOL;
}
