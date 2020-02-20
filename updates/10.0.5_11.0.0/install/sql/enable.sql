SET @sName = 'bx_messenger';

-- SETTINGS

DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_typing_smiles', 'bx_messenger_emoji_set', 'bx_messenger_reactions_size', 'bx_messenger_show_emoji_preview');

UPDATE `sys_options` SET `order` = `order` - 1 WHERE `order` > 9;

SET @iCategId = (SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_emoji_set', 'native', @iCategId, '_bx_messenger_emoji_set', 'select', '', '', 'native,apple,google,twitter,emojione,facebook,messenger', 23),
('bx_messenger_reactions_size', '16', @iCategId, '_bx_messenger_reactions_size', 'select', '', '', '14,16,20,24,32', 24),
('bx_messenger_show_emoji_preview', '', @iCategId, '_bx_messenger_show_emoji_preview', 'checkbox', '', '', '', 25);