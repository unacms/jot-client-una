SET @sName = 'bx_messenger';

DELETE FROM `sys_options` WHERE `name` = 'bx_messenger_show_friends';

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_show_friends', 'on', @iCategId, '_bx_messenger_show_friends', 'checkbox', '', '', '', 20);

UPDATE `sys_options` SET `order` = 21 WHERE `name` = 'bx_messenger_remove_messages_immediately' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 22 WHERE `name` = 'bx_messenger_use_embedly' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 23 WHERE `name` = 'bx_messenger_giphy_key' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 24 WHERE `name` = 'bx_messenger_giphy_type' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 25 WHERE `name` = 'bx_messenger_giphy_content_rating' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 26 WHERE `name` = 'bx_messenger_giphy_limit' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 27 WHERE `name` = 'bx_messenger_emoji_set' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 28 WHERE `name` = 'bx_messenger_reactions_size' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 29 WHERE `name` = 'bx_messenger_show_emoji_preview' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 30 WHERE `name` = 'bx_messenger_jitsi_enable' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 31 WHERE `name` = 'bx_messenger_jitsi_server' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 32 WHERE `name` = 'bx_messenger_jitsi_chat' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 33 WHERE `name` = 'bx_messenger_jitsi_sync' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 34 WHERE `name` = 'bx_messenger_jitsi_hide_info' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 35 WHERE `name` = 'bx_messenger_jitsi_enable_watermark' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 36 WHERE `name` = 'bx_messenger_jitsi_watermark_link' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 37 WHERE `name` = 'bx_messenger_jitsi_only_for_private' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 38 WHERE `name` = 'bx_messenger_jitsi_support_url' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 39 WHERE `name` = 'bx_messenger_disable_contact_privacy' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 40 WHERE `name` = 'bx_messenger_enable_mentions' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 41 WHERE `name` = 'bx_messenger_jwt_app_id' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 42 WHERE `name` = 'bx_messenger_jwt_app_secret' AND `category_id` = @iCategId;