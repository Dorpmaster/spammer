<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once 'lib/logger.php';
require_once 'lib/db.php';
require_once 'lib/queue.php';
require_once 'lib/stat.php';

log_to_console('Starting to process subscriptions.');

/**
 * Provides subscriptions that will expire after a certain number of days.
 * @param int $days
 * @param int $chunk_size
 * @return iterable
 */
function get_subscriptions_over_the_days(int $days = 1, int $chunk_size = 1000): iterable
{
    $offset = 0;
    $days = ($days > 0) ? $days : 1;
    $chunk_size = ($chunk_size > 0) ? $chunk_size : 1000;

    $sql = <<<SQL
SELECT `id`, `username`, `email`, DATE(FROM_UNIXTIME(`validts`)) AS valid_date, `confirmed`, `checked`, `valid`
FROM subscriptions
  LEFT JOIN `log` ON `log`.`subscription_id` = `subscriptions`.`id`
WHERE 
  DATE(FROM_UNIXTIME(`validts`)) = ADDDATE(CURDATE(), :days)
  AND `id` > :offset
  AND (
    `log`.`subscription_id` IS NULL
    OR (`log`.`notified` = ADDDATE(CURDATE(), :days) AND DATEDIFF(`log`.`notified`, `log`.`created`) > :days)
  )
LIMIT :limit;
SQL;

    do {
        $counter = 0;
        $prepared_sql = strtr($sql, [':days' => $days, ':limit' => $chunk_size, ':offset' => $offset]);
        foreach (db_query($prepared_sql) as $item) {
            yield $item;
            $counter++;
            $offset = $item['id'];
        }
    } while ($counter > 0);
}

function spammer_process(iterable $subscriptions): void
{
    $total = $to_send = $to_check = $invalid = 0;
    foreach ($subscriptions as $item) {
        if (!empty($item['confirmed'])) {
            queue_create_item('sender', $item);
            $to_send++;
        } elseif (!empty($item['checked']) && !empty($item['valid'])) {
            queue_create_item('sender', $item);
            $to_send++;
        } elseif (empty($item['checked'])) {
            queue_create_item('checker', $item);
            $to_check++;
        } else {
            $invalid++;
        }

        $total++;

        if (($total % 10000) === 0) {
            log_to_console(sprintf('Processed %d items.', $total));
        }
    }

    log_to_console(sprintf('Processed %d items.', $total));
    log_to_console(sprintf('Total processed items: %d.', $total));
    log_to_console(sprintf('To send: %d, to check: %d , invalid email (skipped): %d.', $to_send, $to_check, $invalid));
}

try {
    log_to_console(sprintf('Getting subscriptions on %s (tomorrow)', date('Y-m-d', strtotime('tomorrow'))));
    spammer_process(get_subscriptions_over_the_days());

    log_to_console(sprintf('Getting subscriptions on %s (+3 days)', date('Y-m-d', strtotime('+3 days'))));
    spammer_process(get_subscriptions_over_the_days(3));
} catch (Throwable $exception) {
    log_to_console($exception->getMessage(), LOG_LEVEL_ERROR);
} finally {
    db_close();
}

calculate_stat();

exit(0);
