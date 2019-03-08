ALTER TABLE `bx_messenger_jots` CHANGE `message` `message` text NOT NULL;
ALTER TABLE `bx_messenger_lcomments` CHANGE `lcmt_author_id` `lcmt_author_id` int(11) NOT NULL DEFAULT '0';