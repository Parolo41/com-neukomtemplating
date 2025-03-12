ALTER TABLE `#__neukomtemplating_templates` MODIFY `fields` TEXT NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` MODIFY `url_parameters` TEXT NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` MODIFY `joined_tables` TEXT NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `contact_email_field` VARCHAR(50) NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `contact_display_name` VARCHAR(50) NOT NULL;
ALTER TABLE `#__neukomtemplating_templates` ADD `notification_trigger` VARCHAR(20) NOT NULL,
ALTER TABLE `#__neukomtemplating_templates` ADD `notification_recipients` TEXT NOT NULL,
