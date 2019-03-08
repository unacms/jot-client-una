SET @sName = 'bx_messenger';

-- SETTINGS
SET @iCategoryId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_ntfs_number', '5', @iCategoryId, '_bx_messenger_max_ntfs_number', 'digit', '', '', '', 14);
