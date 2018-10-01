SET @sName = 'bx_messenger';

UPDATE `sys_menu_items` SET `addon` = 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:20:"get_updated_lots_num";}' WHERE `name` = 'notifications-messenger' AND `module` = @sName;
UPDATE `sys_menu_items` SET `link` = 'page.php?i=messenger&profile_id={profile_id}' WHERE `name` = 'messenger' AND `module` = @sName;
UPDATE `sys_pages_blocks` SET `copyable` = 0 WHERE `object` IN ('bx_messenger_main','sys_home') AND `module` = @sName;

-- PAGE: service blocks
DELETE FROM `sys_pages_blocks` WHERE `module` = @sName AND `object` = ''; 
INSERT INTO `sys_pages_blocks` (`object`, `cell_id`, `module`, `title`, `designbox_id`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
('', 0, @sName, '_bx_messenger_page_block_title_messenger', 0, 2147483647, 'service', 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:19:"get_block_messenger";s:6:"params";a:1:{i:0;s:6:"{type}";}}', 1, 1, 0, 0);