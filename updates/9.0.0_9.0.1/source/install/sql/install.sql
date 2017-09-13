SET @sName = 'bx_messenger';

CREATE TABLE IF NOT EXISTS `bx_messenger_jots` (
   `id` int(11) NOT NULL auto_increment,
   `lot_id` int(11) unsigned NOT NULL default '0',   
   `message` blob NOT NULL,
   `created` int(11) NOT NULL default '0',
   `user_id` int(11) unsigned NOT NULL default '0',
   `attachment_type` varchar(255) NOT NULL default '',
   `attachment` text NOT NULL default '',
   `new_for` text NOT NULL default '',
   PRIMARY KEY (`id`),
   KEY `lot_id` (`lot_id`)   
);

CREATE TABLE IF NOT EXISTS `bx_messenger_lots` (
   `id` int(11) NOT NULL auto_increment,
   `title` varchar(255) collate utf8_unicode_ci NOT NULL,
   `url` varchar(255) NOT NULL default '',
   `type` tinyint(3) NOT NULL default 1,
   `created` int(11) NOT NULL default '0',
   `author` int(11) unsigned NOT NULL default '0',
   `participants` text NOT NULL default '',
   `class` varchar(20) NOT NULL default 'custom', 
   PRIMARY KEY  (`id`),
   FULLTEXT KEY `search_title` (`title`),
   FULLTEXT KEY `search_url` (`url`)
);

INSERT INTO `bx_messenger_lots` (`id`, `title`, `url`, `type`, `created`, `author`, `participants`, `class`) VALUES
(NULL, '_bx_messenger_lots_class_friends', '', 3, UNIX_TIMESTAMP(), 0, '', 'friends'),
(NULL, '_bx_messenger_lots_class_my_members', '', 3, UNIX_TIMESTAMP(), 0, '', 'members');


CREATE TABLE IF NOT EXISTS `bx_messenger_lots_types` (
   `id` int(11) NOT NULL auto_increment,
   `name` varchar(50) NOT NULL default '',
   `show_link` tinyint(1) NOT NULL default 0,
    PRIMARY KEY (`id`)
);

INSERT INTO `bx_messenger_lots_types` (`id`, `name`, `show_link`) VALUES
(1, 'public', 1),
(2, 'private', 0),
(3, 'sets', 0),
(4, 'groups', 1),
(5, 'events', 1);

CREATE TABLE IF NOT EXISTS `bx_messenger_users_info` (
   `lot_id` int(11) NOT NULL auto_increment,
   `user_id` int(11) NOT NULL default '0',
   `params` text NOT NULL default '',
   `star` tinyint(1) NOT NULL default '0',
    UNIQUE KEY `id` (`lot_id`,`user_id`)
);

-- TABLE: storages & transcoders
CREATE TABLE IF NOT EXISTS `bx_messenger_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) unsigned NOT NULL,
  `jot_id` int(10) unsigned NOT NULL,
  `remote_id` varchar(128) NOT NULL,
  `path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `ext` varchar(32) NOT NULL,
  `size` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `private` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remote_id` (`remote_id`)
);

CREATE TABLE IF NOT EXISTS `bx_messenger_photos_resized` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) unsigned NOT NULL,
  `remote_id` varchar(128) NOT NULL,
  `path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(128) NOT NULL,
  `ext` varchar(32) NOT NULL,
  `size` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `private` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `remote_id` (`remote_id`)
);

-- STORAGES & TRANSCODERS

SET @sStorageEngine = (SELECT `value` FROM `sys_options` WHERE `name` = 'sys_storage_default');

INSERT INTO `sys_objects_storage` (`object`, `engine`, `params`, `token_life`, `cache_control`, `levels`, `table_files`, `ext_mode`, `ext_allow`, `ext_deny`, `quota_size`, `current_size`, `quota_number`, `current_number`, `max_file_size`, `ts`) VALUES
('bx_messenger_files', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_files', 'deny-allow', '', 'action,apk,app,bat,bin,cmd,com,command,cpl,csh,exe,gadget,inf,ins,inx,ipa,isu,job,jse,ksh,lnk,msc,msi,msp,mst,osx,out,paf,pif,prg,ps1,reg,rgs,run,sct,shb,shs,u3p,vb,vbe,vbs,vbscript,workflow,ws,wsf', 0, 0, 0, 0, 0, 0),
('bx_messenger_photos_resized', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_photos_resized', 'allow-deny', 'jpg,jpeg,jpe,gif,png', '', 0, 0, 0, 0, 0, 0);

INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`) VALUES 
('bx_messenger_preview', 'bx_messenger_photos_resized', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '1', '2592000', '0');

INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES 
('bx_messenger_preview', 'Resize', 'a:3:{s:1:"w";s:3:"720";s:1:"h";s:3:"720";s:11:"crop_resize";s:1:"0";}', '0');


-- STUDIO PAGE & WIDGET
INSERT INTO `sys_std_pages`(`index`, `name`, `header`, `caption`, `icon`) VALUES
(3, 'bx_messenger', '_bx_messenger', '_bx_messenger', 'bx_messenger@modules/boonex/messenger/|std-icon.svg');
SET @iPageId = LAST_INSERT_ID();

SET @iParentPageId = (SELECT `id` FROM `sys_std_pages` WHERE `name` = 'home');
SET @iParentPageOrder = (SELECT MAX(`order`) FROM `sys_std_pages_widgets` WHERE `page_id` = @iParentPageId);
INSERT INTO `sys_std_widgets` (`page_id`, `module`, `url`, `click`, `icon`, `caption`, `cnt_notices`, `cnt_actions`) VALUES
(@iPageId, 'bx_messenger', '{url_studio}module.php?name=bx_messenger', '', 'bx_messenger@modules/boonex/messenger/|std-icon.svg', '_bx_messenger', '', 'a:4:{s:6:"module";s:6:"system";s:6:"method";s:11:"get_actions";s:6:"params";a:0:{}s:5:"class";s:18:"TemplStudioModules";}');
INSERT INTO `sys_std_pages_widgets` (`page_id`, `widget_id`, `order`) VALUES
(@iParentPageId, LAST_INSERT_ID(), IF(ISNULL(@iParentPageOrder), 1, @iParentPageOrder + 1));
