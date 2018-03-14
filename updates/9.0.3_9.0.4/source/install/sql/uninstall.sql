SET @sName = 'bx_messenger';

DROP TABLE IF EXISTS `bx_messenger_jots`;
DROP TABLE IF EXISTS `bx_messenger_lots`;
DROP TABLE IF EXISTS `bx_messenger_lots_types`;
DROP TABLE IF EXISTS `bx_messenger_users_info`;
DROP TABLE IF EXISTS `bx_messenger_files`;
DROP TABLE IF EXISTS `bx_messenger_photos_resized`;
DROP TABLE IF EXISTS `bx_messenger_videos_processed`;

-- STORAGES & TRANSCODERS
DELETE FROM `sys_objects_storage` WHERE `object` IN('bx_messenger_files', 'bx_messenger_photos_resized', 'bx_messenger_videos_processed');
DELETE FROM `sys_objects_transcoder` WHERE `storage_object` IN ('bx_messenger_photos_resized', 'bx_messenger_videos_processed');
DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object` IN('bx_messenger_preview', 'bx_messenger_videos_poster', 'bx_messenger_videos_mp4', 'bx_messenger_videos_webm');
DELETE FROM `sys_transcoder_images_files` WHERE `transcoder_object` IN ('bx_messenger_preview');
DELETE FROM `sys_transcoder_videos_files` WHERE `transcoder_object` LIKE 'bx_messenger%';

-- STUDIO PAGE & WIDGET
DELETE FROM `tp`, `tw`, `tpw`
USING `sys_std_pages` AS `tp`, `sys_std_widgets` AS `tw`, `sys_std_pages_widgets` AS `tpw`
WHERE `tp`.`id` = `tw`.`page_id` AND `tw`.`id` = `tpw`.`widget_id` AND `tp`.`name` = @sName;