SET @sName = 'bx_messenger';

-- SETTINGS

DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_membership_restrictions', 'bx_messenger_allow_to_moderate_messages', 'bx_messenger_disable_contact_privacy', 'bx_messenger_enable_mentions');

UPDATE `sys_options` SET `order` = `order` + 1 WHERE `order` >= 17;

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_allow_to_moderate_messages', 'on', @iCategId, '_bx_messenger_allow_to_moderate_messages', 'checkbox', '', '', '', 17),
('bx_messenger_disable_contact_privacy', '', @iCategId, '_bx_messenger_disable_contact_privacy', 'checkbox', '', '', '', 36),
('bx_messenger_enable_mentions', 'on', @iCategId, '_bx_messenger_use_mentions', 'checkbox', '', '', '', 37);

-- MENU
DELETE FROM `sys_menu_items` WHERE `name` = 'public-vc-messenger';

-- MENU: notifications
INSERT INTO `sys_menu_items` (`set_name`, `module`, `name`, `title_system`, `title`, `link`, `onclick`, `target`, `icon`, `addon`, `submenu_object`, `visible_for_levels`, `active`, `copyable`, `order`, `visibility_custom`) VALUES
('trigger_profile_view_actions', @sName, 'public-vc-messenger', '_bx_messenger_menu_video_conference_sys_title', '_bx_messenger_menu_video_conference_action_title', 'javascript:void(0)', 'javascript:oMessengerPublicLib.showConferenceWindow(''modules/?r=messenger/get_video_conference_form/{profile_id}'')', '', 'video', '', '', 2147483646, 1, 0, 0, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:27:"is_video_conference_allowed";s:6:"params";a:1:{i:0;s:12:"{profile_id}";}}');

UPDATE `sys_objects_live_updates`
SET `service_call` = 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:16:"get_live_updates";s:6:"params";a:3:{i:0;a:0:{}i:1;a:2:{s:11:"menu_object";s:18:"sys_toolbar_member";s:9:"menu_item";s:23:"notifications-messenger";}i:2;s:7:"{count}";}}'
WHERE `name` = 'bx_messenger_new_messages';

DELETE FROM `sys_objects_live_updates` WHERE `name` = 'bx_messenger_public_video_conference';

INSERT INTO `sys_objects_live_updates`(`name`, `frequency`, `service_call`, `active`) VALUES
('bx_messenger_public_video_conference', 1, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_live_vc_updates";s:6:"params";a:1:{i:1;s:7:"{count}";}}', 1);

-- ACL
DELETE `sys_acl_actions`, `sys_acl_matrix`
FROM `sys_acl_actions`, `sys_acl_matrix`
WHERE `sys_acl_matrix`.`IDAction` = `sys_acl_actions`.`ID` AND `sys_acl_actions`.`Module` = @sName;
DELETE FROM `sys_acl_actions` WHERE `Module` = @sName;

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

