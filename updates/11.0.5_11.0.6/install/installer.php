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
            if(!$this->oDb->isFieldExists('bx_messenger_lots', 'updated'))
                $this->oDb->query("ALTER TABLE `bx_messenger_lots` ADD `updated` int(11) NOT NULL default 0");

            if($this->oDb->isIndexExists('bx_messenger_lots', 'search_title'))
                $this->oDb->query("ALTER TABLE `bx_messenger_lots` DROP INDEX `search_title`");

            if($this->oDb->isIndexExists('bx_messenger_lots', 'search_url'))
                $this->oDb->query("ALTER TABLE `bx_messenger_lots` DROP INDEX `search_url`");

            if(!$this->oDb->isIndexExists('bx_messenger_jots', 'user_lot'))
                $this->oDb->query("ALTER TABLE `bx_messenger_jots` ADD KEY `user_lot` (`user_id`,`lot_id`)");
		}

    	return parent::actionExecuteSql($sOperation);
    }
	
	function update($aParams) {
       $aResult = parent::update($aParams);

       file_put_contents(BX_DIRECTORY_PATH_ROOT . 'messenger.log', print_r($aResult, true), FILE_APPEND);
	   if($aResult['result'])
			$this->convertUnreadMessagesToNewFormat();

       return $aResult;
    }

    function convertUnreadMessagesToNewFormat(){
        $this->oDb->query("TRUNCATE TABLE `bx_messenger_unread_jots`");
        $aRecords = $this->oDb->getAll("SELECT `id`, `lot_id` , `new_for`
                                              FROM `bx_messenger_jots`
                                              WHERE `new_for` NOT IN ('', 0) 
                                              ORDER BY `id`");

        $aProfiles = array();
        foreach($aRecords as &$aRecord){
            $aParticipants = explode(',', $aRecord['new_for']);
            if (!count($aParticipants))
                continue;

            $iJotId = (int)$aRecord['id'];
            $iLotId	= (int)$aRecord['lot_id'];
            foreach($aParticipants as &$iPart){
                if (!$iPart || (isset($aProfiles[$iPart]) && isset($aProfiles[$iPart][$iLotId])))
                    continue;

                $aProfiles[$iPart][$iLotId] = $iJotId;
            }
        }

        foreach($aProfiles as $iProfile => $aInfo){
            $aRows = array();
            foreach($aInfo as $iLotId => $iJot){
                $iCount = $this->oDb->getOne("SELECT COUNT(*) FROM `bx_messenger_jots` WHERE `id` >=:jot and `lot_id`=:lot", array('jot' => $iJot, 'lot' => $iLotId));
                if ($iCount)
                    $aRows[] = "($iJot, $iProfile, $iLotId, $iCount)";
            }

            if (!empty($aRows))
                $this->oDb->query("INSERT INTO `bx_messenger_unread_jots` (`first_jot_id`, `user_id`, `lot_id`, `unread_count`) VALUES " . implode(',', array_filter($aRows)));
        }

        return $this->oDb->query("UPDATE `bx_messenger_lots` AS `l`,
									(
										SELECT MAX( `created` ) as `created` , `lot_id`
										FROM `bx_messenger_jots`
										GROUP BY `lot_id`
									) AS `j`
									SET
										`l`.`updated` = `j`.`created`
									WHERE
										`l`.`id` = `j`.`lot_id`");
    }
}
