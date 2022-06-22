SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema collectme
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `collectme` DEFAULT CHARACTER SET utf8mb4;
USE `collectme`;

-- -----------------------------------------------------
-- Table `collectme`.`users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`users`
(
    `uuid`       VARCHAR(36)          NOT NULL,
    `email`      VARCHAR(120)         NOT NULL,
    `first_name` VARCHAR(45)          NOT NULL,
    `last_name`  VARCHAR(45)          NOT NULL,
    `lang`       ENUM ('d', 'f', 'e') NOT NULL,
    `source`     VARCHAR(255)         NULL,
    `created_at` TIMESTAMP            NOT NULL DEFAULT NOW(),
    `updated_at` TIMESTAMP            NULL,
    `deleted_at` TIMESTAMP            NULL,
    PRIMARY KEY (`uuid`),
    UNIQUE INDEX `email_UNIQUE` (`email` ASC)
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`causes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`causes`
(
    `uuid`       VARCHAR(36) NOT NULL,
    `name`       VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP   NOT NULL DEFAULT NOW(),
    `updated_at` TIMESTAMP   NULL,
    `deleted_at` TIMESTAMP   NULL,
    PRIMARY KEY (`uuid`),
    UNIQUE INDEX `name_UNIQUE` (`name` ASC)
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`groups`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`groups`
(
    `uuid`           VARCHAR(36)                     NOT NULL,
    `name`           VARCHAR(45)                     NULL,
    `type`           ENUM ('person', 'organization') NOT NULL,
    `causes_uuid`    VARCHAR(36)                     NOT NULL,
    `world_readable` TINYINT                         NOT NULL,
    `created_at`     TIMESTAMP                       NOT NULL DEFAULT NOW(),
    `updated_at`     TIMESTAMP                       NULL,
    `deleted_at`     TIMESTAMP                       NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_groups_causes1_idx` (`causes_uuid` ASC),
    UNIQUE INDEX `name_UNIQUE` (`name` ASC),
    CONSTRAINT `fk_groups_causes1`
        FOREIGN KEY (`causes_uuid`)
            REFERENCES `collectme`.`causes` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`objectives`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`objectives`
(
    `uuid`        VARCHAR(36)  NOT NULL,
    `objective`   INT          NOT NULL,
    `groups_uuid` VARCHAR(36)  NOT NULL,
    `source`      VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT NOW(),
    `updated_at`  TIMESTAMP    NULL,
    `deleted_at`  TIMESTAMP    NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_objectives_groups1_idx` (`groups_uuid` ASC),
    CONSTRAINT `fk_objectives_groups1`
        FOREIGN KEY (`groups_uuid`)
            REFERENCES `collectme`.`groups` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`users_causes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`users_causes`
(
    `uuid`        VARCHAR(36) NOT NULL,
    `users_uuid`  VARCHAR(36) NOT NULL,
    `causes_uuid` VARCHAR(36) NOT NULL,
    `created_at`  TIMESTAMP   NOT NULL DEFAULT NOW(),
    `updated_at`  TIMESTAMP   NULL,
    `deleted_at`  TIMESTAMP   NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_users_causes_users1_idx` (`users_uuid` ASC),
    INDEX `fk_users_causes_causes1_idx` (`causes_uuid` ASC),
    CONSTRAINT `fk_users_causes_users1`
        FOREIGN KEY (`users_uuid`)
            REFERENCES `collectme`.`users` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_users_causes_causes1`
        FOREIGN KEY (`causes_uuid`)
            REFERENCES `collectme`.`causes` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`sessions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`sessions`
(
    `uuid`              VARCHAR(36) NOT NULL,
    `users_uuid`        VARCHAR(36) NOT NULL,
    `login_counter`     INT         NOT NULL DEFAULT 0,
    `last_login`        TIMESTAMP   NULL,
    `activation_secret` VARCHAR(64) NOT NULL,
    `session_secret`    VARCHAR(64) NOT NULL,
    `activated_at`      TIMESTAMP   NULL,
    `closed_at`         TIMESTAMP   NULL,
    `created_at`        TIMESTAMP   NOT NULL DEFAULT NOW(),
    `updated_at`        TIMESTAMP   NULL,
    `deleted_at`        TIMESTAMP   NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_sessions_users1_idx` (`users_uuid` ASC),
    UNIQUE INDEX `activation_secret_UNIQUE` (`activation_secret` ASC),
    UNIQUE INDEX `session_secret_UNIQUE` (`session_secret` ASC),
    CONSTRAINT `fk_sessions_users1`
        FOREIGN KEY (`users_uuid`)
            REFERENCES `collectme`.`users` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`roles`
(
    `uuid`        VARCHAR(36)      NOT NULL,
    `users_uuid`  VARCHAR(36)      NOT NULL,
    `groups_uuid` VARCHAR(36)      NOT NULL,
    `permission`  ENUM ('r', 'rw') NOT NULL,
    `created_at`  TIMESTAMP        NOT NULL DEFAULT NOW(),
    `updated_at`  TIMESTAMP        NULL,
    `deleted_at`  TIMESTAMP        NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_users_groups_users1_idx` (`users_uuid` ASC),
    INDEX `fk_users_groups_groups1_idx` (`groups_uuid` ASC),
    CONSTRAINT `fk_users_groups_users1`
        FOREIGN KEY (`users_uuid`)
            REFERENCES `collectme`.`users` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_users_groups_groups1`
        FOREIGN KEY (`groups_uuid`)
            REFERENCES `collectme`.`groups` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`activity_logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`activity_logs`
(
    `uuid`        VARCHAR(36)                                                                                                       NOT NULL,
    `type`        ENUM ('pledge', 'personal signature', 'organization signature', 'personal goal achieved', 'personal goal raised') NOT NULL,
    `count`       INT                                                                                                               NOT NULL,
    `causes_uuid` VARCHAR(36)                                                                                                       NOT NULL,
    `groups_uuid` VARCHAR(36)                                                                                                       NULL,
    `users_uuid`  VARCHAR(36)                                                                                                       NULL,
    `created_at`  TIMESTAMP                                                                                                         NOT NULL DEFAULT NOW(),
    `updated_at`  TIMESTAMP                                                                                                         NULL,
    `deleted_at`  TIMESTAMP                                                                                                         NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_activity_log_causes1_idx` (`causes_uuid` ASC),
    INDEX `fk_activity_logs_groups1_idx` (`groups_uuid` ASC),
    INDEX `fk_activity_logs_users1_idx` (`users_uuid` ASC),
    CONSTRAINT `fk_activity_log_causes1`
        FOREIGN KEY (`causes_uuid`)
            REFERENCES `collectme`.`causes` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_activity_logs_groups1`
        FOREIGN KEY (`groups_uuid`)
            REFERENCES `collectme`.`groups` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk_activity_logs_users1`
        FOREIGN KEY (`users_uuid`)
            REFERENCES `collectme`.`users` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`signatures`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`signatures`
(
    `uuid`                     VARCHAR(36) NOT NULL,
    `collected_by_groups_uuid` VARCHAR(36) NOT NULL,
    `entered_by_users_uuid`    VARCHAR(36) NULL,
    `count`                    INT         NOT NULL,
    `activity_logs_uuid`       VARCHAR(36) NULL,
    `created_at`               TIMESTAMP   NOT NULL DEFAULT NOW(),
    `updated_at`               TIMESTAMP   NULL,
    `deleted_at`               TIMESTAMP   NULL,
    PRIMARY KEY (`uuid`),
    INDEX `fk_signatures_groups1_idx` (`collected_by_groups_uuid` ASC),
    INDEX `fk_signatures_users1_idx` (`entered_by_users_uuid` ASC),
    INDEX `fk_signatures_activity_logs1_idx` (`activity_logs_uuid` ASC),
    CONSTRAINT `fk_signatures_groups1`
        FOREIGN KEY (`collected_by_groups_uuid`)
            REFERENCES `collectme`.`groups` (`uuid`)
            ON DELETE NO ACTION
            ON UPDATE CASCADE,
    CONSTRAINT `fk_signatures_users1`
        FOREIGN KEY (`entered_by_users_uuid`)
            REFERENCES `collectme`.`users` (`uuid`)
            ON DELETE SET NULL
            ON UPDATE CASCADE,
    CONSTRAINT `fk_signatures_activity_logs1`
        FOREIGN KEY (`activity_logs_uuid`)
            REFERENCES `collectme`.`activity_logs` (`uuid`)
            ON DELETE SET NULL
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `collectme`.`login_tokens`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`login_tokens`
(
    `uuid`        VARCHAR(36)          NOT NULL,
    `token`       VARCHAR(64)          NOT NULL,
    `email`       VARCHAR(120)         NOT NULL,
    `first_name`  VARCHAR(45)          NOT NULL,
    `last_name`   VARCHAR(45)          NOT NULL,
    `lang`        ENUM ('d', 'f', 'e') NOT NULL,
    `valid_until` TIMESTAMP            NOT NULL,
    `created_at`  TIMESTAMP            NOT NULL DEFAULT NOW(),
    `updated_at`  TIMESTAMP            NULL,
    `deleted_at`  TIMESTAMP            NULL,
    PRIMARY KEY (`uuid`),
    INDEX `token_idx` (`token` ASC)
)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;


-- -----------------------------------------------------
-- Triggers
-- -----------------------------------------------------

DROP TRIGGER IF EXISTS `collectme`.`users_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`users_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`users`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`users_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`users_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`users`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`causes_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`causes_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`causes`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`causes_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`causes_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`causes`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`groups_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`groups_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`groups`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`groups_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`groups_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`groups`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`objectives_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`objectives_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`objectives`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`objectives_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`objectives_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`objectives`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`users_causes_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`users_causes_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`users_causes`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`users_causes_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`users_causes_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`users_causes`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`sessions_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`sessions_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`sessions`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`sessions_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`sessions_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`sessions`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`roles_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`roles_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`roles`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`roles_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`roles_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`roles`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`activity_logs_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`activity_logs_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`activity_logs`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`activity_logs_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`activity_logs_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`activity_logs`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`signatures_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`signatures_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`signatures`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`signatures_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`signatures_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`signatures`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`login_tokens_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`login_tokens_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`login_tokens`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`login_tokens_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`login_tokens_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`login_tokens`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$


DELIMITER ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
