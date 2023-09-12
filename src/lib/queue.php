<?php

declare(strict_types=1);

require_once 'db.php';

function queue_create_item(string $queue, mixed $data): mysqli_result|bool
{
    global $db_connection;

    if (!$db_connection) {
        $db_connection = db_connect();
    }

    $sql = 'INSERT INTO `queue` (`queue`, `data`, `created`) VALUES (\':name\', \':data\', :created)';
    $prepared_sql = strtr($sql, [
        ':name' => mysqli_real_escape_string($db_connection, $queue),
        ':data' => mysqli_real_escape_string($db_connection, serialize($data)),
        ':created' => time(),
    ]);

    return db_command($prepared_sql);
}

function queue_number_of_items(string $queue): int
{
    global $db_connection;

    if (!$db_connection) {
        $db_connection = db_connect();
    }

    $sql = 'SELECT COUNT(`id`) AS count FROM `queue` WHERE `queue` = \':name\'';
    $prepared_sql = strtr($sql, [
        ':name' => mysqli_real_escape_string($db_connection, $queue),
    ]);

    foreach (db_query($prepared_sql) as $data) {
        return isset($data['count']) ? (int)$data['count'] : 0;
    }

    return 0;
}

function queue_claim_item(string $queue, int $lease_time = 30): false|array
{
    global $db_connection;

    if (!$db_connection) {
        $db_connection = db_connect();
    }

    $sql = <<<SQL
SELECT `data`, `id` FROM `queue` WHERE `expire` = 0 AND `queue` = ':name' ORDER BY `created`, `id` ASC LIMIT 1;
SQL;
    $prepared_sql = strtr($sql, [
        ':name' => mysqli_real_escape_string($db_connection, $queue),
    ]);

    while (TRUE) {
        $item = null;
        foreach (db_query($prepared_sql) as $item) {
            break;
        }

        if ($item) {
            $command = <<<SQL
UPDATE `queue` SET `expire` = :expire WHERE `id` = :id AND `expire` = 0;
SQL;
            $prepared_command = strtr($command, [
                ':expire' => time() + $lease_time,
                ':id' => $item['id'],
            ]);

            if (true === db_command($prepared_command)) {
                return [
                    'id' => $item['id'],
                    'data' => unserialize($item['data'])
                ];
            }
        } else {
            return FALSE;
        }
    }
}

function queue_release_item(int $id): void
{
    $command = <<<SQL
UPDATE `queue` SET `expire` = 0 WHERE `id` = :id;
SQL;
    $prepared_command = strtr($command, [
        ':id' => $id,
    ]);

    db_command($prepared_command);
}

function queue_delete_item(int $id): void
{
    $command = <<<SQL
DELETE FROM `queue` WHERE `id` = :id;
SQL;
    $prepared_command = strtr($command, [
        ':id' => $id,
    ]);

    db_command($prepared_command);
}


function queue_release_abandoned_items(): void
{
    $command = <<<SQL
UPDATE `queue` SET `expire` = 0 WHERE `expire` > 0 AND `expire` < UNIX_TIMESTAMP();
SQL;

    db_command($command);
}
