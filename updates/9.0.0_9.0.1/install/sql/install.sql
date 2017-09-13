-- TABLES
UPDATE `bx_messenger_lots` SET `title`='_bx_messenger_lots_class_friends' WHERE `title`='_bx_messenger_lots_class_my_members' AND `class`='friends';
UPDATE `bx_messenger_lots` SET `title`='_bx_messenger_lots_class_my_members' WHERE `title`='_bx_messenger_lots_class_friends' AND `class`='members';

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

DELETE FROM `sys_objects_storage` WHERE `object` IN ('bx_messenger_files', 'bx_messenger_photos_resized');
INSERT INTO `sys_objects_storage` (`object`, `engine`, `params`, `token_life`, `cache_control`, `levels`, `table_files`, `ext_mode`, `ext_allow`, `ext_deny`, `quota_size`, `current_size`, `quota_number`, `current_number`, `max_file_size`, `ts`) VALUES
('bx_messenger_files', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_files', 'deny-allow', '', 'action,apk,app,bat,bin,cmd,com,command,cpl,csh,exe,gadget,inf,ins,inx,ipa,isu,job,jse,ksh,lnk,msc,msi,msp,mst,osx,out,paf,pif,prg,ps1,reg,rgs,run,sct,shb,shs,u3p,vb,vbe,vbs,vbscript,workflow,ws,wsf', 0, 0, 0, 0, 0, 0),
('bx_messenger_photos_resized', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_photos_resized', 'allow-deny', 'jpg,jpeg,jpe,gif,png', '', 0, 0, 0, 0, 0, 0);

DELETE FROM `sys_objects_transcoder` WHERE `object`='bx_messenger_preview';
INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`) VALUES 
('bx_messenger_preview', 'bx_messenger_photos_resized', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '1', '2592000', '0');

DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object`='bx_messenger_preview' AND `filter`='Resize';
INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES 
('bx_messenger_preview', 'Resize', 'a:3:{s:1:"w";s:3:"720";s:1:"h";s:3:"720";s:11:"crop_resize";s:1:"0";}', '0');
