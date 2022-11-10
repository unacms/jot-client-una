DROP TABLE IF EXISTS `bx_messenger_attachments`;
CREATE TABLE IF NOT EXISTS `bx_messenger_attachments` (
   `name` varchar(50) NOT NULL default '',
   `service` varchar(255) NOT NULL,
    PRIMARY KEY (`name`)
);
