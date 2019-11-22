SET @sName = 'bx_messenger';

-- SETTINGS

UPDATE `sys_options` SET `order` = `order` + 1 WHERE `order` > 15;

DELETE FROM `sys_options` WHERE `name` LIKE 'bx_messenger_max_drop_down_select';

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_drop_down_select', '5', @iCategId, '_bx_messenger_max_drop_down_select', 'digit', '', '', '', 16);