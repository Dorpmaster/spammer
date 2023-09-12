<?php

declare(strict_types=1);

require_once 'logger.php';

function check_email(string $email): int
{
    log_to_console(sprintf('Checking email: %s.', $email));
    $delay = rand(1, 60);

    log_to_console(sprintf('Check will go on %d sec.', $delay));
    sleep($delay);

    $is_valid = (($delay % 10) === 0);
    log_to_console(sprintf('Email %s is %s.', $email, $is_valid ? 'valid' : 'invalid'));

    return (int)$is_valid;
}

function send_email(string $from, string $to, string $text): void
{
    log_to_console(sprintf('Sending notification to %s: %s.', $to, $text));
    $delay = rand(1, 10);

    log_to_console(sprintf('Process will go on %d sec.', $delay));
    sleep($delay);

    log_to_console('Email was successfully sent.');
}
