DROP TABLE IF EXISTS `#__neukomtemplating_templates`;

CREATE TABLE `#__neukomtemplating_templates` ( 
    `id` SERIAL NOT NULL, 
    `name` VARCHAR(50) NOT NULL,
    `template` TEXT NOT NULL,
    `header` TEXT NOT NULL,
    `footer` TEXT NOT NULL,
    `detail_template` TEXT NOT NULL,
    `tablename` VARCHAR(40) NOT NULL,
    `fields` VARCHAR(2000) NOT NULL,
    `condition` VARCHAR(500) NOT NULL,
    `show_detail_page` BOOLEAN,
    `allow_create` BOOLEAN,
    `allow_edit` BOOLEAN,
    `access` INT NOT NULL DEFAULT 1,
    `joined_tables` VARCHAR(500),
    `id_field_name` VARCHAR(40) NOT NULL,
    `sorting` VARCHAR(80) NOT NULL,
    `limit` INT,
    `user_id_link_field` VARCHAR(40) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB; 