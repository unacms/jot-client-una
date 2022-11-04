SET @sName = 'bx_messenger';

-- SETTINGS
DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_check_toxic','bx_messenger_jot_server_jwt','bx_messenger_search_criteria','bx_messenger_time_in_history','bx_messenger_dont_show_search_desc','bx_messenger_use_unique_mode','bx_messenger_connect_friends_only');
SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);

INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_check_toxic', '', @iCategId, '_bx_messenger_check_toxic', 'checkbox', '', '', '', 43),
('bx_messenger_jot_server_jwt', '', @iCategId, '_bx_messenger_jot_server_jwt', 'digit', '', '', '', 44),
('bx_messenger_search_criteria', 'titles,participants,content', @iCategId, '_bx_messenger_search_criteria_list', 'list', '', '', 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:18:"get_search_options";}', 45),
('bx_messenger_time_in_history', '', @iCategId, '_bx_messenger_time_in_history', 'checkbox', '', '', '', 46),
('bx_messenger_dont_show_search_desc', '', @iCategId, '_bx_messenger_dont_show_search_desc', 'checkbox', '', '', '', 47),
('bx_messenger_use_unique_mode', '', @iCategId, '_bx_messenger_use_unique_mode', 'checkbox', '', '', '', 48),
('bx_messenger_connect_friends_only', '', @iCategId, '_bx_messenger_connect_friends_only', 'checkbox', '', '', '', 49);

UPDATE `sys_menu_items` SET `hidden_on`= 9 WHERE `set_name` = 'sys_toolbar_member' AND `module` = @sName;

DELETE FROM `sys_menu_items` WHERE `set_name` = 'sys_account_notifications' AND `module` = @sName AND `title_system` = '_bx_messenger_menu_notifications_item_sys_title';
SET @iMIOrder = (SELECT IFNULL(MAX(`order`), 0) FROM `sys_menu_items` WHERE `set_name` = 'sys_account_notifications' AND `order` < 9999);
INSERT INTO `sys_menu_items` (`set_name`, `module`, `name`, `title_system`, `title`, `link`, `onclick`, `target`, `icon`, `addon`, `submenu_object`, `visible_for_levels`, `active`, `copyable`, `order`, `visibility_custom`, `hidden_on`) VALUES
('sys_account_notifications', @sName, 'notifications-messenger', '_bx_messenger_menu_notifications_item_sys_title', '_bx_messenger_menu_notifications_item_title', 'page.php?i=messenger', '', '', 'far comments col-green1', 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:20:"get_updated_lots_num";}', '', 2147483646, 1, 1, @iMIOrder + 1, '', 6);

DELETE FROM `sys_objects_transcoder` WHERE `object` = 'bx_messenger_icon';
INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`, `override_class_name`) VALUES
('bx_messenger_icon', 'bx_messenger_photos_resized', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '1', '0', '0', '');

DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object` = 'bx_messenger_icon';
INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES
('bx_messenger_icon', 'Resize', 'a:3:{s:1:"w";s:2:"96";s:1:"h";s:2:"96";s:11:"crop_resize";s:1:"0";}', 0);