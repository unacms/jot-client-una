SET @sName = 'bx_messenger';

-- ACL
DELETE `sys_acl_actions`, `sys_acl_matrix`
FROM `sys_acl_actions`, `sys_acl_matrix`
WHERE `sys_acl_matrix`.`IDAction` = `sys_acl_actions`.`ID` AND `sys_acl_actions`.`Module` = @sName AND `Name` = 'send files';

-- ACL
INSERT INTO `sys_acl_actions` (`Module`, `Name`, `AdditionalParamName`, `Title`, `Desc`, `Countable`, `DisabledForLevels`) VALUES
(@sName, 'send files', NULL, '_bx_messenger_acl_action_send_files', '', 1, 0);
SET @iIdActionSendFiles = LAST_INSERT_ID();

SET @iUnauthenticated = 1;
SET @iAccount = 2;
SET @iStandard = 3;
SET @iUnconfirmed = 4;
SET @iPending = 5;
SET @iSuspended = 6;
SET @iModerator = 7;
SET @iAdministrator = 8;
SET @iPremium = 9;

INSERT INTO `sys_acl_matrix` (`IDLevel`, `IDAction`) VALUES
-- send files
(@iStandard, @iIdActionSendFiles),
(@iModerator, @iIdActionSendFiles),
(@iAdministrator, @iIdActionSendFiles),
(@iPremium, @iIdActionSendFiles);

INSERT INTO sys_acl_matrix (`IDLevel`, `IDAction`) SELECT `ID`, @iIdActionSendFiles FROM `sys_acl_levels` WHERE `ID` > @iPremium;