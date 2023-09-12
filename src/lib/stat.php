<?php

declare(strict_types=1);

require_once 'logger.php';

$stat_started_at = microtime(true);

function calculate_stat(): void
{
    global $stat_started_at;

    // Execution time
    $duration = round(microtime(true) - $stat_started_at);
    log_to_console(sprintf('Execution time: %d sec.', $duration));

    // Memory usage
    $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $bytes = memory_get_peak_usage(true);
    $memory_usage = round($bytes / pow(1000, ($i = floor(log($bytes, 1000)))), 2) . ' ' . ($unit[$i] ?? 'B');
    log_to_console(sprintf('Memory usage: %s', $memory_usage));
}
