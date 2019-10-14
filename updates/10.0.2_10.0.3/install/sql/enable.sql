SET @sName = 'bx_messenger';

-- SETTINGS

DELETE FROM `sys_options` WHERE `name` LIKE 'bx_messenger_giphy_%';
SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_giphy_key', '', @iCategId, '_bx_messenger_giphy_api_key', 'digit', '', '', '', 19),
('bx_messenger_giphy_type', 'gifs', @iCategId, '_bx_messenger_giphy_type', 'select', '', '', 'gifs,stickers', 20),
('bx_messenger_giphy_content_rating', 'g', @iCategId, '_bx_messenger_giphy_content_rating', 'select', '', '', 'g,pg,pg-13,r', 21),
('bx_messenger_giphy_limit', '20', @iCategId, '_bx_messenger_giphy_limit', 'digit', '', '', '', 22);