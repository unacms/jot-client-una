SET @sName = 'bx_messenger';

-- SETTINGS
DELETE FROM `sys_options` WHERE `name` IN ('bx_messenger_membership_restrictions');

SET @iCategId = ( SELECT `id` FROM `sys_options_categories` WHERE `name`=@sName LIMIT 1 );
SET @iOrderId = (SELECT MAX(`Order`) FROM `sys_options` WHERE `category_id` = @iCategId);
INSERT INTO `sys_options` (`name`, `value`, `category_id`, `caption`, `type`, `check`, `check_error`, `extra`, `order`) VALUES
('bx_messenger_membership_restrictions', '4,5,6', @iCategId, '_bx_messenger_restricted_memberships', 'list', '', '', 'a:2:{s:6:"module";s:12:"bx_messenger";s:6:"method";s:21:"get_membership_levels";}', @iOrderId + 1);

