DROP TABLE IF EXISTS `bx_messenger_jvc`;
CREATE TABLE IF NOT EXISTS `bx_messenger_jvc` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `lot_id` int(11) NOT NULL,
   `room` varchar(64) NOT NULL,
   `number` tinyint(255) NOT NULL default 0,
   `active` int(11) unsigned NOT NULL default 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `lot_id` (`lot_id`),
    UNIQUE KEY `room` (`room`)
);

DROP TABLE IF EXISTS `bx_messenger_jvc_track`;
CREATE TABLE IF NOT EXISTS `bx_messenger_jvc_track` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `jvc_id` int(11) NOT NULL,
   `author_id` int(11) NOT NULL,
   `start` int(11) NOT NULL,
   `end` int(11) NOT NULL,
   `participants` varchar(255) NOT NULL default '',
   `joined` varchar(255) NOT NULL default '',
    PRIMARY KEY (`id`)
);