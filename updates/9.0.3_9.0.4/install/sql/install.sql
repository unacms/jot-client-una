SET @sName = 'bx_messenger';


-- TABLES
CREATE TABLE IF NOT EXISTS `bx_messenger_videos_processed` (
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

-- TABLE: bx_messenger_files

ALTER TABLE `bx_messenger_files` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_files` CHANGE `remote_id` `remote_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_files` CHANGE `path` `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_files` CHANGE `file_name` `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_files` CHANGE `mime_type` `mime_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_files` CHANGE `ext` `ext` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_files`;
OPTIMIZE TABLE `bx_messenger_files`;


-- TABLE: bx_messenger_jots

ALTER TABLE `bx_messenger_jots` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_jots` CHANGE `attachment_type` `attachment_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_jots` CHANGE `attachment` `attachment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_jots` CHANGE `new_for` `new_for` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_jots`;
OPTIMIZE TABLE `bx_messenger_jots`;


-- TABLE: bx_messenger_lots

ALTER TABLE `bx_messenger_lots` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_lots` CHANGE `title` `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_lots` CHANGE `url` `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_lots` CHANGE `participants` `participants` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_lots` CHANGE `class` `class` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_lots`;
OPTIMIZE TABLE `bx_messenger_lots`;


-- TABLE: bx_messenger_lots_types

ALTER TABLE `bx_messenger_lots_types` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_lots_types` CHANGE `name` `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_lots_types`;
OPTIMIZE TABLE `bx_messenger_lots_types`;


-- TABLE: bx_messenger_photos_resized

ALTER TABLE `bx_messenger_photos_resized` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_photos_resized` CHANGE `remote_id` `remote_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_photos_resized` CHANGE `path` `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_photos_resized` CHANGE `file_name` `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_photos_resized` CHANGE `mime_type` `mime_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_photos_resized` CHANGE `ext` `ext` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_photos_resized`;
OPTIMIZE TABLE `bx_messenger_photos_resized`;


-- TABLE: bx_messenger_users_info

ALTER TABLE `bx_messenger_users_info` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_users_info` CHANGE `params` `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_users_info`;
OPTIMIZE TABLE `bx_messenger_users_info`;


-- TABLE: bx_messenger_videos_processed

ALTER TABLE `bx_messenger_videos_processed` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `bx_messenger_videos_processed` CHANGE `remote_id` `remote_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_videos_processed` CHANGE `path` `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_videos_processed` CHANGE `file_name` `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_videos_processed` CHANGE `mime_type` `mime_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `bx_messenger_videos_processed` CHANGE `ext` `ext` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

REPAIR TABLE `bx_messenger_videos_processed`;
OPTIMIZE TABLE `bx_messenger_videos_processed`;


-- STORAGES & TRANSCODERS

SET @sStorageEngine = (SELECT `value` FROM `sys_options` WHERE `name` = 'sys_storage_default');

DELETE FROM `sys_objects_storage` WHERE `object`='bx_messenger_videos_processed';
INSERT INTO `sys_objects_storage` (`object`, `engine`, `params`, `token_life`, `cache_control`, `levels`, `table_files`, `ext_mode`, `ext_allow`, `ext_deny`, `quota_size`, `current_size`, `quota_number`, `current_number`, `max_file_size`, `ts`) VALUES
('bx_messenger_videos_processed', @sStorageEngine, '', 360, 2592000, 3, 'bx_messenger_videos_processed', 'allow-deny', 'avi,flv,mpg,mpeg,wmv,mp4,m4v,mov,divx,xvid,3gp,webm,jpg', '', 0, 0, 0, 0, 0, 0);

DELETE FROM `sys_objects_transcoder` WHERE `object` IN ('bx_messenger_videos_poster', 'bx_messenger_videos_mp4', 'bx_messenger_videos_webm');
INSERT INTO `sys_objects_transcoder` (`object`, `storage_object`, `source_type`, `source_params`, `private`, `atime_tracking`, `atime_pruning`, `ts`, `override_class_name`) VALUES 
('bx_messenger_videos_poster', 'bx_messenger_videos_processed', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '0', '0', '0', 'BxDolTranscoderVideo'),
('bx_messenger_videos_mp4', 'bx_messenger_videos_processed', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '0', '0', '0', 'BxDolTranscoderVideo'),
('bx_messenger_videos_webm', 'bx_messenger_videos_processed', 'Storage', 'a:1:{s:6:"object";s:18:"bx_messenger_files";}', 'no', '0', '0', '0', 'BxDolTranscoderVideo');

DELETE FROM `sys_transcoder_filters` WHERE `transcoder_object` IN ('bx_messenger_videos_poster', 'bx_messenger_videos_mp4', 'bx_messenger_videos_webm');
INSERT INTO `sys_transcoder_filters` (`transcoder_object`, `filter`, `filter_params`, `order`) VALUES 
('bx_messenger_videos_poster', 'Poster', 'a:2:{s:1:"h";s:3:"720";s:10:"force_type";s:3:"jpg";}', 0),
('bx_messenger_videos_mp4', 'Mp4', 'a:2:{s:1:"h";s:3:"480";s:10:"force_type";s:3:"mp4";}', 0),
('bx_messenger_videos_webm', 'Webm', 'a:2:{s:1:"h";s:3:"480";s:10:"force_type";s:4:"webm";}', 0);


