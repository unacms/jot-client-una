SET @sName = 'bx_messenger';


-- SETTINGS
SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
DELETE FROM `sys_options` WHERE `name`='bx_messenger_max_video_length_minutes';
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_video_length_minutes', '5', @iCategId, '_bx_messenger_max_video_file_size', 'digit', '', '', '', 13);
