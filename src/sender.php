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
    $mail_template = getenv('SENDER_MAIL_TEMPLATE') ?: '{username}, your subscription is expiring soon.';
    $from = getenv('SENDER_FROM_ADDRESS') ?: 'spammer@test.job';

    while ($item = queue_claim_item('sender')) {
        $item_id = (int)$item['id'];
        $email = $item['data']['email'];
        $username = $item['data']['username'];
        $id = (int)$item['data']['id'];
        $confirmed = (int)$item['data']['confirmed'];
        $checked = (int)$item['data']['checked'];
        $valid = (int)$item['data']['valid'];
        $expired = $item['data']['valid_date'];

        // Checking if email is not valid
        if ((!$confirmed && !$checked) || ($checked && !$valid)) {
            log_to_console(sprintf('Email %s is not checked yet. Skipping.', $email));
            queue_delete_item($item_id);
            $processed++;
            continue;
        }

        // Checking if the notification was already sent by the concurrent process
        $sql = <<<SQL
SELECT `subscription_id` 
FROM `log` 
WHERE 
  `subscription_id` = :id 
  AND `notified` = ':notified'
  AND DATEDIFF(`notified`, `created`) = 1;
SQL;
        $prepared_sql = strtr($sql, [
            ':id' => $id,
            ':notified' => $expired,
        ]);

        $log = null;
        foreach (db_query($prepared_sql) as $log) {
            break;
        }

        if (isset($log)) {
            log_to_console(sprintf('Subscription %d was already notified. Skipping.', $id));
            queue_delete_item($item_id);
            $processed++;
            continue;
        }

        // Sending a notification
        send_email($from, $email, strtr($mail_template, ['{username}' => $username]));

        // Setting a mark about notification
        $command = <<<SQL
INSERT INTO `log` SET `subscription_id` = :id, `notified` = ':notified' ON DUPLICATE KEY UPDATE `created` = CURRENT_TIMESTAMP;
SQL;
        $prepared_command = strtr($command, [
            ':notified' => $expired,
            ':id' => $id,
        ]);

        if (true === db_command($prepared_command)) {
            queue_delete_item($item_id);
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
    if ($processed > 0) {
        log_to_console('Releasing abandoned items.');
        // Clearing the queue. It can get a deadlock because concurrent execution.
        // But it does not affect business process.
        queue_release_abandoned_items();
    }

    db_close();
}

calculate_stat();

exit(0);