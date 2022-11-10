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
            if(!$this->oDb->isFieldExists('bx_messenger_jot_reactions', 'id'))
                $this->oDb->query("ALTER TABLE `bx_messenger_jot_reactions` ADD `id` int(11) unsigned PRIMARY KEY AUTO_INCREMENT");

            if(!$this->oDb->isFieldExists('bx_messenger_lots', 'visibility'))
                $this->oDb->query("ALTER TABLE `bx_messenger_lots` ADD `visibility` tinyint(1) NOT NULL default 0");

            if($this->oDb->isIndexExists('bx_messenger_users_info', 'id')) {
               $this->oDb->query("ALTER TABLE `bx_messenger_users_info` CHANGE `lot_id` `lot_id` INT(11) UNSIGNED NOT NULL");
               $this->oDb->query("ALTER TABLE `bx_messenger_users_info` DROP INDEX `id`");
               $this->oDb->query("ALTER TABLE `bx_messenger_users_info` ADD PRIMARY KEY (`lot_id`,`user_id`)");
            }

            if(!$this->oDb->isFieldExists('bx_messenger_lots_settings', 'actions')) {
                $this->oDb->query("ALTER TABLE `bx_messenger_lots_settings` CHANGE `settings` `actions` varchar(255) NOT NULL default ''");
                $this->oDb->query("ALTER TABLE `bx_messenger_lots_settings` ADD `settings` varchar(255) NOT NULL default ''");
                $this->oDb->query("ALTER TABLE `bx_messenger_lots_settings` ADD `icon` int(11) NOT NULL default '0'");
				$this->oDb->query("ALTER TABLE `bx_messenger_lots_settings` DROP INDEX `id`");
                $this->oDb->query("ALTER TABLE `bx_messenger_lots_settings` ADD PRIMARY KEY (`lot_id`)");
            }
    
            if(!$this->oDb->isFieldExists('bx_messenger_unread_jots', 'id'))
                $this->oDb->query("ALTER TABLE `bx_messenger_unread_jots` ADD `id` int(11) unsigned PRIMARY KEY AUTO_INCREMENT");

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