CREATE TABLE `subscriptions`
(
    `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`  VARCHAR(255) NOT NULL,
    `email`     VARCHAR(50)  NOT NULL,
    `validts`   INT          NOT NULL DEFAULT 0,
    `confirmed` BOOL         NOT NULL DEFAULT 0,
    `checked`   BOOL         NOT NULL DEFAULT 0,
    `valid`     BOOL         NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=INNODB;

SET
cte_max_recursion_depth = 5001000;

INSERT INTO `subscriptions`(`username`, `email`, `validts`, `confirmed`)
SELECT CONCAT(
               ELT(0.5 + RAND() * 10, 'Wade', 'Dave', 'Seth', 'Ivan', 'Riley', 'Gilbert', 'Jorge', 'Dan', 'Brian',
                   'Roberto'),
               ' ',
               ELT(0.5 + RAND() * 10, 'Smith', 'Jones', 'Williams', 'Taylor', 'Brown', 'Davies', 'Evans', 'Thomas',
                   'Wilson', 'Johnson')
           ),
       CONCAT(MD5(UUID()), '@example.local'), /* Random email */
       IF(RAND() >= 0.2, 0, UNIX_TIMESTAMP(ADDDATE(DATE(NOW()), 1 + RAND() * 10))), /* 80% of users have no a subscription */
       RAND() <= 0.15 /* 15% of users confirmed their email */
FROM (WITH RECURSIVE seq AS (SELECT 1 AS v UNION ALL SELECT v + 1 FROM seq WHERE v < 5000500)
      SELECT v
      FROM seq) gen;

CREATE INDEX `subscriptions_date_idx` USING BTREE ON `subscriptions` ((DATE(FROM_UNIXTIME(`validts`))) DESC);

CREATE TABLE `queue`
(
    `id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue`   VARCHAR(50) NOT NULL,
    `data`    BLOB        NOT NULL,
    `expire`  INT         NOT NULL DEFAULT 0,
    `created` INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=INNODB;

CREATE INDEX `queue_queue_created_idx` USING BTREE ON `queue` (`queue`, `created` ASC);
CREATE INDEX `queue_expire_idx` USING BTREE ON `queue` (`expire`);

CREATE TABLE `log`
(
    `subscription_id` BIGINT UNSIGNED NOT NULL,
    `notified`        DATE      NOT NULL,
    `created`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`subscription_id`, `notified`),
    FOREIGN KEY (`subscription_id`)
        REFERENCES `subscriptions` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=INNODB;

