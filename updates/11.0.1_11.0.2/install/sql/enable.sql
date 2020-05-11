SET @sName = 'bx_messenger';

-- SETTINGS
DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_jitsi_support_url','bx_messenger_jitsi_only_for_private','bx_messenger_jitsi_enable','bx_messenger_jitsi_server','bx_messenger_jitsi_chat','bx_messenger_jitsi_sync','bx_messenger_jitsi_hide_info','bx_messenger_jitsi_enable_watermark','bx_messenger_jitsi_watermark_link');

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
SET @iOrderId = (SELECT MAX(`Order`) FROM `sys_options` WHERE `category_id` = @iCategId);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_jitsi_enable', 'on', @iCategId, '_bx_messenger_allow_to_use_jitsi', 'checkbox', '', '', '', @iOrderId + 1),
('bx_messenger_jitsi_server', 'meet.jit.si', @iCategId, '_bx_messenger_jitsi_server', 'digit', '', '', '', @iOrderId + 2),
('bx_messenger_jitsi_chat', '', @iCategId, '_bx_messenger_jitsi_chat_enable', 'checkbox', '', '', '', @iOrderId + 3),
('bx_messenger_jitsi_sync', '', @iCategId, '_bx_messenger_jitsi_chat_sync', 'checkbox', '', '', '', @iOrderId + 4),
('bx_messenger_jitsi_hide_info', '', @iCategId, '_bx_messenger_jitsi_hide_info', 'checkbox', '', '', '', @iOrderId + 5),
('bx_messenger_jitsi_enable_watermark', '', @iCategId, '_bx_messenger_jitsi_watermark', 'checkbox', '', '', '', @iOrderId + 6),
('bx_messenger_jitsi_watermark_link', '', @iCategId, '_bx_messenger_jitsi_watermark_link', 'digit', '', '', '', @iOrderId + 7),
('bx_messenger_jitsi_only_for_private', 'on', @iCategId, '_bx_messenger_jitsi_enable_only_for_private', 'checkbox', '', '', '', @iOrderId + 8),
('bx_messenger_jitsi_support_url', 'https://community.jitsi.org/', @iCategId, '_bx_messenger_jitsi_support_url', 'digit', '', '', '', @iOrderId + 9);

REPLACE INTO `sys_storage_mime_types` (`ext`, `mime_type`, `icon`, `icon_font`) VALUES
('x-matroska', 'video/x-matroska', '', '');