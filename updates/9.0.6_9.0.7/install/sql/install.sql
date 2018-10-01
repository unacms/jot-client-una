CREATE TABLE IF NOT EXISTS `bx_messenger_lcomments` (
  `lcmt_id` int(11) NOT NULL AUTO_INCREMENT,
  `lcmt_parent_id` int(11) NOT NULL DEFAULT '0',
  `lcmt_system_id` int(11) NOT NULL DEFAULT '0',
  `lcmt_vparent_id` int(11) NOT NULL DEFAULT '0',
  `lcmt_object_id` int(11) NOT NULL DEFAULT '0',
  `lcmt_author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lcmt_level` int(11) NOT NULL DEFAULT '0',
  `lcmt_text` text NOT NULL,
  `lcmt_time` int(11) unsigned NOT NULL DEFAULT '0',
  `lcmt_replies` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lcmt_id`),
  KEY `lcmt_object_id` (`lcmt_object_id`,`lcmt_parent_id`),
  FULLTEXT KEY `search_fields` (`lcmt_text`)
) ENGINE=MyISAM;
