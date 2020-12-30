DROP TABLE IF EXISTS `bx_messenger_unread_jots`;
CREATE TABLE `bx_messenger_unread_jots` (
   `lot_id` int(11) NOT NULL default 0,
   `first_jot_id` int(11) NOT NULL default 0,
   `unread_count` int(11) NOT NULL default 0,
   `user_id` int(11) NOT NULL default 0,
    UNIQUE KEY `id` (`lot_id`, `user_id`),
    KEY `user` (`user_id`),
    KEY `jot` (`first_jot_id`)
);

DROP TABLE IF EXISTS `bx_messenger_public_jvc`;
CREATE TABLE `bx_messenger_public_jvc` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `room` varchar(100) NOT NULL,
   `participants` varchar(255) NOT NULL default 0,
   `active` tinyint(1) unsigned NOT NULL default 1,
   `created` int(11) unsigned NOT NULL default 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `room` (`room`)
);