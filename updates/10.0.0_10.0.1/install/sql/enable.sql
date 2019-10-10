SET @sName = 'bx_messenger';

-- SETTINGS

DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_use_embedly');
SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_use_embedly', 'on', @iCategId, '_bx_messenger_use_embedly', 'checkbox', '', '', '', 18);
