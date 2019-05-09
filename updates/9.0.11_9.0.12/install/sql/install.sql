SET @sStorageEngine = (SELECT `value` FROM `sys_options` WHERE `name` = 'sys_storage_default');

DROP TABLE IF EXISTS `bx_messenger_mp3_processed`;
CREATE TABLE IF NOT EXISTS `bx_messenger_mp3_processed` (
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

DELETE FROM `sys_objects_storage` WHERE `object` = 'bx_messenger_mp3_processed';
INSERT INTO `sys_objects_storage` (`object`, `engine`, `params`, `token_life`, `cache_control`, `levels`, `table_files`, `ext_mode`, `ext_allow`, `ext_deny`, `quota_size`, `current_size`, `quota_number`, `current_number`, `max_file_size`, `ts`) VALUES
('bx_messenger_mp3_processed', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_mp3_processed', 'allow-deny', '3gp,aa,aac,aax,act,aiff,amr,ape,au,awb,dct,dss,dvf,flac,gsm,iklax,ivs,m4a,m4b,m4p,mmf,mp3,mpc,msv,nmf,nsf,ogg,opus,ra,raw,sln,tta,vox,wav,wma,wv,webm,8svx', '', 0, 0, 0, 0, 0, 0);

DELETE FROM `sys_objects_transcoder` WHERE `object` = 'bx_messenger_mp3';
INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`, `override_class_name`) VALUES 
('bx_messenger_mp3', 'bx_messenger_mp3_processed', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '0', '0', '0', 'BxDolTranscoderAudio');

DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object` = 'bx_messenger_mp3';
INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES 
('bx_messenger_mp3', 'Mp3', 'a:2:{s:13:"audio_bitrate";s:3:"128";s:10:"force_type";s:3:"mp3";}', 0);