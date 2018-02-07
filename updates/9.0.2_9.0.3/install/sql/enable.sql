-- LIVE UPDATES
DELETE FROM `sys_objects_live_updates` WHERE `name`='bx_messenger_new_messages';
INSERT INTO `sys_objects_live_updates`(`name`, `frequency`, `service_call`, `active`) VALUES
('bx_messenger_new_messages', 1, 'a:3:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:16:"get_live_updates";s:6:"params";a:3:{i:0;a:2:{s:11:"menu_object";s:18:"sys_toolbar_member";s:9:"menu_item";s:7:"account";}i:1;a:2:{s:11:"menu_object";s:25:"sys_account_notifications";s:9:"menu_item";s:23:"notifications-messenger";}i:2;s:7:"{count}";}}', 1);
