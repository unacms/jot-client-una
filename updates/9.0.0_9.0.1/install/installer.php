<?php
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 */

class BxMessengerUpdater extends BxDolStudioUpdater
{
    function __construct($aConfig)
	{
        parent::__construct($aConfig);
    }

	public function actionExecuteSql($sOperation)
    {
    	if($sOperation == 'install') {
    		if(!$this->oDb->isFieldExists('bx_messenger_users_info', 'star'))
        		$this->oDb->query("ALTER TABLE `bx_messenger_users_info` ADD `star` tinyint(1) NOT NULL default '0' AFTER `params`");
    	}

    	return parent::actionExecuteSql($sOperation);
    }
}
