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
			if(!$this->oDb->isFieldExists('bx_messenger_jots', 'last_edit'))
        		$this->oDb->query("ALTER TABLE `bx_messenger_jots` ADD `last_edit` int(11) NOT NULL default '0' AFTER `new_for`");

			if(!$this->oDb->isFieldExists('bx_messenger_jots', 'edit_by'))
        		$this->oDb->query("ALTER TABLE `bx_messenger_jots` ADD `edit_by` int(11) unsigned NOT NULL default '0' AFTER `last_edit`");

			if(!$this->oDb->isFieldExists('bx_messenger_jots', 'trash'))
        		$this->oDb->query("ALTER TABLE `bx_messenger_jots` ADD `trash` tinyint(1) unsigned NOT NULL default '0' AFTER `edit_by`");
		}

    	return parent::actionExecuteSql($sOperation);
    }
}
