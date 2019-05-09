SET @sName = 'bx_messenger';

-- SETTINGS

DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_max_parts_views', 'bx_messenger_allow_to_remove_messages', 'bx_messenger_remove_messages_immediately');
SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_parts_views', '10', @iCategId, '_bx_messenger_max_parts_views', 'digit', '', '', '', 15),
('bx_messenger_allow_to_remove_messages', 'on', @iCategId, '_bx_messenger_allow_to_remove_messages', 'checkbox', '', '', '', 16),
('bx_messenger_remove_messages_immediately', '', @iCategId, '_bx_messenger_remove_messages_immediately', 'checkbox', '', '', '', 17);

