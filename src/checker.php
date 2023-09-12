<?php

declare(strict_types=1);

require_once 'lib/stat.php';
require_once 'lib/logger.php';
require_once 'lib/db.php';
require_once 'lib/util.php';
require_once 'lib/queue.php';

const CHECKER_MAX_PROCESSED_ITEMS = 10000;

$processed = 0;
try {
    while ($item = queue_claim_item('checker', 90)) {
        $item_id = (int)$item['id'];
        $email = $item['data']['email'];
        $id = (int)$item['data']['id'];
        $checked = (int)$item['data']['checked'];

        if (1 === $checked) {
            log_to_console(sprintf('Email %s was already checked.', $email));
            queue_delete_item($item_id);
            $processed++;
            continue;
        }

        // Checking if the check was already processed by the concurrent process
        $sql = <<<SQL
SELECT `checked` FROM `subscriptions` WHERE `id` = :id;
SQL;
        $prepared_sql = strtr($sql, [
            ':id' => $id,
        ]);

        $check = null;
        foreach (db_query($prepared_sql) as $check) {
            break;
        }

        if (!empty($check['checked'])) {
            log_to_console(sprintf('Email %s was already checked. Skipping.', $email));
            queue_delete_item($item_id);
            $processed++;
            continue;
        }

        // Checking the email
        $command = <<<SQL
UPDATE `subscriptions` SET `checked` = 1, `valid` = :valid WHERE `id` = :id;
SQL;
        $is_valid = check_email($email);
        $prepared_command = strtr($command, [
            ':valid' => $is_valid,
            ':id' => $id,
        ]);

        if (true === db_command($prepared_command)) {
            queue_delete_item($item_id);

            if ($is_valid) {
                log_to_console('Put the subscription to the "sender" queue.');

                $subscription = $item['data'];
                $subscription['checked'] = 1;
                $subscription['valid'] = $is_valid;
                queue_create_item('sender', $subscription);
            }
        } else {
            queue_release_item($item_id);
        }

        $processed++;

        // Stopping the process to avoid memory leak
        // The process will restart automatically with Docker
        if ($processed > CHECKER_MAX_PROCESSED_ITEMS) {
            log_to_console('Reached max number of processed items. Stopping.');
            calculate_stat();
            exit(0);
        }
    }
} catch (Throwable $exception) {
    log_to_console($exception->getMessage(), LOG_LEVEL_ERROR);
} finally {
    db_close();
}

calculate_stat();

exit(0);