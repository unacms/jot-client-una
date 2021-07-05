SET @sName = 'bx_messenger';

-- SETTINGS
SET @iTypeOrder = (SELECT MAX(`order`) FROM `sys_options_types` WHERE `group` = 'modules');
INSERT INTO `sys_options_types`(`group`, `name`, `caption`, `icon`, `order`) VALUES 
('modules', @sName, '_bx_messenger', 'bx_messenger@modules/boonex/messenger/|std-icon.svg', IF(ISNULL(@iTypeOrder), 1, @iTypeOrder + 1));
SET @iTypeId = LAST_INSERT_ID();

INSERT INTO `sys_options_categories` (`type_id`, `name`, `caption`, `order`)
VALUES (@iTypeId, @sName, '_bx_messenger', 1);
SET @iCategId = LAST_INSERT_ID();

INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_max_symbols_number', 64000, @iCategId, '_bx_messenger_symbols_num_option', 'digit', '', '', '', 1),
('bx_messenger_max_symbols_brief_jot', 145, @iCategId, '_bx_messenger_symbols_num_brief_jot', 'digit', '', '', '', 2),
('bx_messenger_max_jot_number_default', 20, @iCategId, '_bx_messenger_jot_number_default', 'digit', '', '', '', 3),
('bx_messenger_max_jot_number_in_history', 10, @iCategId, '_bx_messenger_max_jot_number_in_history', 'digit', '', '', '', 4),
('bx_messenger_max_lots_number', 15, @iCategId, '_bx_messenger_max_max_lots_number', 'digit', '', '', '', 5),
('bx_messenger_is_push_enabled', '', @iCategId, '_bx_messenger_is_push_enabled', 'checkbox', '', '', '', 6),
('bx_messenger_push_app_id', '', @iCategId, '_bx_messenger_push_app_id', 'digit', '', '', '', 7),
('bx_messenger_push_rest_api', '', @iCategId, '_bx_messenger_push_rest_api', 'digit', '', '', '', 8),
('bx_messenger_push_short_name', '', @iCategId, '_bx_messenger_push_short_name', 'digit', '', '', '', 9),
('bx_messenger_push_safari_id', '', @iCategId, '_bx_messenger_push_safari_id', 'digit', '', '', '', 10),
('bx_messenger_server_url', '', @iCategId, '_bx_messenger_server_url', 'digit', '', '', '', 11),
('bx_messenger_max_files_send', 5, @iCategId, '_bx_messenger_max_files_upload', 'digit', '', '', '', 12),
('bx_messenger_max_video_length_minutes', 5, @iCategId, '_bx_messenger_max_video_file_size', 'digit', '', '', '', 13),
('bx_messenger_max_ntfs_number', 5, @iCategId, '_bx_messenger_max_ntfs_number', 'digit', '', '', '', 14),
('bx_messenger_max_parts_views', 10, @iCategId, '_bx_messenger_max_parts_views', 'digit', '', '', '', 15),
('bx_messenger_max_drop_down_select', 5, @iCategId, '_bx_messenger_max_drop_down_select', 'digit', '', '', '', 16),
('bx_messenger_allow_to_remove_messages', 'on', @iCategId, '_bx_messenger_allow_to_remove_messages', 'checkbox', '', '', '', 17),
('bx_messenger_allow_to_moderate_messages', 'on', @iCategId, '_bx_messenger_allow_to_moderate_messages', 'checkbox', '', '', '', 18),
('bx_messenger_enable_joined_organizations', '', @iCategId, '_bx_messenger_enable_joined_organizations', 'checkbox', '', '', '', 19),
('bx_messenger_remove_messages_immediately', '', @iCategId, '_bx_messenger_remove_messages_immediately', 'checkbox', '', '', '', 20),
('bx_messenger_use_embedly', 'on', @iCategId, '_bx_messenger_use_embedly', 'checkbox', '', '', '', 21),
('bx_messenger_giphy_key', '', @iCategId, '_bx_messenger_giphy_api_key', 'digit', '', '', '', 22),
('bx_messenger_giphy_type', 'gifs', @iCategId, '_bx_messenger_giphy_type', 'select', '', '', 'gifs,stickers', 23),
('bx_messenger_giphy_content_rating', 'g', @iCategId, '_bx_messenger_giphy_content_rating', 'select', '', '', 'g,pg,pg-13,r', 24),
('bx_messenger_giphy_limit', 15, @iCategId, '_bx_messenger_giphy_limit', 'digit', '', '', '', 25),
('bx_messenger_emoji_set', 'native', @iCategId, '_bx_messenger_emoji_set', 'select', '', '', 'native,apple,google,twitter,emojione,facebook,messenger', 26),
('bx_messenger_reactions_size', 16, @iCategId, '_bx_messenger_reactions_size', 'select', '', '', '16,20,24,32', 27),
('bx_messenger_show_emoji_preview', '', @iCategId, '_bx_messenger_show_emoji_preview', 'checkbox', '', '', '', 28),
('bx_messenger_jitsi_enable', 'on', @iCategId, '_bx_messenger_allow_to_use_jitsi', 'checkbox', '', '', '', 29),
('bx_messenger_jitsi_server', 'meet.jit.si', @iCategId, '_bx_messenger_jitsi_server', 'digit', '', '', '', 30),
('bx_messenger_jitsi_chat', '', @iCategId, '_bx_messenger_jitsi_chat_enable', 'checkbox', '', '', '', 31),
('bx_messenger_jitsi_sync', '', @iCategId, '_bx_messenger_jitsi_chat_sync', 'checkbox', '', '', '', 32),
('bx_messenger_jitsi_hide_info', '', @iCategId, '_bx_messenger_jitsi_hide_info', 'checkbox', '', '', '', 33),
('bx_messenger_jitsi_enable_watermark', '', @iCategId, '_bx_messenger_jitsi_watermark', 'checkbox', '', '', '', 34),
('bx_messenger_jitsi_watermark_link', '', @iCategId, '_bx_messenger_jitsi_watermark_link', 'digit', '', '', '', 35),
('bx_messenger_jitsi_only_for_private', 'on', @iCategId, '_bx_messenger_jitsi_enable_only_for_private', 'checkbox', '', '', '', 36),
('bx_messenger_jitsi_support_url', 'https://community.jitsi.org/', @iCategId, '_bx_messenger_jitsi_support_url', 'digit', '', '', '', 37),
('bx_messenger_disable_contact_privacy', '', @iCategId, '_bx_messenger_disable_contact_privacy', 'checkbox', '', '', '', 38),
('bx_messenger_enable_mentions', 'on', @iCategId, '_bx_messenger_use_mentions', 'checkbox', '', '', '', 39),
('bx_messenger_jwt_app_id', '', @iCategId, '_bx_messenger_jwt_app_id', 'digit', '', '', '', 40),
('bx_messenger_jwt_app_secret', '', @iCategId, '_bx_messenger_jwt_app_secret', 'digit', '', '', '', 41);


-- MENU: notifications
SET @iMIOrder = (SELECT IFNULL(MAX(`order`), 0) FROM `sys_menu_items` WHERE `set_name` = 'sys_toolbar_member' AND `order` < 9999);
INSERT INTO `sys_menu_items` (`set_name`, `module`, `name`, `title_system`, `title`, `link`, `onclick`, `target`, `icon`, `addon`, `submenu_object`, `visible_for_levels`, `active`, `copyable`, `order`, `visibility_custom`) VALUES
('sys_toolbar_member', @sName, 'notifications-messenger', '_bx_messenger_menu_notifications_item_sys_title', '', 'page.php?i=messenger', '', '', 'far comments col-green1', 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:20:"get_updated_lots_num";}', '', 2147483646, 1, 1, @iMIOrder + 1, ''),
('trigger_profile_view_actions', @sName, 'messenger', '_bx_messenger_menu_new_chat_sys_title', '_bx_messenger_menu_new_chat_action_title', 'page.php?i=messenger&profile_id={profile_id}', '', '', 'far comments', '', '', 2147483646, 1, 0, 0, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:18:"is_contact_allowed";s:6:"params";a:1:{i:0;s:12:"{profile_id}";}}'),
('trigger_profile_view_actions', @sName, 'public-vc-messenger', '_bx_messenger_menu_video_conference_sys_title', '_bx_messenger_menu_video_conference_action_title', 'javascript:void(0)', 'javascript:oMessengerPublicLib.showConferenceWindow(''modules/?r=messenger/get_video_conference_form/{profile_id}'')', '', 'video', '', '', 2147483646, 1, 0, 0, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:27:"is_video_conference_allowed";s:6:"params";a:1:{i:0;s:12:"{profile_id}";}}');

-- PAGE: module home
INSERT INTO `sys_objects_page`(`object`, `title_system`, `title`, `module`, `layout_id`, `visible_for_levels`, `visible_for_levels_editable`, `uri`, `url`, `meta_description`, `meta_keywords`, `meta_robots`, `cache_lifetime`, `cache_editable`, `deletable`, `override_class_name`, `override_class_file`, `cover`) VALUES 
('bx_messenger_main', '_bx_messenger_page_title_sys_main', '_bx_messenger_page_title_main', @sName, 1, 2147483647, 1, 'messenger', 'page.php?i=messenger', '', '', '', 0, 1, 0, 'BxMessengerPageMain', 'modules/boonex/messenger/classes/BxMessengerPageMain.php', 0);

INSERT INTO `sys_pages_blocks`(`object`, `cell_id`, `module`, `title`, `designbox_id`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`) VALUES 
('bx_messenger_main', 1, @sName, '_bx_messenger_page_inbox_block_title', 0, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:15:"get_block_inbox";s:6:"params";a:1:{i:0;s:2:"{}";}}', 0, 0, 0),
('bx_messenger_main', 2, @sName, '_bx_messenger_page_conversation_block_title', 0, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:13:"get_block_lot";s:6:"params";a:1:{i:0;s:2:"{}";}}', 0, 0, 0);

-- PAGES: add page block to profiles modules (trigger* page objects are processed separately upon modules enable/disable)
SET @iPBCellProfile = 2;
SET @iPBCellGroup = 4;
INSERT INTO `sys_pages_blocks` (`object`, `cell_id`, `module`, `title`, `designbox_id`, `tabs`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
('trigger_page_profile_view_entry', @iPBCellProfile, @sName, '_bx_messenger_page_block_title_messenger', 11, 1, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_block_messenger";s:6:"params";a:1:{i:0;s:6:"{type}";}}', 0, 0, 0, 0),
('trigger_page_group_view_entry', @iPBCellGroup, @sName, '_bx_messenger_page_block_title_messenger', 11, 1, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_block_messenger";s:6:"params";a:1:{i:0;s:6:"{type}";}}', 0, 0, 0, 0);

-- PAGE: service blocks
INSERT INTO `sys_pages_blocks` (`object`, `cell_id`, `module`, `title`, `designbox_id`, `tabs`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
('', 0, @sName, '_bx_messenger_page_block_title_messenger', 11, 1, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_block_messenger";s:6:"params";a:1:{i:0;s:6:"{type}";}}', 1, 1, 0, 0);

-- PAGES: add page block on home
SET @iPBCellHome = 1;
SET @iPBOrderHome = (SELECT IFNULL(MAX(`order`), 0) FROM `sys_pages_blocks` WHERE `object` = 'sys_home' AND `cell_id` = @iPBCellHome ORDER BY `order` DESC LIMIT 1);
INSERT INTO `sys_pages_blocks`(`object`, `cell_id`, `module`, `title`, `designbox_id`, `tabs`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
('sys_home', @iPBCellHome, @sName, '_bx_messenger_home_page_all_members_block', 11, 1, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_block_messenger";s:6:"params";a:1:{i:0;s:7:"members";}}', 0, 0, @iPBOrderHome + 1, 0);

-- ALERTS
INSERT INTO `sys_alerts_handlers` (`name`, `class`, `file`, `service_call`) VALUES 
('bx_messenger', '', '', 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:24:"delete_history_by_author";}');
SET @iHandler := LAST_INSERT_ID();

INSERT INTO `sys_alerts` (`unit`, `action`, `handler_id`) VALUES
('profile', 'delete', @iHandler);

-- LIVE UPDATES
INSERT INTO `sys_objects_live_updates`(`name`, `frequency`, `service_call`, `active`) VALUES
('bx_messenger_new_messages', 1, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:16:"get_live_updates";s:6:"params";a:3:{i:0;a:0:{}i:1;a:2:{s:11:"menu_object";s:18:"sys_toolbar_member";s:9:"menu_item";s:23:"notifications-messenger";}i:2;s:7:"{count}";}}', 1);

INSERT INTO `sys_objects_live_updates`(`name`, `frequency`, `service_call`, `active`) VALUES
('bx_messenger_public_video_conference', 1, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_live_vc_updates";s:6:"params";a:1:{i:1;s:7:"{count}";}}', 1);

REPLACE INTO `sys_storage_mime_types` (`ext`, `mime_type`, `icon`, `icon_font`) VALUES
('x-matroska', 'video/x-matroska', '', '');

-- ACL
INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'video conference', NULL, '_bx_messenger_acl_action_video_conference', '', 1, 0);
SET @iIdActionEntryIMCreate = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'create talks', NULL, '_bx_messenger_acl_action_create_talks', '', 1, 0);
SET @iIdActionEntryTalkCreate = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'send messages', NULL, '_bx_messenger_acl_action_send_messages', '', 1, 0);
SET @iIdActionSendMessage = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'administrate messages', NULL, '_bx_messenger_acl_action_administrate_messages', '', 1, 0);
SET @iIdActionAdminMessages = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'administrate talks', NULL, '_bx_messenger_acl_action_administrate_talks', '', 1, 0);
SET @iIdActionAdminTalks = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'create vc', NULL, '_bx_messenger_acl_action_create_vc', '', 1, 0);
SET @iIdActionVCCreate = LAST_INSERT_ID();

INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'video recorder', NULL, '_bx_messenger_acl_action_video_recorder', '', 1, 0);
SET @iIdActionVRecorder = LAST_INSERT_ID();

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
-- IM video conference
(@iStandard, @iIdActionEntryIMCreate),
(@iModerator, @iIdActionEntryIMCreate),
(@iAdministrator, @iIdActionEntryIMCreate),
(@iPremium, @iIdActionEntryIMCreate),
-- video conference
(@iStandard, @iIdActionVCCreate),
(@iModerator, @iIdActionVCCreate),
(@iAdministrator, @iIdActionVCCreate),
(@iPremium, @iIdActionVCCreate),
-- video recorder
(@iStandard, @iIdActionVRecorder),
(@iModerator, @iIdActionVRecorder),
(@iAdministrator, @iIdActionVRecorder),
(@iPremium, @iIdActionVRecorder),
-- create talk
(@iStandard, @iIdActionEntryTalkCreate),
(@iModerator, @iIdActionEntryTalkCreate),
(@iAdministrator, @iIdActionEntryTalkCreate),
(@iPremium, @iIdActionEntryTalkCreate),
-- send messages
(@iStandard, @iIdActionSendMessage),
(@iModerator, @iIdActionSendMessage),
(@iAdministrator, @iIdActionSendMessage),
(@iPremium, @iIdActionSendMessage),
-- administration messages
(@iModerator, @iIdActionAdminMessages),
(@iAdministrator, @iIdActionAdminMessages),
-- administration messages
(@iModerator, @iIdActionAdminTalks),
(@iAdministrator, @iIdActionAdminTalks);


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
