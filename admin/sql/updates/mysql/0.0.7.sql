DROP TABLE IF EXISTS `#__neukomtemplating_templates`;

CREATE TABLE `#__neukomtemplating_templates` ( 
    `id` SERIAL NOT NULL, 
    `name` VARCHAR(200) NOT NULL, 
    PRIMARY KEY (`id`)
) ENGINE = InnoDB; 