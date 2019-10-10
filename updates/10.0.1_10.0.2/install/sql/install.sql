DELETE FROM `sys_objects_transcoder` WHERE `object` = 'bx_messenger_videos_mp4_hd';
INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`, `override_class_name`) VALUES 
('bx_messenger_videos_mp4_hd', 'bx_messenger_videos_processed', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '0', '0', '0', 'BxDolTranscoderVideo');

DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object` = 'bx_messenger_videos_mp4_hd';
INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES 
('bx_messenger_videos_mp4_hd', 'Mp4', 'a:3:{s:1:"h";s:3:"720";s:13:"video_bitrate";s:4:"1536";s:10:"force_type";s:3:"mp4";}', 0);

UPDATE `bx_notifications_settings`
SET `group` = 'bx_messenger'
WHERE `title` = '_bx_ntfs_alert_action_got_jot_ntfs_personal';