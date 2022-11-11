SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE =
        'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema collectme
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `collectme` DEFAULT CHARACTER SET utf8mb4;
USE `collectme`;

-- -----------------------------------------------------
-- Table `collectme`.`mails`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `collectme`.`mails`
(
    `uuid`               VARCHAR(36)                              NOT NULL,
    `groups_uuid`        VARCHAR(36)                              NOT NULL,
    `msg_key`            VARCHAR(45)                              NOT NULL,
    `unsubscribe_secret` VARCHAR(64)                              NOT NULL,
    `sent_at`            TIMESTAMP                                NULL,
    `trigger_obj_uuid`   VARCHAR(36)                              NULL,
    `trigger_obj_type`   ENUM ('signature', 'objective', 'group') NULL,
    `created_at`         TIMESTAMP                                NOT NULL DEFAULT NOW(),
    `updated_at`         TIMESTAMP                                NULL,
    `deleted_at`         TIMESTAMP                                NULL,
    PRIMARY KEY (`uuid`),
    UNIQUE INDEX `unsubscribe_secret_UNIQUE` (`unsubscribe_secret` ASC),
    INDEX `fk_mails_groups1_idx` (`groups_uuid` ASC),
    CONSTRAINT `fk_mails_groups1_idx`
        FOREIGN KEY (`groups_uuid`)
            REFERENCES `collectme`.`groups` (`uuid`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
)
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci
    ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `collectme`.`users`
-- -----------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS ADD_COL_IF_NOT_EXISTS$$
CREATE PROCEDURE ADD_COL_IF_NOT_EXISTS()
BEGIN
DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
-- Wrap alter table in ADD_COL_IF_NOT_EXISTS procedure to
-- prevent script from failing if the column exists.
-- See https://stackoverflow.com/a/39120133
ALTER TABLE `collectme`.`users`
    ADD `mail_permission` BOOLEAN NOT NULL DEFAULT TRUE AFTER `lang`;
END$$
CALL ADD_COL_IF_NOT_EXISTS()$$
DROP PROCEDURE ADD_COL_IF_NOT_EXISTS$$

DELIMITER ;

-- -----------------------------------------------------
-- Triggers
-- -----------------------------------------------------

DROP TRIGGER IF EXISTS `collectme`.`mails_BEFORE_INSERT`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`mails_BEFORE_INSERT`
    BEFORE INSERT
    ON `collectme`.`mails`
    FOR EACH ROW
BEGIN
    IF new.uuid IS NULL THEN
        SET new.uuid = uuid();
    END IF;

    SET new.updated_at = now();
END$$

DROP TRIGGER IF EXISTS `collectme`.`mails_BEFORE_UPDATE`;
DELIMITER $$
CREATE DEFINER = CURRENT_USER TRIGGER `collectme`.`mails_BEFORE_UPDATE`
    BEFORE UPDATE
    ON `collectme`.`mails`
    FOR EACH ROW
BEGIN
    SET new.updated_at = now();
END$$


DELIMITER ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;