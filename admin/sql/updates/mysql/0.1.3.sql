ALTER TABLE `#__neukomtemplating_templates` ADD `header` TEXT NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `footer` TEXT NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `tablename` VARCHAR(40) NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `fields` VARCHAR(200) NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `condition` VARCHAR(500) NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `show_detail_page` BOOLEAN;
ALTER TABLE `#__neukomtemplating_templates` ADD `allow_create` BOOLEAN;
ALTER TABLE `#__neukomtemplating_templates` ADD `allow_edit` BOOLEAN;