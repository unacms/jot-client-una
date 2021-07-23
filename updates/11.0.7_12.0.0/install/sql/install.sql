DROP TABLE IF EXISTS `bx_messenger_lots_settings`;
CREATE TABLE IF NOT EXISTS `bx_messenger_lots_settings` (
   `lot_id` int(11) NOT NULL,
   `settings` text NOT NULL,
   UNIQUE KEY `id` (`lot_id`)
);

ALTER TABLE `bx_messenger_files` MODIFY `size` bigint(20) NOT NULL;
ALTER TABLE `bx_messenger_photos_resized` MODIFY `size` bigint(20) NOT NULL;
ALTER TABLE `bx_messenger_videos_processed` MODIFY `size` bigint(20) NOT NULL;
ALTER TABLE `bx_messenger_mp3_processed` MODIFY `size` bigint(20) NOT NULL;