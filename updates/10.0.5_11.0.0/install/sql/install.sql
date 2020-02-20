DROP TABLE IF EXISTS `bx_messenger_jot_reactions`;
CREATE TABLE IF NOT EXISTS `bx_messenger_jot_reactions` (
   `jot_id` int(11) unsigned NOT NULL default '0',
   `native` varchar(10) NOT NULL,
   `emoji_id` varchar(50) NOT NULL,
   `user_id` int(11) unsigned NOT NULL default '0',
   `added` int(11) NOT NULL default '0',
   KEY `jot_id` (`jot_id`),
   UNIQUE KEY `jot` (`jot_id`,`emoji_id`, `user_id`)
);