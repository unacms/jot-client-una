<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
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
