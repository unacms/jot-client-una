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
			if(!$this->oDb->isFieldExists('bx_messenger_jots', 'vc'))
        		$this->oDb->query("ALTER TABLE `bx_messenger_jots` ADD `vc` int(11) NOT NULL default 0");
		}

    	return parent::actionExecuteSql($sOperation);
    }
	
	public function update($aParams)
    {
        $aResult = parent::update($aParams);
		if(!$aResult['result'])
			return $aResult;

        $oCacheUtilities = BxDolCacheUtilities::getInstance();
        $oCacheUtilities->clear('css');
        $oCacheUtilities->clear('js');
        $oCacheUtilities->clear('template');
        return $aResult;
    }
}
