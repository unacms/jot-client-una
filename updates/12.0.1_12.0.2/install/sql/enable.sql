SET @sName = 'bx_messenger';

-- SETTINGS
DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_max_lots_number', 'bx_messenger_enable_joined_organizations', 'bx_messenger_jwt_app_id', 'bx_messenger_jwt_app_secret');

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);

UPDATE `sys_options` SET `order` = 1  WHERE `name` = 'bx_messenger_max_symbols_number' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 2  WHERE `name` = 'bx_messenger_max_symbols_brief_jot' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 3  WHERE `name` = 'bx_messenger_max_jot_number_default' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 4  WHERE `name` = 'bx_messenger_max_jot_number_in_history' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 6  WHERE `name` = 'bx_messenger_is_push_enabled' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 7  WHERE `name` = 'bx_messenger_push_app_id' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 8  WHERE `name` = 'bx_messenger_push_rest_api' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 9  WHERE `name` = 'bx_messenger_push_short_name' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 10 WHERE `name` = 'bx_messenger_push_safari_id' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 11 WHERE `name` = 'bx_messenger_server_url' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 12 WHERE `name` = 'bx_messenger_max_files_send' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 13 WHERE `name` = 'bx_messenger_max_video_length_minutes' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 14 WHERE `name` = 'bx_messenger_max_ntfs_number' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 15 WHERE `name` = 'bx_messenger_max_parts_views' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 16 WHERE `name` = 'bx_messenger_max_drop_down_select' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 17 WHERE `name` = 'bx_messenger_allow_to_remove_messages' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 18 WHERE `name` = 'bx_messenger_allow_to_moderate_messages' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 20 WHERE `name` = 'bx_messenger_remove_messages_immediately' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 21 WHERE `name` = 'bx_messenger_use_embedly' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 22 WHERE `name` = 'bx_messenger_giphy_key' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 23 WHERE `name` = 'bx_messenger_giphy_type' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 24 WHERE `name` = 'bx_messenger_giphy_content_rating' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 25 WHERE `name` = 'bx_messenger_giphy_limit' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 26 WHERE `name` = 'bx_messenger_emoji_set' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 27 WHERE `name` = 'bx_messenger_reactions_size' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 28 WHERE `name` = 'bx_messenger_show_emoji_preview' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 29 WHERE `name` = 'bx_messenger_jitsi_enable' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 30 WHERE `name` = 'bx_messenger_jitsi_server' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 31 WHERE `name` = 'bx_messenger_jitsi_chat' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 32 WHERE `name` = 'bx_messenger_jitsi_sync' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 33 WHERE `name` = 'bx_messenger_jitsi_hide_info' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 34 WHERE `name` = 'bx_messenger_jitsi_enable_watermark' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 35 WHERE `name` = 'bx_messenger_jitsi_watermark_link' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 36 WHERE `name` = 'bx_messenger_jitsi_only_for_private' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 37 WHERE `name` = 'bx_messenger_jitsi_support_url' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 38 WHERE `name` = 'bx_messenger_disable_contact_privacy' AND `category_id` = @iCategId;
UPDATE `sys_options` SET `order` = 39 WHERE `name` = 'bx_messenger_enable_mentions' AND `category_id` = @iCategId;

INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_lots_number', 15, @iCategId, '_bx_messenger_max_max_lots_number', 'digit', '', '', '', 5),
('bx_messenger_enable_joined_organizations', '', @iCategId, '_bx_messenger_enable_joined_organizations', 'checkbox', '', '', '', 19),
('bx_messenger_jwt_app_id', '', @iCategId, '_bx_messenger_jwt_app_id', 'digit', '', '', '', 40),
('bx_messenger_jwt_app_secret', '', @iCategId, '_bx_messenger_jwt_app_secret', 'digit', '', '', '', 41);

UPDATE `sys_pages_blocks` SET `designbox_id`=11 WHERE `object` != 'bx_messenger_main' AND `module` = @sName;

-- ACL

DELETE `sys_acl_actions`, `sys_acl_matrix`
FROM `sys_acl_actions`, `sys_acl_matrix`
WHERE `sys_acl_matrix`.`IDAction` = `sys_acl_actions`.`ID` AND `sys_acl_actions`.`Module` = @sName AND `Name` IN ('video recorder', 'join vc', 'join personal vc');

-- ACL

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'video recorder', NULL, '_bx_messenger_acl_action_video_recorder', '', 1, 0);
SET @iIdActionVRecorder = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'join vc', NULL, '_bx_messenger_acl_action_join_vc', '', 1, 0);
SET @iIdActionVCJoin = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'join personal vc', NULL, '_bx_messenger_acl_action_join_personal_vc', '', 1, 0);
SET @iIdActionVCPJoin = LAST_INSERT_ID();

SET @iUnauthenticated = 1;
SET @iAccount = 2;
SET @iStandard = 3;
SET @iUnconfirmed = 4;
SET @iPending = 5;
SET @iSuspended = 6;
SET @iModerator = 7;
SET @iAdministrator = 8;
SET @iPremium = 9;

INSERT INTO `sys_acl_matrix` (`IDLevel`, `IDAction`) VALUES
-- JOIN talks' video conference
(@iStandard, @iIdActionVCJoin),
(@iModerator, @iIdActionVCJoin),
(@iAdministrator, @iIdActionVCJoin),
(@iPremium, @iIdActionVCJoin),
-- JOIN personal video conference
(@iStandard, @iIdActionVCPJoin),
(@iModerator, @iIdActionVCPJoin),
(@iAdministrator, @iIdActionVCPJoin),
(@iPremium, @iIdActionVCPJoin),
-- video recorder
(@iStandard, @iIdActionVRecorder),
(@iModerator, @iIdActionVRecorder),
(@iAdministrator, @iIdActionVRecorder),
(@iPremium, @iIdActionVRecorder);

-- MENU
DELETE FROM `sys_objects_menu` WHERE `object` = CONCAT(@sName, '_lot_menu');
DELETE FROM `sys_menu_sets` WHERE `set_name` = CONCAT(@sName, '_lot_menu');
DELETE FROM `sys_menu_items` WHERE `set_name` = CONCAT(@sName, '_lot_menu');

-- MENU: Talk Menu
INSERT INTO `sys_objects_menu`(`object`, `title`, `set_name`, `module`, `template_id`, `deletable`, `active`, `override_class_name`, `override_class_file`) VALUES
(CONCAT(@sName, '_lot_menu'), '_bx_messenger_menu_title_view', CONCAT(@sName, '_lot_menu'), @sName, 22, 0, 1, 'BxMessengerLotMenu', 'modules/boonex/messenger/classes/BxMessengerLotMenu.php');

INSERT INTO `sys_menu_sets`(`set_name`, `module`, `title`, `deletable`) VALUES
(CONCAT(@sName, '_lot_menu'), @sName, '_bx_messenger_menu_set_title_talk_menu', 0);

INSERT INTO `sys_menu_items`(`set_name`, `module`, `name`, `title_system`, `title`, `link`, `onclick`, `target`, `icon`, `submenu_object`, `visible_for_levels`, `active`, `copyable`, `order`) VALUES
(CONCAT(@sName, '_lot_menu'), @sName, 'video_call', '_bx_messenger_menu_item_title_video_call', '_bx_messenger_menu_item_title_video_call', 'javascript:void(0);', 'oMessenger.onStartVideoCall(this, {id}, ''{room}'');', '_self', 'video', '', 2147483647, 1, 0, 1),
(CONCAT(@sName, '_lot_menu'), @sName, 'star', '_bx_messenger_menu_item_title_star', '_bx_messenger_menu_item_title_star', 'javascript:void(0);', 'oMessenger.onStarLot(this, {id});', '_self', 'star', '', 2147483647, 1, 0, 2),
(CONCAT(@sName, '_lot_menu'), @sName, 'mute', '_bx_messenger_menu_item_title_mute', '_bx_messenger_menu_item_title_mute', 'javascript:void(0);', 'oMessenger.onMuteLot(this, {id});', '_self', 'bell', '', 2147483647, 1, 0, 3),
(CONCAT(@sName, '_lot_menu'), @sName, 'settings', '_bx_messenger_lots_menu_settings_title', '_bx_messenger_lots_menu_settings_title', 'javascript:void(0)', 'oMessenger.showInfoMenu(this, ''{lot_menu_id}'')', '_self', 'info-circle', '', 2147483647, 1, 0, 4);
