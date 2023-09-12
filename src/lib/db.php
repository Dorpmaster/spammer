<?php

declare(strict_types=1);

require_once 'logger.php';

$db_connection = false;

function db_connect(): mysqli|bool
{
    return mysqli_connect(getenv('MYSQL_HOST'), getenv('MYSQL_USER'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
}

function db_query(string $sql): iterable
{
    global $db_connection;

    if (!$db_connection) {
        $db_connection = db_connect();
    }

    if ($result = mysqli_query($db_connection, $sql)) {
        while ($record = mysqli_fetch_assoc($result)) {
            yield $record;
        }
    }
}

function db_command(string $sql): mysqli_result | bool
{
    global $db_connection;

    if (!$db_connection) {
        $db_connection = db_connect();
    }

    return mysqli_query($db_connection, $sql);
}

function db_close(): void
{
    global $db_connection;

    if (!$db_connection) {
        return;
    }

    mysqli_close($db_connection);
}