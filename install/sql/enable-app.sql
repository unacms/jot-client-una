-- PAGES: config_api
UPDATE `sys_objects_page` SET `config_api`='{"layout":"messenger"}' WHERE `object`='bx_messenger_main';

-- PAGES: active_api
UPDATE `sys_pages_blocks` SET `active_api`=1 WHERE `object`='bx_messenger_main' AND `module`='bx_messenger' AND `title_system`='' AND `title`='_bx_messenger_page_main_messenger_block';


-- MENUS:

-- MENUS: config_api

-- MENUS: active_api
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='sys_toolbar_member' AND `module`='bx_messenger' AND `name`='notifications-messenger';

UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='parent';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='list';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='video_call';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='star';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='mute';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_menu' AND `module`='bx_messenger' AND `name`='settings';

UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='inbox';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='direct';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='threads';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='reply';
UPDATE `sys_menu_items` SET `active_api`=0 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='mentions_reactions';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_nav_menu' AND `module`='bx_messenger' AND `name`='saved';

UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='add_participants';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='delete';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='leave';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='media';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='clear';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_lot_info_menu' AND `module`='bx_messenger' AND `name`='settings';

UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_jot_menu' AND `module`='bx_messenger' AND `name`='reply';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_jot_menu' AND `module`='bx_messenger' AND `name`='share';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_jot_menu' AND `module`='bx_messenger' AND `name`='edit';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_jot_menu' AND `module`='bx_messenger' AND `name`='remove';
UPDATE `sys_menu_items` SET `active_api`=1 WHERE `set_name`='bx_messenger_jot_menu' AND `module`='bx_messenger' AND `name`='save';
