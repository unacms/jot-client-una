<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup		UnaModules
 *
 * @{
 */

/**
 * Database queries
 */ 
class BxMessengerDb extends BxBaseModTextDb
{
   private $CNF;
   function __construct(&$oConfig)
   {
		parent::__construct($oConfig);		
		$this->CNF = &$oConfig -> CNF;
	}
	
	/**
	* Get all lot by class name 
	*@param string $sClass
	*@return array lot info
	*/
	public function getLotByClass($sClass){
		if (!$sClass)
		    return '';

	    $sQuery = $this -> prepare("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_CLASS']}` = ? LIMIT 1", $sClass);
		return $this -> getRow($sQuery);
	}

	/**
	* Get all lot by page url
	*@param string $sUrl or the page
	*@return array lot info
	*/
	public function getLotByUrl($sUrl)
	{
		$sQuery = $this -> prepare("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_URL']}` = ? LIMIT 1", $sUrl);
		return $this -> getRow($sQuery);
	}

	/**
	* Get all lot by id
	*@param int $iId lot id
	*@return array lot info
	*/
	public function getLotInfoById($iId)
	{
		$sQuery = $this -> prepare("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = ? LIMIT 1", (int)$iId);
		return $this -> getRow($sQuery);
	}

	/**
	* Get lot by id or url
	*@param int $iId lot id
	*@param string $sUrl url or url of the page with talk 
	*@param int $iAuthor profile id 
	*@return array lot info
	*/
	public function getLotByIdOrUrl($iId, $sUrl, $iAuthorId = 0){
		if ($iId)
			return $this -> getLotInfoById($iId);
		
		if ($sUrl)
			return $this -> getLotByUrl($sUrl);
		
		return array();
	}

	/**
	* Check if is the member author of the lot or jot
	*@param int $iId lot id
	*@param int $iAuthor profile id 
	*@param boolean $bLotAuthor if true checks Lot, otherwise jot  
	*@return boolean
	*/
	public function isAuthor($iId, $iAuthorId, $bLotAuthor = true){
		if (!$iAuthorId)
			return false;
		
		if ($bLotAuthor)
			$sQuery = $this -> prepare("SELECT COUNT(*) FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = ? AND `{$this->CNF['FIELD_AUTHOR']}` = ? LIMIT 1", (int)$iId, (int)$iAuthorId);
		else
			$sQuery = $this -> prepare("SELECT COUNT(*) FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = ? AND `{$this->CNF['FIELD_MESSAGE_AUTHOR']}` = ? LIMIT 1", (int)$iId, (int)$iAuthorId);
			
		return $this -> getOne($sQuery) == 1;
	}

	/**
	* Deletes lot by lot Id
	*@param int $iLotId lot id
	*@return int affected rows
	*/
	public function deleteLot($iLotId){
		$aJots = $this -> getJotsByLotId($iLotId);
		foreach($aJots as $iKey => $aJot)
            $this->clearJotsConnections($aJot[$this->CNF['FIELD_MESSAGE_ID']]);

        $this->query("DELETE FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                                        WHERE `{$this->CNF['FIELD_NEW_LOT']}`=:lot                                        
                                                     ", array('lot' => $iLotId));

        $this -> query("DELETE FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = :id", array('id' => $iLotId));
        $this -> query("DELETE FROM `{$this->CNF['TABLE_LOT_SETTINGS']}` WHERE `{$this->CNF['FLS_ID']}` = :id", array('id' => $iLotId));
		$this -> query("DELETE FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :id", array('id' => $iLotId));

		return true;
	}

    /**
     * Clear lot history
     *@param int $iLotId lot id
     *@return boolean result
     */
    public function clearLot($iLotId){
        $aJots = $this -> getJotsByLotId($iLotId);
        foreach($aJots as $iKey => $aJot)
            $this->clearJotsConnections($aJot[$this->CNF['FIELD_MESSAGE_ID']]);

        return $this->query("DELETE FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :id", array('id' => $iLotId));
    }
	
	/**
	* Deletes jot
	*@param int iJotId jot id
	*@param int iProfileId member who deleted jot
	*@param boolean $bCompletely if true totally remove from the site 
	*@return int affected rows
	*/
	public function deleteJot($iJotId, $iProfileId, $bCompletely = false){
		$iJotId = (int)$iJotId;
		if ($bCompletely)
		{
            $this->clearJotsConnections($iJotId);
			return $this -> query("DELETE FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId));
		}		
		
		return $this -> query("UPDATE `{$this->CNF['TABLE_MESSAGES']}` 
								SET 
									`{$this->CNF['FIELD_MESSAGE_TRASH']}` = 1,
									`{$this->CNF['FIELD_MESSAGE_LAST_EDIT']}` = UNIX_TIMESTAMP(),
									`{$this->CNF['FIELD_MESSAGE_EDIT_BY']}` = :profile
								WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId, 'profile' => $iProfileId));
	}	

	public function clearJotsConnections($iJotId){
        $this->removeFilesByJotId($iJotId);
        $this->deleteJotReactions($iJotId);

        $aUnreadJots = $this->getColumn("SELECT `{$this->CNF['FIELD_NEW_PROFILE']}` 
                                            FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                            WHERE `{$this->CNF['FIELD_NEW_JOT']}`=:jot", array('jot' => $iJotId));
        foreach($aUnreadJots as &$iProfileId)
           $this->deleteNewJot($iProfileId, false, $iJotId);
    }

	public function updateJot($iJotId, $sField, $mixedValue){
	    return $this -> query("UPDATE `{$this->CNF['TABLE_MESSAGES']}` 
								SET 
									`{$sField}` =:value
								WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId, 'value' => $mixedValue));
       }

	/**
	* Removes participant from the participants list
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@return int/false 
	*/	
	public function leaveLot($iLotId, $iParticipant){
        $this->deleteNewJot($iParticipant, $iLotId);
		return $this -> removeParticipant($iLotId, $iParticipant);
	}
	
	/**
	* Get Lot(Talk) settings for a member
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@param string $sName name the option
	*@param boolean $bParams get from params array, otherwise from table's field
	*@return mixed option value
	*/
	private function getParams($iLotId, $iParticipant, $sName = 0, $bParams = false){
		$sQuery = $this -> prepare("SELECT * FROM `{$this->CNF['TABLE_USERS_INFO']}` 
									WHERE `{$this->CNF['FIELD_INFO_LOT_ID']}` = ? AND `{$this->CNF['FIELD_INFO_USER_ID']}` = ? 
									LIMIT 1", (int)$iLotId, (int)$iParticipant);
		$aInfo = $this -> getRow($sQuery);
		if (empty($aInfo))
			return false;
		
		if (!$sName)
			return $aInfo;
		
		if (!$bParams){	
			$aParams = unserialize($aInfo[$this->CNF['FIELD_INFO_PARAMS']]);
			return isset($aParams[$sName]) ? $aParams[$sName] : 0;
		}
				
		return $aInfo[$sName];
	}

	/**
	* Save Lot(Talk) settings for a member
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@param array $aParams options list
	*@param boolean $bParams get from  params array otherwise as table field
	*@return int affected rows
	*/
	private function setParams($iLotId, $iParticipant, $sName, $sValue, $bParams = false){
		$aParams = $this -> getParams($iLotId, $iParticipant);
	
		if (!$bParams)
		{
			if (empty($aParams))
				return $this -> query("INSERT INTO `{$this->CNF['TABLE_USERS_INFO']}` SET `{$this->CNF['FIELD_INFO_PARAMS']}` = :values, `{$this->CNF['FIELD_INFO_LOT_ID']}` = :id, `{$this->CNF['FIELD_INFO_USER_ID']}` = :user", array('user' => $iParticipant, 'id' => $iLotId, 'values' => serialize(array($sName => $sValue))));
			
			return $this -> query("UPDATE `{$this->CNF['TABLE_USERS_INFO']}` SET `{$this->CNF['FIELD_INFO_PARAMS']}` = :values WHERE `{$this->CNF['FIELD_INFO_LOT_ID']}` = :id AND `{$this->CNF['FIELD_INFO_USER_ID']}` = :user", array('user' => $iParticipant, 'id' => $iLotId, 'values' => serialize(array($sName => $sValue))));
		}
		
		if (empty($aParams))
			return $this -> query("INSERT INTO `{$this->CNF['TABLE_USERS_INFO']}` SET `{$sName}` = :value, `{$this->CNF['FIELD_INFO_LOT_ID']}` = :id, `{$this->CNF['FIELD_INFO_USER_ID']}` = :user", array('user' => $iParticipant, 'id' => $iLotId, 'value' => $sValue));		
		
		return $this -> query("UPDATE `{$this->CNF['TABLE_USERS_INFO']}` SET `{$sName}` = :value WHERE `{$this->CNF['FIELD_INFO_LOT_ID']}` = :id AND `{$this->CNF['FIELD_INFO_USER_ID']}` = :user", array('user' => $iParticipant, 'id' => $iLotId, 'value' => $sValue));		
	}

	/**
	* Make the lot mute for a member
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@return int set value
	*/
	public function muteLot($iLotId, $iParticipant){
		$iCurrentNotification = (int)$this -> getParams($iLotId, $iParticipant, 'notification');
		$iNotification = (int)!$iCurrentNotification;
		
		$this -> setParams($iLotId, $iParticipant, 'notification', $iNotification);		
		return $iNotification;
	}
	
	/**
	* Mark the lot with star
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@return int set
	*/
	public function starLot($iLotId, $iParticipant){
		$iCurrent = (int)$this -> getParams($iLotId, $iParticipant, 'star', true);
		$iNew = (int)!$iCurrent;
		
		$this -> setParams($iLotId, $iParticipant, 'star', $iNew, true);
		return $iNew;
	}
	
	public function isStarred($iLotId, $iParticipant){
		return $this -> getParams($iLotId, $iParticipant, 'star', true);
	}
	/**
	* Check if the lot is mute for a member
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@return boolean 
	*/
	public function isMuted($iLotId, $iParticipant){
		return $this -> getParams($iLotId, $iParticipant, 'notification');
	}

	/**
	* Save participants list for the lot
	*@param int $iLotId lot id
	*@param array $aParticipants
	*@return int affected rows
	*/
	public function savePariticipantsList($iLotId, $aParticipants){
		$sParticipants = '';
		if (!empty($aParticipants))
		{
			$aParticipants = array_map('intval', $aParticipants);
			$sParticipants = implode(',', $aParticipants);
		}
		
		return $this -> query("UPDATE `{$this->CNF['TABLE_ENTRIES']}` SET `{$this->CNF['FIELD_PARTICIPANTS']}` = :parts WHERE `{$this->CNF['FIELD_ID']}` = :id", array('parts' => $sParticipants, 'id' => $iLotId));
	}

	public function findThePageOwner($mixedPage){
	    $iAuthorId = 0;
	    if (!$mixedPage)
	        return $iAuthorId;

	    if (is_string($mixedPage))
	        parse_str($mixedPage, $aUrl);
        else if (is_array($mixedPage))
            $aUrl = $mixedPage;

	    if (empty($aUrl) || !isset($aUrl['id']) || !isset($aUrl['i']))
	        return $iAuthorId;

	    $sModule = $this->getOne("SELECT `module` FROM `sys_objects_page` WHERE LOWER(`uri`)=:uri", array('uri' => strtolower($aUrl['i'])));
	    if (!$sModule)
	        return $iAuthorId;

	    $aTable = $this->getRow("SELECT `TriggerTable`, `TriggerFieldId`, `TriggerFieldAuthor` 
                                                             FROM `sys_objects_cmts`
                                                             WHERE `Module`=:module LIMIT 1", array('module' => $sModule));
	    if (empty($aTable))
	        return $iAuthorId;

	    if (isset($aTable['TriggerTable']) && isset($aTable['TriggerFieldId']) && isset($aTable['TriggerFieldAuthor']))
	        return $this->getOne("SELECT `{$aTable['TriggerFieldAuthor']}` 
                                            FROM `{$aTable['TriggerTable']}` 
                                            WHERE `{$aTable['TriggerFieldId']}`=:id",
                                            array('id' => $aUrl['id']));

	    return $iAuthorId;
    }

    function createLot($iProfileId, $sUrl = '', $sTitle = '', $sType = '', $aParticipants = array()){
        $iAuthorId = $this->findThePageOwner($sUrl);
        $iAuthorId = $iAuthorId ? $iAuthorId : (int)$iProfileId;
        $iLotId = $this->createNewLot($iAuthorId, $sTitle, $sType, $sUrl, $aParticipants);
        if (!$iLotId)
            return false;

        bx_alert($this->_oConfig->getObject('alert'), 'create_lot', $iLotId, $iProfileId);
        foreach($aParticipants as &$iParticipant) {
            if ((int)$iParticipant === (int)$iProfileId)
                continue;

            bx_alert($this->_oConfig->getObject('alert'), 'add_part', $iParticipant, $iProfileId, array('lot_id' => $iLotId, 'author_id' => $iAuthorId));
        }
        
        return $iLotId;
    }
	/**
	* Save message for participants to database
	*@param array $aData lot settings
	*@param array $aParticipants participants list, if it empty then used default for lot
	*@return int affected rows
	*/
    public function saveMessage($aData, $aParticipants = array())
    {
        $iLotId = isset($aData['lot']) ? (int)$aData['lot'] : 0;
        $aLotInfo = $iLotId ? $this->getLotInfoById($iLotId) : array();
		
        if (empty($aLotInfo))
            $aLotInfo = $this->getLotByUrlAndParticipantsList($aData['url'], $aParticipants, $aData['type']);

        if ($iLotId && $aData['type'] == BX_IM_TYPE_PRIVATE && !$this->isParticipant($iLotId, $aData['member_id']))
            return false;

        if (empty($aLotInfo))
            $iLotId = $this->createLot((int)$aData['member_id'], $aData['url'], $aData['title'], $aData['type'], $aParticipants);
        else
            $iLotId = $aLotInfo[$this->CNF['FIELD_ID']];

        if (($aData['type'] != BX_IM_TYPE_PRIVATE || $this->isAuthor($iLotId, $aData['member_id'])) && !$this->isParticipant($iLotId, $aData['member_id'], true)) {
            if ($this->addMemberToParticipantsList($iLotId, $aData['member_id']))
                bx_alert($this->_oConfig->getObject('alert'), 'participant_joined', $iLotId, $aData['member_id'],
                    array(
                        'url' => $aData['url'],
                        'object_author_id' => !empty($aLotInfo[$this->CNF['FIELD_AUTHOR']]) ? $aLotInfo[$this->CNF['FIELD_AUTHOR']] : $this->findThePageOwner($aData['url'])
                    ));
        }

      	$aData['message'] = clear_xss($aData['message']);
	    return $this->addJot($iLotId, $aData['message'], $aData['member_id']);
	}	

	/**
	* Save message for participants to database
	*@param string $sUrl of the page with lot
	*@param array $aParticipants participants list
	*@param int $iType lot type
	*@return array Lot info
	*/
	public function getLotByUrlAndParticipantsList($sUrl = '', $aParticipants = array(), $iType = BX_IM_TYPE_PRIVATE){
		if ($iType != BX_IM_TYPE_PRIVATE && $sUrl && $aLot = $this -> getLotByUrl($sUrl)) 
			return $aLot;
		
		$aResult = array();
		if (!empty($aParticipants)){
			$sWhere = " AND `{$this->CNF['FIELD_AUTHOR']}` IN (" . $this -> implode_escape($aParticipants) . ")";

			$aLots = $this -> getAll("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `type` = :type {$sWhere}", array('type' => $iType));

			if (!empty($aLots))
			{
				foreach($aLots as $iKey => $aValue)
				{
					 $aParticipantsList = $this -> getParticipantsList($aValue[$this->CNF['FIELD_ID']]);
					 if (empty($aParticipantsList) || count($aParticipantsList) != count($aParticipants)) continue;
					 
					 sort($aParticipantsList);
					 sort($aParticipants);
					 
					 if (array_values($aParticipantsList) == array_values($aParticipants)){
						$aResult = $aValue;
					 }	
				}					
			}
		}

		return $aResult;
	}

	/**
	* Mark message as read in lot history
	*@param int $iJotId message id
	*@param int iProfileId	
	*/
	public function readMessage($iJotId, $iProfileId){
        $aJotInfo = $this->getJotById($iJotId);
	    if (!empty($aJotInfo) && $this->deleteNewJot($iProfileId, $aJotInfo[$this->CNF['FIELD_MESSAGE_FK']], $iJotId))
            bx_alert($this->_oConfig->getObject('alert'), 'read_jot', $iJotId, $iProfileId, array('author_id' => $aJotInfo[$this->CNF['FIELD_MESSAGE_AUTHOR']], 'lot_id' => $aJotInfo[$this->CNF['FIELD_MESSAGE_FK']]));
    }

    /**
     * Mark all message as read in lot history for member
     * @param int $iLot lot id
     * @param int iProfileId
     * @return false
     */
	public function readAllMessages($iLot, $iProfileId){
		$CNF = &$this->_oConfig->CNF;
	    $aNew = $this->getNewJots($iProfileId, $iLot);
	    if (empty($aNew))
	        return false;

	    bx_alert($this->_oConfig->getObject('alert'), 'read_bulk_jot', $iLot, $iProfileId,
        array(
               'first_jot' => $aNew[$CNF['FIELD_NEW_JOT']],
               'count' => $aNew[$CNF['FIELD_NEW_JOT']],
               'lot_id' => $iLot
             ));

        $this->deleteNewJot($iProfileId, $iLot);
        $this->markNotificationAsRead($iProfileId, $iLot);
	}

	/**
	*Add new new jot to database  
	*@param int $iLotID  lot id
	*@param string $sMessage posted message 
	*@param int iProfile Id	owner of the message
	*@return  int affected rows
	*/
	public function addJot($iLotId, $sMessage, $iProfileId)
	{
		$sQuery = $this->prepare("INSERT INTO `{$this->CNF['TABLE_MESSAGES']}` 
												SET  `{$this->CNF['FIELD_MESSAGE']}` = ?, 
													 `{$this->CNF['FIELD_MESSAGE_FK']}` = ?, 
													 `{$this->CNF['FIELD_MESSAGE_AUTHOR']}` = ?,
													 `{$this->CNF['FIELD_MESSAGE_ADDED']}` = UNIX_TIMESTAMP()", $sMessage, $iLotId, $iProfileId);

		if (!$this->query($sQuery))
		    return false;
		
		$iJotId = $this -> lastId();
		$aParticipants = $this -> getParticipantsList($iLotId, true, $iProfileId);
		$this->markAsNewJot($aParticipants, $iLotId, $iJotId);
		return $iJotId;
	}
		
	/**
	*Create new chat/lot with list of participants
	*@param int iProfileId owner of the lot
	*@param string $sTitle lot title
	*@param int $iType type of the lot
	*@param string $sUrl url of the page
	*@param array $aParticipants list of participants
	*@return  int affected rows
	*/
	public function createNewLot($iProfileId, $sTitle, $iType, $sUrl = '', &$aParticipants = array())
	{
		$mixedParticipants = !empty($aParticipants) ? implode(',', $aParticipants) : $iProfileId;
		$sQuery = $this->prepare("INSERT INTO `{$this->CNF['TABLE_ENTRIES']}` 
												SET  `{$this->CNF['FIELD_TITLE']}` = ?, 
													 `{$this->CNF['FIELD_TYPE']}` = ?, 
													 `{$this->CNF['FIELD_AUTHOR']}` = ?,
													 `{$this->CNF['FIELD_ADDED']}` = UNIX_TIMESTAMP(),
													 `{$this->CNF['FIELD_PARTICIPANTS']}` = ?,
													 `{$this->CNF['FIELD_URL']}` = ?", $sTitle, $iType, $iProfileId, $mixedParticipants, $sUrl);
		
		return $this->query($sQuery) ? $this -> lastId() : false;
	}
	
	/**
	*Get list of types of member lots
	*@param int iProfileId
	*@param string $sParam keyword to filter lots
	*@return  array found lots types with lots count per each type
	*/
	public function getMemberLotsTypes($iProfileId, $sParam = ''){
		$sWhere = '';
		$aWhere = array();
		
		if ($iProfileId){
			$aSWhere[] = "FIND_IN_SET(:profile, `{$this->CNF['FIELD_PARTICIPANTS']}`)";
			$aWhere = array('profile' => $iProfileId);
		}		
		
		if ($sParam){
			$sParam = "%{$sParam}%";
			$aSWhere[] = " (`{$this->CNF['FIELD_TITLE']}` LIKE :title OR `{$this->CNF['FIELD_URL']}` LIKE :url OR `{$this->CNF['FIELD_TYPE']}` LIKE :type)";
			$aWhere = array_merge($aWhere, array('title' => $sParam, 'url' => $sParam, 'type' => $sParam));
		}	
				
		if (!empty($aWhere))
			$sWhere = "WHERE (" . implode(' AND ', $aSWhere) . ')';
			
		return $this-> getPairs("SELECT `{$this->CNF['FIELD_TYPE']}`, COUNT(*) as `count` 
			FROM `{$this->CNF['TABLE_ENTRIES']}` 
			{$sWhere}
			GROUP BY `{$this->CNF['FIELD_TYPE']}`", $this->CNF['FIELD_TYPE'], 'count', $aWhere);
	}

	/**
	*Get list of all existed types 
	*@return  array types
	*/
	public function getAllLotsTypes(){
		return $this -> getAll("SELECT * FROM `{$this->CNF['TABLE_TYPES']}` ORDER BY `{$this->CNF['FIELD_TYPE_ID']}` ASC");
	}
	
	/**
	*Get list of all existed types as pairs: Id - Value
	*@return  array types
	*/
	public function getLotsTypesPairs(){
		return $this -> getPairs("SELECT * FROM `{$this->CNF['TABLE_TYPES']}` ORDER BY `{$this->CNF['FIELD_TYPE_ID']}`", $this->CNF['FIELD_TYPE_ID'], $this->CNF['FIELD_TYPE_NAME']);
	}

	/**
	* Check if this ype of lot exists
	*@param int $iType type of the lot
	*@return boolean 
	*/
	public function isLotType($iType){
		return $this -> getOne("SELECT COUNT(*) FROM `{$this->CNF['TABLE_TYPES']}` WHERE `{$this->CNF['FIELD_TYPE_ID']}` = :type LIMIT 1", array('type' => $iType)) == 1;
	}
	
	/**
	* Get lot's participant list 
	*@param int $iLotId lot id
	*@param boolean $bArray if true, returns result as array, otherwise string with ids separated commas
	*@param int $bExcludeProfile allows to exclude profile from the list, usually it is owner
	*@return string/array
	*/
	public function getParticipantsList($iLotId, $bArray = true, $bExcludeProfile = 0){
		$sParticipants = $this -> getOne("SELECT `{$this->CNF['FIELD_PARTICIPANTS']}` FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = :value", array('value' => $iLotId));
		
		if (!$sParticipants) 
				return array();
		
		$aParticipants = explode(',', $sParticipants);
		if ($bExcludeProfile && ($iId = array_search($bExcludeProfile, $aParticipants)) !== FALSE)
			unset($aParticipants[$iId]);
		
		return !$bArray ? implode(',', $aParticipants) : $aParticipants;
	}

	/**
	* Check if it is participant of the lot
	*@param int $iLotId lot id
	*@param int $iParticipantId profile id
	*@param boolean $bAsParticipant if true to check only participants list without lot owner
	*@return boolean 
	*/
	public function isParticipant($iLotId, $iParticipantId, $bAsParticipant = false)
	{
		$aParticipants = $this -> getParticipantsList($iLotId);
		if (array_search($iParticipantId, $aParticipants) !== FALSE)
			return true;
		
		return !$bAsParticipant ? $this -> getOne("SELECT COUNT(*) FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = :lot AND `{$this->CNF['FIELD_AUTHOR']}` = :author LIMIT 1", array('lot' => (int)$iLotId, 'author' => (int)$iParticipantId)) == 1 : false;
	}
	
	/**
	* Add profile to lot's participants list
	*@param int $iLotId lot id
	*@param int $iParticipantId profile id
	*@return int affected rows
	*/
	public function addMemberToParticipantsList($iLotId, $iParticipantId){
		$sParticipants = $this -> getParticipantsList($iLotId, false /* as string list */);
		$sParticipants = $sParticipants ? "{$sParticipants},{$iParticipantId}" : $iParticipantId;
		return $this -> query("UPDATE `{$this->CNF['TABLE_ENTRIES']}` SET `{$this->CNF['FIELD_PARTICIPANTS']}` = :parts WHERE `{$this->CNF['FIELD_ID']}` = :id", array('parts' => $sParticipants, 'id' => $iLotId));
	}	

	/**
	* Remove profile from participants list
	*@param int $iLotId lot id
	*@param int $iParticipantId profile id
	*@return mixed false or affected rows
	*/
	private function removeParticipant($iLotId, $iParticipantId){
		$aParticipants = $this -> getParticipantsList($iLotId);
		$iKey = array_search($iParticipantId, $aParticipants);
		
		if ($iKey !== FALSE){
			unset($aParticipants[$iKey]);
			return $this -> savePariticipantsList($iLotId, $aParticipants);
		}
			
		return false;
	}

	/**
	* Get jots list for specified lot
	*@param int $iLotId lot id
	*@param int $iStart jot id from which start to get jots
	*@param string $sMode just posted or old jots from history 
	*@param int $iLimit number of jots to get
	*@return array of the jots
	*/
	public function getJotsByLotId($iLotId, $iStart = BX_IM_EMPTY, $sMode = 'new', $iLimit = BX_IM_EMPTY, $bInclude = false){
		$sLimit = '';
		$aSWhere[] = "`{$this->CNF['FIELD_MESSAGE_FK']}` = :lot_id ";
		$aBindings['lot_id'] = (int)$iLotId;
		$sInsideOrder = $sMode == 'all' ? 'ASC' : 'DESC';

		if ($iStart && $sMode !== 'all')
		{ 
			$sEqual = '';
			if ($bInclude)
				$sEqual='=';
				
			$aSWhere[] = "`{$this->CNF['FIELD_MESSAGE_ID']}` " . ($sMode == 'new' ? '>' . $sEqual : '<' . $sEqual) . " :start ";
			$aBindings['start'] = (int)$iStart;

            if ($sMode == 'new')
                $sInsideOrder = 'ASC';
        }

		if ($iLimit)
		{ 
			$sLimit = "LIMIT :limit";
			$aBindings['limit'] = (int)$iLimit;
		}

		if (!empty($aBindings))
			$sWhere = 'WHERE ' . implode(' AND ', $aSWhere);

		$sQuery = "SELECT * FROM `{$this->CNF['TABLE_MESSAGES']}`
									{$sWhere}	
									ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}` {$sInsideOrder}
									$sLimit";
									
		return $this -> getAll( $iStart && $sMode == 'new' ? $sQuery : "({$sQuery}) ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}`", $aBindings);					
	}

    public function getPrevJot($iLotId, $iStart){
        $sQuery = "SELECT * FROM `{$this->CNF['TABLE_MESSAGES']}`
									WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :lot_id AND `{$this->CNF['FIELD_MESSAGE_ID']}` < :jot
									ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}` DESC
									LIMIT 1";

        return $this -> getRow(  $sQuery, array('lot_id' => (int)$iLotId, 'jot' => (int)$iStart));
    }

	function getLotJotsCount($iLotId){
        if (!$iLotId)
            return false;

        // find the user with most number of unread messages
        $iUnread = $this->getOne("SELECT `{$this->CNF['FIELD_NEW_UNREAD']}` FROM `{$this->CNF['TABLE_NEW_MESSAGES']}`
                                      WHERE `{$this->CNF['FIELD_NEW_LOT']}`=:id
                                      ORDER BY `{$this->CNF['FIELD_NEW_UNREAD']}` DESC
                                      LIMIT 1", array('id' => $iLotId));

        $iTotal = $this-> getOne( "SELECT COUNT(*)
                                             FROM `{$this->CNF['TABLE_MESSAGES']}`                                             
                                             WHERE `{$this->CNF['FIELD_MESSAGE_FK']}`=:id 
                                             ", array('id' => $iLotId));

        return array('unread' => $iUnread, 'read' => ($iTotal - $iUnread));
    }
	/**
	* Get lot id by jot id 
	*@param int $iJotId jot id
	*@param boolean bIdOnly return id or array with info
	*@return mixed lot id or lot info in array
	*/
    public function getLotByJotId($iJotId, $bIdOnly = true){
        $iLotId = $this -> getOne("SELECT `{$this->CNF['FIELD_MESSAGE_FK']}`
						FROM `{$this->CNF['TABLE_MESSAGES']}` 
						WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :jot LIMIT 1", array('jot' => $iJotId));

        if (!$iLotId)
            return false;

        return $bIdOnly ? $iLotId : $this -> getRow("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` 
						WHERE `{$this->CNF['FIELD_ID']}` = :lot LIMIT 1", array('lot' => $iLotId));
    }

	/**
	* Get the latest posted jot(message)
	*@param int $iLotId lot id
	*@param int $iProfileId if not specified the just latest jot of any member
	*@param boolean $bNotTrash don't show trash message
	*@return array with jot info
	*/
	public function getLatestJot($iLotId, $iProfileId = 0, $bNotTrash = true){
		$sWhere = '';
		$aWhere['lot'] = $iLotId;
		
		if ($iProfileId)
		{
			$sWhere = " AND `{$this->CNF['FIELD_MESSAGE_AUTHOR']}` = :profile"; 
			$aWhere['profile'] = $iProfileId;
		}
		
		if ($bNotTrash)
			$sWhere .= " AND `{$this->CNF['FIELD_MESSAGE_TRASH']}` = 0"; 			
		
		return $this -> getRow("SELECT *
			FROM `{$this->CNF['TABLE_MESSAGES']}` 
			WHERE  `{$this->CNF['FIELD_MESSAGE_FK']}` = :lot {$sWhere}
			ORDER BY `{$this->CNF['FIELD_MESSAGE_ADDED']}` DESC
			LIMIT 1", $aWhere);
	}
	
	/**
	* Get jot(message) by id
	*@param int $iJotId id
	*@return array with jot info
	*/
	public function getJotById($iJotId){
		$sQuery = $this -> prepare("SELECT * FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = ?", $iJotId);
		return $this -> getRow($sQuery);
	}

    /**
     * Get messages number between two messages
     * @param $iLotId
     * @param $iJotStart
     * @param $iJotEnd
     * @return array with jot info
     */
    public function getJotsNumber($iLotId, $iJotStart, $iJotEnd = 0){
        $aWhere = array('id' => $iLotId, 'start' => $iJotStart);
        $sWhere = "`{$this->CNF['FIELD_MESSAGE_ID']}` > :start";
        if ($iJotEnd) {
            $sWhere = "`{$this->CNF['FIELD_MESSAGE_ID']}` BETWEEN :start AND :end";
            $aWhere['end'] = $iJotEnd;
        }

        return $this -> getOne("SELECT COUNT(*) FROM `{$this->CNF['TABLE_MESSAGES']}` 
                            WHERE `{$this->CNF['FIELD_MESSAGE_FK']}`=:id AND {$sWhere} 
                            ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}` ASC
                            ", $aWhere);
    }

	/**
	* Get all pages with comments block	
	*@return array list with pages
	*/	
	public function getPagesWithComments(){
		return $this -> getPairs("SELECT * FROM `sys_objects_cmts`", 'Name', 'BaseUrl');
	}

	/**
	* Check if the page already contains messenger block
	*@param $sPage page name 
	*@return boolean
	*/
	public function isBlockAdded($sPage){
		$sPage = bx_process_input($sPage);
		$sQuery = $this -> prepare("SELECT COUNT(*) FROM `sys_pages_blocks` WHERE `object` = ? AND `title` = '_bx_messenger_page_block_title_messenger'", $sPage);
		
		return $this -> getOne($sQuery) == 1;
	}

	/**
	* Add messenger block to the page
	*@param $sPage page name
	*@return affected rows
	*/
	public function addMessengerBlock($sPage){
		$sPage = bx_process_input($sPage);
		$aInfo = $this -> findCommentsBlock($sPage);
		
		if (!empty($aInfo))
				return $this -> query("INSERT INTO `sys_pages_blocks` (`object`, `cell_id`, `module`, `title`, `designbox_id`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
							  (:page, :cell, 'bx_messenger', '_bx_messenger_page_block_title_messenger', 0, 2147483647, 'service', 'a:3:{s:6:\"module\";s:12:\"bx_messenger\";s:6:\"method\";s:19:\"get_block_messenger\";s:6:\"params\";a:1:{i:0;s:6:\"{type}\";}}', 0, 0, :order, 0)",
							  array('page' => $sPage, 'cell' => $aInfo['cell_id'], 'order' => $aInfo['order'] + 1));

		return $this -> query("INSERT INTO `sys_pages_blocks` (`object`, `cell_id`, `module`, `title`, `designbox_id`, `visible_for_levels`, `type`, `content`, `deletable`, `copyable`, `order`, `active`) VALUES
							  (:page, 1, 'bx_messenger', '_bx_messenger_page_block_title_messenger', 0, 2147483647, 'service', 'a:3:{s:6:\"module\";s:12:\"bx_messenger\";s:6:\"method\";s:19:\"get_block_messenger\";s:6:\"params\";a:1:{i:0;s:6:\"{type}\";}}', 0, 0, 0, 0)",
							   array('page' => $sPage));
	}

	/**
	* Find comments block on the page
	*@param $sPage page name
	*@return array block info
	*/
	private function findCommentsBlock($sPage){
		$sPage = bx_process_input($sPage);
		
		$sQuery = $this -> prepare("SELECT * FROM `sys_pages_blocks` WHERE `object` = ? AND `title` LIKE '%comments%'", $sPage);
		return $this -> getRow($sQuery);
	}
	
	public function getUnreadJotsMessagesCount($iProfileId, $iLotId){
		if (!(int)$iLotId)
		    return false;
		
		return $this->getNewJots($iProfileId, $iLotId, true);
	}

    function getLotsWithUnreadMessages($iProfileId){
	     if (!$iProfileId)
	         return false;

         return $this->getPairs("SELECT `{$this->CNF['FIELD_NEW_UNREAD']}`, `{$this->CNF['FIELD_NEW_LOT']}` 
                                      FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                      WHERE `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile AND `{$this->CNF['FIELD_NEW_UNREAD']}` > 0                                       
                                      ", $this->CNF['FIELD_NEW_LOT'], $this->CNF['FIELD_NEW_UNREAD'], array('profile' => $iProfileId));
    }

	function getNewJots($iProfileId, $iLotId, $bCount = false){
        $aInfo = $this-> getRow("SELECT * 
                                           FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                           WHERE `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile AND `{$this->CNF['FIELD_NEW_LOT']}`=:lot
                                          ", array('lot' => $iLotId, 'profile' => $iProfileId));
        if (empty($aInfo))
            return 0;

        return $bCount ? (int)$aInfo[$this->CNF['FIELD_NEW_UNREAD']] : $aInfo;
    }

    function getForWhomJotIsNew($iLotId, $iJotId){
	    if (!$iLotId && !$iJotId)
	        return array();

        return $this->getColumn("SELECT `{$this->CNF['FIELD_NEW_PROFILE']}` 
                                            FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                            WHERE `{$this->CNF['FIELD_NEW_JOT']}` <= :jot AND `{$this->CNF['FIELD_NEW_LOT']}`=:lot
                                         ", array('jot' => $iJotId, 'lot' => $iLotId));
    }

    private function addNewJotItem($iProfileId, $iLotId, $iJotId){
	    if (!(int)$iProfileId)
	        return false;

        $iJotNumber = 0;
        $iLastJot = $iJotId;
        $aJotInfo = $this->getNewJots($iProfileId, $iLotId);
	    if (!empty($aJotInfo)) {
            $iJotNumber = (int)$aJotInfo[$this->CNF['FIELD_NEW_UNREAD']];
            $iLastJot = (int)$aJotInfo[$this->CNF['FIELD_NEW_JOT']];
        }

	    return $this-> query("REPLACE INTO `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                            SET 
                                            `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile,
                                            `{$this->CNF['FIELD_NEW_UNREAD']}`=:number,
                                            `{$this->CNF['FIELD_NEW_LOT']}`=:lot,
                                            `{$this->CNF['FIELD_NEW_JOT']}`=:jot
                                         ", array(
            'profile' => $iProfileId,
            'lot' => $iLotId,
            'number' => $iJotNumber + 1,
            'jot' => $iLastJot
        ));
    }
    function markAsNewJot($mixedProfile, $iLotId, $iJotId){
	    if (is_array($mixedProfile)){
	        foreach($mixedProfile as &$iProfileId)
                $this->addNewJotItem($iProfileId, $iLotId, $iJotId);
        } elseif ((int)$mixedProfile)
                $this->addNewJotItem($mixedProfile, $iLotId, $iJotId);

        return $this -> query("UPDATE `{$this->CNF['TABLE_ENTRIES']}` 
                                            SET `{$this->CNF['FIELD_UPDATED']}` = UNIX_TIMESTAMP() 
                                            WHERE `{$this->CNF['FIELD_ID']}` = :id", array('id' => $iLotId));
    }

    function deleteNewJot($iProfileId, $iLotId = 0, $iJotId = 0){
        if ($iJotId) {
           $iLotId = $iLotId ? $iLotId : $this->getLotByJotId($iJotId);
           $aNewJotInfo = $this->getNewJots($iProfileId, $iLotId);

           if (!empty($aNewJotInfo) && (int)$aNewJotInfo[$this->CNF['FIELD_NEW_JOT']] > (int)$iJotId)
                return false;

           $iCount = (int)$this->getOne("SELECT COUNT(*) FROM `{$this->CNF['TABLE_MESSAGES']}`
									WHERE `{$this->CNF['FIELD_MESSAGE_FK']}`=:lot AND `{$this->CNF['FIELD_MESSAGE_ID']}` > :jot
									", array('lot' => $iLotId, 'jot' => $iJotId));
           if ($iCount)
               return $this-> query("REPLACE INTO `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                            SET 
                                            `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile,
                                            `{$this->CNF['FIELD_NEW_UNREAD']}`=:number,
                                            `{$this->CNF['FIELD_NEW_JOT']}`=:jot,
                                            `{$this->CNF['FIELD_NEW_LOT']}`=:lot                                            
                                         ", array(
                                            'profile' => $iProfileId,
                                            'lot' => $iLotId,
                                            'number' => $iCount,
                                            'jot' => $iJotId
                                        ));
        }

	    return $iLotId ?
                        $this->query("DELETE FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                                        WHERE `{$this->CNF['FIELD_NEW_LOT']}`=:lot AND `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile                                        
                                                     ", array('profile' => $iProfileId, 'lot' => $iLotId))
                       :
                        $this-> query("DELETE FROM `{$this->CNF['TABLE_NEW_MESSAGES']}` 
                                                        WHERE `{$this->CNF['FIELD_NEW_PROFILE']}`=:profile                                        
                                                     ", array('profile' => $iProfileId));
	}
		
    public function searchMessage($sParam, $iProfileId = 0, $iLotId = 0){
	   $aResult = array();
	    if (!$sParam)
	       return $aResult;

	   $aParams = preg_split('/[\s]/', $sParam, -1, PREG_SPLIT_NO_EMPTY);
	   $sCriteria = implode('%', $aParams);
       $aWhere = array('criteria' => "%{$sCriteria}%");

		if ($iLotId){
            $sWhere = " AND `j`.`{$this->CNF['FIELD_MESSAGE_FK']}`=:id";
            $aWhere['id'] = (int)$iLotId;
        } else
            if ($iProfileId)
            {
                $sWhere = " AND (`l`.`{$this->CNF['FIELD_PARTICIPANTS']}` REGEXP '(^|,){$iProfileId}(,|$)' OR `l`.`{$this->CNF['FIELD_AUTHOR']}`=:profile)";
                $aWhere['profile'] = (int)$iProfileId;
		}

       return $this->getColumn("SELECT `l`.`{$this->CNF['FIELD_ID']}`
			 FROM `{$this->CNF['TABLE_MESSAGES']}` as `j`
			 RIGHT JOIN `{$this->CNF['TABLE_ENTRIES']}` as `l` on `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}` 
             WHERE `j`.`{$this->CNF['FIELD_MESSAGE']}` LIKE :criteria {$sWhere}
             GROUP BY `j`.`{$this->CNF['FIELD_MESSAGE_FK']}`", $aWhere);
	}
	/**
	* Get all member's lots
	*@param int $iProfileId
    *@param int $iLotId lot id
	*@param string $sParam search keyword
	*@param int $iType
	*@return array list of lots
	*/
	public function getMyLots($iProfileId, $iLotId = 0, $sParam = '', $iStar = 0, $iType = 0)
	{
        $sJoin = $sWhere = '';
		$aSWhere = array();
		$aWhere = array('profile' => (int)$iProfileId, 'parts' => '(^|,)' . (int)$iProfileId . '(,|$)');
		if ($sParam)
		{
		    $sParamWhere = "`l`.`{$this->CNF['FIELD_TITLE']}` LIKE :title";
			$aWhere['title'] = "%{$sParam}%";
			$aProfiles = BxDolService::call('system', 'profiles_search', array($sParam), 'TemplServiceProfiles');
			if (!empty($aProfiles))
            {
                $aRegexp = array();
                foreach($aProfiles as &$aProfile)
                    $aRegexp[] = "(^|,){$aProfile['value']}(,|$)";

                if (!empty($aRegexp)) {
                    $aWhere['part_search'] = implode('|', $aRegexp);
                    $sParamWhere .= " OR `{$this->CNF['FIELD_PARTICIPANTS']}` REGEXP :part_search";
                }
            }

            $aSelectedLots = $this->searchMessage($sParam, $iProfileId, $iLotId);
            if (!empty($aSelectedLots))
                $sParamWhere .= " OR `l`.`{$this->CNF['FIELD_ID']}` IN (" . implode(',', $aSelectedLots) .")";

            $aSWhere[] = "({$sParamWhere})";
		}

		if ($iType)
		{
			$aSWhere[] = " `l`.`{$this->CNF['FIELD_TYPE']}` = :type ";
			$aWhere['type'] = $iType;
		}
		
		if ($iLotId)
		{
			$aSWhere[] = " `l`.`{$this->CNF['FIELD_ID']}` = :id ";
			$aWhere['id'] = $iLotId;	
		}
		
		if ($iStar)
		{
			$sJoin .= "INNER JOIN `{$this->CNF['TABLE_USERS_INFO']}` as `u` ON `u`.`{$this->CNF['FIELD_INFO_LOT_ID']}` = `l`.`{$this->CNF['FIELD_ID']}` AND `u`.`{$this->CNF['FIELD_INFO_USER_ID']}`=:profile";
			$aSWhere[] = "`u`.`{$this->CNF['FIELD_INFO_STAR']}` = 1";
		}
		
		if (!empty($aSWhere))
				$sWhere = ' AND ' . implode(' AND ', $aSWhere);

		/*$sLimit = "";
		if (!$iLotId && !$sParam && !$iStar && !$iType)
            $sLimit = "LIMIT 200";*/

		return $this-> getAll("SELECT *, 
                                         IF (`l`.`{$this->CNF['FIELD_UPDATED']}` = 0, `l`.`{$this->CNF['FIELD_ADDED']}`, `l`.`{$this->CNF['FIELD_UPDATED']}`) as `order` 
			                             FROM `{$this->CNF['TABLE_ENTRIES']}` as `l`
                                         {$sJoin}
                                         WHERE (`l`.`{$this->CNF['FIELD_PARTICIPANTS']}` REGEXP :parts OR `l`.`{$this->CNF['FIELD_AUTHOR']}`=:profile) {$sWhere}
                                         ORDER BY `order` DESC", $aWhere);
	}
	
	/**
	* Check if the title of the lot type must contain link
	*@param int $iType
	*@return boolean
	*/
	public function isLinkedTitle($iType){
		$sQuery = $this -> prepare("SELECT `{$this->CNF['FIELD_TYPE_LINKED']}` FROM `{$this->CNF['TABLE_TYPES']}` WHERE `{$this->CNF['FIELD_TYPE_ID']}` = ? LIMIT 1", $iType);
		return (int)$this -> getOne($sQuery) == 1;	
	}

	/**
	* Get time when member was online  
	*@param int  $iProfileId
	*@return boolean
	*/
	public function lastOnline($iProfileId)
	{
	   $sSql = $this -> prepare("SELECT 
				IF (`ts`.`date` != '', `ts`.`date`, `ta`.`logged`) as `logged` 
			FROM `sys_profiles` AS `tp` 
			INNER JOIN `sys_accounts` AS `ta` ON `tp`.`account_id`=`ta`.`id` 
			LEFT JOIN `sys_sessions` AS `ts` ON `tp`.`account_id`=`ts`.`user_id` 
			WHERE 
				`tp`.`id` = ? AND 
				`ta`.`profile_id`=`tp`.`id`
			LIMIT 1", $iProfileId);
		
		return $this -> getOne($sSql);
	}

	/**
	* Delete all profiles info from lots and jots
	*@param int  $iProfileId
	*@return int affected rows
	*/
	public function deleteProfileInfo($iProfileId){
		$bResult = true;
		
		$aWhere['profile'] = (int)$iProfileId;
			
		$bResult &= $this-> query("DELETE
			FROM `{$this->CNF['TABLE_ENTRIES']}`
			WHERE `{$this->CNF['FIELD_AUTHOR']}`=:profile", $aWhere);

		$aJots = $this-> getAll("SELECT *
			FROM `{$this->CNF['TABLE_MESSAGES']}` 
			WHERE `{$this->CNF['FIELD_MESSAGE_AUTHOR']}`=:profile", $aWhere);
		
		foreach($aJots as $iKey => $aJot)
           $this->clearJotsConnections($aJot[$this->CNF['FIELD_MESSAGE_ID']]);
			
		$bResult &= $this-> query("DELETE
			FROM `{$this->CNF['TABLE_MESSAGES']}` 
			WHERE `{$this->CNF['FIELD_MESSAGE_AUTHOR']}`=:profile", $aWhere);

		$aJots = $this-> getAll("SELECT * 
			FROM `{$this->CNF['TABLE_ENTRIES']}` 
			WHERE FIND_IN_SET(:profile, `{$this->CNF['FIELD_PARTICIPANTS']}`)", $aWhere);

		if (empty($aJots)) 
				return $bResult;
		
		foreach($aJots as $iKey => $aJot){
			$bResult &= $this -> removeParticipant($aJot[$this->CNF['FIELD_ID']], $iProfileId);
		}
		
		return $bResult;	
	}

	/**
	* Add attachment to the jot
	*@param int $iJotId jot id
	*@param text mixedContent attachment content
	*@param int sType attachment type
	*@return int affected rows
	*/
	public function addAttachment($iJotId, $mixedContent, $sType = BX_ATT_TYPE_REPOST){
		$iJotId = (int)$iJotId;
		if (!$iJotId || !($aJotInfo = $this -> getJotById($iJotId)) || !$mixedContent)
			return false;
		
		if ($aJotInfo[$this->CNF['FIELD_MESSAGE_AT_TYPE']] && $aJotInfo[$this->CNF['FIELD_MESSAGE_AT_TYPE']] != BX_ATT_TYPE_REPOST) /* don't update attachment if it is already exists and it is not a repost */
			return false;
			
		$sQuery = $this->prepare("UPDATE `{$this->CNF['TABLE_MESSAGES']}` 
												SET  `{$this->CNF['FIELD_MESSAGE_AT_TYPE']}` = ?, 
													 `{$this->CNF['FIELD_MESSAGE_AT']}` = ?
												WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = ?", $sType, $mixedContent, $iJotId);
		
		return $this -> query($sQuery);
	}
	
	/**
	* Add attachment to the jot
	*@param int  $iJotId jot id
	*@param int  $iProfileId profile id
	*@param string $sMessage
	*@return int affected rows
	*/
	public function editJot($iJotId, $iProfileId, $sMessage){
		$iJotId = (int)$iJotId;
		$aJotInfo = array();
			
		if (!$iJotId || !($aJotInfo = $this -> getJotById($iJotId)) || !$sMessage)
			return false;
		
		$sWhere = '';
		if ($aJotInfo[$this->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_REPOST)
			$sWhere = ",`{$this->CNF['FIELD_MESSAGE_AT_TYPE']}` = '', `{$this->CNF['FIELD_MESSAGE_AT']}` = ''";

        $sMessage = clear_xss($sMessage);

		$sQuery = $this->prepare("UPDATE `{$this->CNF['TABLE_MESSAGES']}` 
												SET  `{$this->CNF['FIELD_MESSAGE']}` = ?,
													 `{$this->CNF['FIELD_MESSAGE_LAST_EDIT']}` = UNIX_TIMESTAMP(),
													 `{$this->CNF['FIELD_MESSAGE_EDIT_BY']}` = ?
													 {$sWhere}
												WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = ?", $sMessage, $iProfileId, $iJotId);
		
		return $this -> query($sQuery);
	}
	
	/**
	* Check if the Jot already has attachment
	*@param int  $iJotId jot id
	*@param string $sType attachment type
	*@return int original attachment Id
	*/
	public function hasAttachment($iJotId, $sType='repost'){
		$iResult = $this->getOne("SELECT
									`{$this->CNF['FIELD_MESSAGE_AT']}` 
									FROM `{$this->CNF['TABLE_MESSAGES']}` 
									WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id AND `{$this->CNF['FIELD_MESSAGE_AT_TYPE']}`= :type", array('id' => $iJotId, 'type' => $sType));
		
		return $iResult ? $iResult : $iJotId;
	}
	
	public function updateFiles($iJotId, $mixedValues){
	    $sQuery = $this->prepare("UPDATE `{$this->CNF['OBJECT_STORAGE']}` SET " . $this->arrayToSQL($mixedValues) . " WHERE `{$this->CNF['FIELD_ST_ID']}` = ?", $iJotId);
		return $this -> query($sQuery);
	}
	
	public function getJotFiles($iJot, $bCount = false){
		return !$bCount ? $this->getAll("SELECT * FROM `{$this->CNF['OBJECT_STORAGE']}` WHERE `{$this->CNF['FIELD_ST_JOT']}` = :id", array('id' => $iJot)) : $this->getOne("SELECT COUNT(*) FROM `{$this->CNF['OBJECT_STORAGE']}` WHERE `{$this->CNF['FIELD_ST_JOT']}` = :id", array('id' => $iJot));
	}
	
	private function removeFilesByJotId($iJotId){
		 $aFiles = $this -> getJotFiles($iJotId);
		 if (empty($aFiles))
			 return false;
		 
		 $oStorage = BxDolStorage::getObjectInstance($this->CNF['OBJECT_STORAGE']);
		 $bResult = true;
		 foreach($aFiles as $iKey => $aFile)
			$bResult &= $oStorage -> deleteFile($aFile[$this->CNF['FIELD_ST_ID']], $aFile[$this->CNF['FIELD_ST_AUTHOR']]);
		  
		  return $bResult;
	}
	
	public function isFileVendor($iFileId, $iProfileId){
		$oStorage = BxDolStorage::getObjectInstance($this->CNF['OBJECT_STORAGE']);
		$aFile = $oStorage -> getFile($iFileId);
		return $aFile[$this->CNF['FIELD_ST_AUTHOR']] == $iProfileId;
	}
	
	public function isEmptyJotMessage($iJotId){
		$aJotInfo = $this -> getJotById($iJotId);
		
		return empty($aJotInfo) || !$aJotInfo['FIELD_MESSAGE'];
	}
	
	public function getFirstUnreadJot($iProfileId, $iLotId){
		if (!$iLotId)
		    return 0;

		$aUnreadJots = $this->getNewJots($iProfileId, $iLotId);
		return !empty($aUnreadJots) ? (int)$aUnreadJots[$this->CNF['FIELD_NEW_JOT']] : 0;
	}

    /**
     * @param int $iObjectId item's object ID
     * @param string $sType module's name from sys_modules
     * @param int $iProfileId comments' author
     * @param int $iStart get comments from position
     * @param int $iPerPage number of the comments to get at once
     * @return array list of the comments
     *
     */
    public function getLiveComments($iObjectId, $sType, $iProfileId, $iStart = 0, $iPerPage = 0){
        $aWhere = array('object' => $iObjectId, 'type' => $sType);
        $sLimit = $sAuthor = '';
        if ((int)$iProfileId) {
            $aWhere['author'] = (int)$iProfileId;
            $sAuthor = "AND `{$this->CNF['FIELD_LCMTS_AUTHOR']}`=:author";
        }

        if ($iPerPage) {
            $aWhere['start'] = (int)$iStart;
            $aWhere['per_page'] = (int)$iPerPage;
            $sLimit = "LIMIT :start, :per_page";
        }

        $aComments = $this -> getAll("SELECT 
                                                  SQL_CALC_FOUND_ROWS
                                                  `{$this->CNF['FIELD_LCMTS_ID']}`,
                                                  `{$this->CNF['FIELD_LCMTS_TEXT']}`,
                                                  `{$this->CNF['FIELD_LCMTS_AUTHOR']}`,
                                                  `{$this->CNF['FIELD_LCMTS_DATE']}`,
                                                  `{$this->CNF['FIELD_LCMTS_OBJECT_ID']}`
                                                 FROM `{$this->CNF['TABLE_LIVE_COMMENTS']}` as `c`  
                                                 LEFT JOIN `{$this->CNF['TABLE_CMTS_OBJECTS']}` as `s` ON `s`.`{$this->CNF['FIELD_COBJ_ID']}` = `c`.`{$this->CNF['FIELD_LCMTS_SYS_ID']}`                                                   
                                                 WHERE `s`.`{$this->CNF['FIELD_COBJ_MODULE']}` = :type AND `{$this->CNF['FIELD_LCMTS_OBJECT_ID']}` = :object {$sAuthor}
                                                 ORDER BY `{$this->CNF['FIELD_LCMTS_ID']}` DESC
                                                 {$sLimit}",
            $aWhere);
        return array('result' => $aComments, 'total' => (int)$this->getOne("SELECT FOUND_ROWS()"));
    }

    /**
     * @param string $sText message body
     * @param int $iObjectId item's object ID
     * @param string $sName module's name from sys_modules
     * @param int $iProfileId comments' author
     * @param int $iDate comments' time
     * @return mixed last added comments id or false
     *
     */

    public function addLiveComment($sText, $iObjectId, $sName, $iProfileId, $iDate = 0){
        $iSystem = $this -> getOne("SELECT `{$this->CNF['FIELD_COBJ_ID']}` 
                                            FROM `{$this->CNF['TABLE_CMTS_OBJECTS']}`
                                            WHERE `{$this->CNF['FIELD_COBJ_MODULE']}`=:name LIMIT 1", array('name' => $sName));
        if (!$iSystem)
            return false;

        $sQuery = $this -> prepare("REPLACE INTO `{$this->CNF['TABLE_LIVE_COMMENTS']}`
                                   SET 
                                         `{$this->CNF['FIELD_LCMTS_TEXT']}`=?,
                                         `{$this->CNF['FIELD_LCMTS_AUTHOR']}`=?,
                                         `{$this->CNF['FIELD_LCMTS_DATE']}`=?,                                         
                                         `{$this->CNF['FIELD_LCMTS_OBJECT_ID']}`=?,
                                         `{$this->CNF['FIELD_LCMTS_SYS_ID']}`=?
                                   ", $sText, $iProfileId, $iDate ? $iDate : time(), $iObjectId, $iSystem);

        return $this -> query($sQuery) ? $this -> lastId() : false;
    }

    public function removeLiveComment($iObjectId, $iProfileId){
        return $this -> query("DELETE FROM `{$this->CNF['TABLE_LIVE_COMMENTS']}` 
                              WHERE `{$this->CNF['FIELD_LCMTS_AUTHOR']}`=:author AND `{$this->CNF['FIELD_LCMTS_ID']}`=:object_id", array('author' => $iProfileId, 'object_id' => $iObjectId));
    }

    public function getLotFiles($iLotId, $iStart = 0, $iPerPage = 0){
        $aWhere = array('id' => $iLotId);

        $sLimit = '';
        if ($iPerPage) {
            $aWhere['start'] = (int)$iStart;
            $aWhere['per_page'] = (int)$iPerPage;
            $sLimit = "LIMIT :start, :per_page";
        }

        return $this->getAll("SELECT `s`.* 
                                         FROM `{$this->CNF['OBJECT_STORAGE']}` as `s`
                                         LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `s`.`{$this->CNF['FIELD_ST_JOT']}` = `j`.`{$this->CNF['FIELD_MESSAGE_ID']}`
                                         LEFT JOIN `{$this->CNF['TABLE_ENTRIES']}` as `l` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}` 
                                         WHERE `l`.`{$this->CNF['FIELD_ID']}` = :id 
                                         ORDER BY `s`.`{$this->CNF['FIELD_ST_ADDED']}` DESC
                                         {$sLimit}
                                         ", $aWhere);
    }

    public function getLotFilesCount($iLot){
        return $this->getOne("SELECT COUNT(*)
                                         FROM `{$this->CNF['OBJECT_STORAGE']}` as `s`
                                         LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `s`.`{$this->CNF['FIELD_ST_JOT']}` = `j`.`{$this->CNF['FIELD_MESSAGE_ID']}`
                                         LEFT JOIN `{$this->CNF['TABLE_ENTRIES']}` as `l` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}` 
                                         WHERE `l`.`{$this->CNF['FIELD_ID']}` = :id 
                                         ORDER BY `s`.`{$this->CNF['FIELD_ST_ADDED']}` DESC
                                         ", array( 'id' => $iLot ));
    }

    public function getSentNtfsNumber($iProfileId, $iLotId){
        $iInterval = (int)$this->CNF['PARAM_NTFS_INTERVAL'];
        return $this -> getOne("SELECT COUNT(*)
                                        FROM `bx_notifications_events`                                        
                                        WHERE `action`='got_jot_ntfs' AND `type`=:type AND `owner_id` = :profile AND `object_id`=:lot_id 
                                              AND `date` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :interval HOUR))",
                                array(
                                        'type' => $this->_oConfig->getName(),
                                        'profile' => $iProfileId,
                                        'lot_id' => $iLotId,
                                        'interval' => $iInterval
                                    ));
    }

    /**
     * @param int $iJotId id of the message
     * @param int $iProfileId viewer id
     * @param int $iJotAuthor message's author
     * @param int $iLotAuthorId author id
     * @return bool
     */
    public function isAllowedToDeleteJot($iJotId, $iProfileId=0, $iJotAuthor=0, $iLotAuthorId=0){
        if (!$iJotId)
            return true;

        $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_MESSAGES, $iProfileId);
        if ($mixedResult === true)
            return true;

        if (!$iProfileId)
            $iProfileId = bx_get_logged_profile_id();

        if (!$iJotAuthor){
            $aJot = $this->getJotById($iJotId);
            $iJotAuthor = $aJot[$this->CNF['FIELD_MESSAGE_AUTHOR']];
        }

        if (!$iLotAuthorId) {
            $iLotId = $this->getLotByJotId($iJotId);
            $bIsLotAuthor = $this -> isAuthor($iLotId, $iProfileId);
        }
        else
            $bIsLotAuthor = $iLotAuthorId == $iProfileId;

        return ($this->CNF['ALLOW_TO_REMOVE_MESSAGE'] && $iJotAuthor == $iProfileId) || ($this->CNF['ALLOW_TO_MODERATE_MESSAGE_FOR_AUTHORS'] && $bIsLotAuthor);
    }

    function createJVC($iLotId, $iProfileId){
        $aJVC = $this->getJVC($iLotId);
        $aOpened = array();

        if (empty($aJVC)) {
            $aLotInfo = $this->getLotInfoById($iLotId);
            $sRoom = $this->_oConfig->getRoomId($aLotInfo[$this->CNF['FIELD_ID']], $aLotInfo[$this->CNF['FIELD_AUTHOR']]);
            $this->query("INSERT INTO `{$this->CNF['TABLE_JVC']}` 
                SET 
                    `{$this->CNF['FJVC_ROOM']}`=:room,
                    `{$this->CNF['FJVC_LOT_ID']}`=:lot_id,
                    `{$this->CNF['FJVC_NUMBER']}`=1
                ",
                array(
                    'room' => $sRoom,
                    'lot_id' => $iLotId
                ));

          $iJVCId = $this->lastId();
        }
        else
        {
            $iJVCId = $aJVC[$this->CNF['FJVC_ID']];
            $aOpened = $this->closeAllOpenedConversations($iJVCId);
            $sRoom = $aJVC[$this->CNF['FJVC_ROOM']];
            $this->query("UPDATE `{$this->CNF['TABLE_JVC']}` 
                        SET `{$this->CNF['FJVC_NUMBER']}`= 1
                        WHERE `{$this->CNF['FJVC_LOT_ID']}`= :lot_id    
                        ", array('lot_id' => $iLotId));
        }

        if ($iJVCId && ($iJVCItemId = $this->addJVCItem($iJVCId, $iProfileId))) {
            $this->updateJVC($iLotId, $this->CNF['FJVC_ACTIVE'], $iJVCItemId);
            $iJotId = $this->addJot($iLotId, '', $iProfileId);
            $this->updateJot($iJotId, $this->CNF['FIELD_MESSAGE_VIDEOC'], $iJVCItemId);
        }

        return array('jitsi_id' => $iJVCId, 'jot_id' => $iJotId, $this->CNF['FJVC_ROOM'] => $sRoom, 'opened' => array_values($aOpened));
    }

    public function getJotIdByJitsiItem($iJitsiId){
        $sQuery = $this -> prepare("SELECT `{$this->CNF['FIELD_MESSAGE_ID']}` 
                                            FROM `{$this->CNF['TABLE_MESSAGES']}` 
                                            WHERE `{$this->CNF['FIELD_MESSAGE_VIDEOC']}` = ? 
                                            ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}` DESC
                                            LIMIT 1", $iJitsiId);
        return $this -> getOne($sQuery);
    }

    function addJVCItem($iId, $iProfileId){
        $this->query("INSERT INTO `{$this->CNF['TABLE_JVCT']}` SET 
                            `{$this->CNF['FJVCT_AUTHOR_ID']}` = :author,
                            `{$this->CNF['FJVCT_FK']}`= :id,
                            `{$this->CNF['FJVCT_PART']}`= :part,
                            `{$this->CNF['FJVCT_JOINED']}`= :part,
                            `{$this->CNF['FJVCT_START']}` = UNIX_TIMESTAMP()
                            ",
                    array(
                        'author' => $iProfileId,
                        'id' => $iId,
                        'part' => $iProfileId
                    ));

        return $this->lastId();
    }

    function closeAllOpenedConversations($iJVCId){
        $aAllOpenedChats = $this->getPairs("SELECT  `j`.`{$this->CNF['FIELD_MESSAGE_ID']}`, `v`.`{$this->CNF['FJVCT_ID']}` as `jot_id` 
                                                            FROM `{$this->CNF['TABLE_JVCT']}` as `v` 
                                                            LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `v`.`{$this->CNF['FJVCT_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_VIDEOC']}`                                                                                                  
                                                            WHERE `{$this->CNF['FJVCT_FK']}`= :id AND `{$this->CNF['FJVCT_END']}`= 0", 'jot_id', $this->CNF['FIELD_MESSAGE_ID'], array('id' => $iJVCId));

        if (empty($aAllOpenedChats))
            return array();

        $this->query("UPDATE `{$this->CNF['TABLE_JVCT']}`
                                  SET
                                        `{$this->CNF['FJVCT_END']}`= UNIX_TIMESTAMP()                                        
                                  WHERE `{$this->CNF['FJVCT_FK']}`= :id AND `{$this->CNF['FJVCT_END']}`= 0",
                    array('id' => $iJVCId));

        return $aAllOpenedChats;
    }

    function joinToActiveJVC($iLotId, $iProfileId, $bJoin = true){
        $aItem = $this->getActiveJVCItem($iLotId);
        if (empty($aItem))
            return false;

        $aParticipants = array();
        if ($aItem[$this->CNF['FJVCT_PART']])
            $aParticipants = explode(',', $aItem[$this->CNF['FJVCT_PART']]);

        if (!in_array($iProfileId, $aParticipants))
             $aParticipants[] = $iProfileId;

        $aJoined = $aItem[$this->CNF['FJVCT_JOINED']] ? explode(',', $aItem[$this->CNF['FJVCT_JOINED']]) : array();
        if (!in_array($iProfileId, $aJoined))
            $aJoined[] = $iProfileId;

        $this->updateJVCItem($aItem[$this->CNF['FJVCT_ID']], $this->CNF['FJVCT_PART'], implode(',', $aParticipants));
        if ($bJoin)
            $this->updateJVCItem($aItem[$this->CNF['FJVCT_ID']], $this->CNF['FJVCT_JOINED'], implode(',', $aJoined));

        $this->updateJVC($iLotId, $this->CNF['FJVC_NUMBER'], count($aParticipants));
        return true;
    }

    function updateJVC($iLotId, $sField, $mixedValue){
       return $this->query("UPDATE `{$this->CNF['TABLE_JVC']}` SET 
                            `{$sField}`=:value                                                       
                            WHERE `{$this->CNF['FJVC_LOT_ID']}`=:lot_id",
            array(
                'lot_id' => $iLotId,
                'value' => $mixedValue
            ));
    }

    function leaveJVC($iLotId, $sField, $mixedValue){
        return $this->query("UPDATE `{$this->CNF['TABLE_JVC']}` SET 
                            `{$sField}`=:value                                                       
                            WHERE `{$this->CNF['FJVC_LOT_ID']}`=:lot_id",
            array(
                'lot_id' => $iLotId,
                'value' => $mixedValue
            ));
    }

    function updateJVCItem($iId, $sField, $mixedValue){
        return $this->query("UPDATE `{$this->CNF['TABLE_JVCT']}` SET 
                            `{$sField}`=:value                                                       
                            WHERE `{$this->CNF['FJVC_ID']}`=:id",
            array(
                'id' => $iId,
                'value' => $mixedValue
            ));
    }

    function closeJVC($iLotId)
    {
        $aJVC = $this->getJVC($iLotId);
        if (empty($aJVC) || !(int)$aJVC[$this->CNF['FJVC_ACTIVE']])
            return false;

        $this->query("UPDATE `{$this->CNF['TABLE_JVCT']}` 
                                        SET                                                    
                                        `{$this->CNF['FJVCT_JOINED']}`=:joined,
                                        `{$this->CNF['FJVCT_END']}`= UNIX_TIMESTAMP()                                              
                                        WHERE `{$this->CNF['FJVCT_ID']}`= :id",
        array(
              'id' => $aJVC[$this->CNF['FJVC_ACTIVE']],
              'joined' => '',
        ));

        $this->updateJVC($iLotId, $this->CNF['FJVC_ACTIVE'], 0);
        return $this->updateJVC($iLotId, $this->CNF['FJVC_NUMBER'], 0);
    }

    function stopJVC($iLotId, $iProfileId){
        $aJVC = $this->getJVC($iLotId);
        if (empty($aJVC) || !(int)$aJVC[$this->CNF['FJVC_ACTIVE']])
            return false;

        $aTrack = $this->getRow("SELECT * FROM `{$this->CNF['TABLE_JVCT']}`                                                                                           
                                        WHERE `{$this->CNF['FJVCT_ID']}`= :id",
            array(
                'id' => $aJVC[$this->CNF['FJVC_ACTIVE']]
            ));

        $aJoined = explode(',', $aTrack[$this->CNF['FJVCT_JOINED']]);
        if (in_array($iProfileId, $aJoined))
            unset($aJoined[array_search($iProfileId, $aJoined)]);
        else
            $this->joinToActiveJVC($iLotId, $iProfileId, false);

        $sEnd = '';
        $iJoinedNumber = count($aJoined);
        if (!$iJoinedNumber)
            $sEnd = ",`{$this->CNF['FJVCT_END']}`= UNIX_TIMESTAMP()";

        $this->query("UPDATE `{$this->CNF['TABLE_JVCT']}` 
                                        SET                                                    
                                        `{$this->CNF['FJVCT_JOINED']}`=:joined
                                        {$sEnd}                                                
                                        WHERE `{$this->CNF['FJVCT_ID']}`= :id",
            array(
                'id' => $aJVC[$this->CNF['FJVC_ACTIVE']],
                'joined' => implode(',', $aJoined),
            ));

        if (!$iJoinedNumber)
            $this->updateJVC($iLotId, $this->CNF['FJVC_ACTIVE'], 0);

        return $this->updateJVC($iLotId, $this->CNF['FJVC_NUMBER'], $iJoinedNumber);
    }

    function getJVC($iLotId){
        return $this->getRow("SELECT * FROM `{$this->CNF['TABLE_JVC']}`
                                     WHERE `{$this->CNF['FJVC_LOT_ID']}`= :lot_id",
        array('lot_id' => $iLotId));
    }

    function getJVCItem($iId){
        return $this->getRow("SELECT * 
                                    FROM `{$this->CNF['TABLE_JVCT']}` 
                                    WHERE `{$this->CNF['FJVCT_ID']}`= :id",
                                    array('id' => $iId));
    }

    function getActiveJVCItem($iLotId, $sFiled = ''){
       $aJVC = $this->getJVC($iLotId);
       if (empty($aJVC) || !(int)$aJVC[$this->CNF['FJVC_ACTIVE']])
           return false;

       $aInfo = $this->getJVCItem($aJVC[$this->CNF['FJVC_ACTIVE']]);
       if ($sFiled)
           return isset($aInfo[$sFiled]) ? $aInfo[$sFiled] : false;

       return $aInfo;
    }

    /*********************** REACT JOT Integration part *****************/

    /**
     * Returns installed list of modules with comments ability
     * @param string $sModule
     * @return array
     */
    function getAllCmtsModule($sModule = ''){
        return $this -> getAllWithKey("SELECT 
                    `c`.`Module` as `module`,
                    `TriggerTable` as `table`,
                    `TriggerFieldId` as `id`,
                    `TriggerFieldAuthor` as `owner`,
                    `TriggerFieldTitle` as `title`,
                    `TriggerFieldComments` as `cmts`,
                    `sm`.*,
                    `m`.`icon`
                FROM `sys_objects_cmts` as `c`
                LEFT JOIN `sys_modules` as `sm` ON `sm`.`name` = `c`.`module`
                LEFT JOIN `sys_menu_items` as `m` ON `c`.`module` = `m`.`module` AND `m`.`set_name` = 'sys_site'
                WHERE `c`.`module` != 'bx_timeline' 
                GROUP BY `c`.`module`
                ", 'module');
    }

    function addJotReaction($iJotId, $iProfileId, $aEmoji){
        $CNF = &$this->_oConfig->CNF;
        return $this->query("REPLACE INTO `{$CNF['TABLE_JOR_REACTIONS']}` 
                                        SET 
                                            `{$CNF['FIELD_REACT_JOT_ID']}` = :jot_id,
                                            `{$CNF['FIELD_REACT_NATIVE']}` = :native,
                                            `{$CNF['FIELD_REACT_EMOJI_ID']}` = :emoji_id,
                                            `{$CNF['FIELD_REACT_PROFILE_ID']}` = :profile_id,
                                            `{$CNF['FIELD_REACT_ADDED']}` = UNIX_TIMESTAMP()
                                        ", array(
            'jot_id' => $iJotId,
            'native' => $aEmoji['native'],
            'emoji_id' => $aEmoji['id'],
            'profile_id' => $iProfileId
        ));
    }

    function deleteReaction($iJotId, $iProfileId, $sEmoji){
        $CNF = &$this->_oConfig->CNF;
        return $this->query("DELETE FROM `{$CNF['TABLE_JOR_REACTIONS']}` 
                                        WHERE 
                                            `{$CNF['FIELD_REACT_JOT_ID']}` = :jot_id AND 
                                            `{$CNF['FIELD_REACT_PROFILE_ID']}` = :profile_id AND 
                                            `{$CNF['FIELD_REACT_EMOJI_ID']}` = :emoji_id
                                        ", array(
            'jot_id' => $iJotId,
            'profile_id' => $iProfileId,
            'emoji_id' => $sEmoji
        ));
    }

    function deleteJotReactions($iJotId){
        $CNF = &$this->_oConfig->CNF;
        return $this->query("DELETE FROM `{$CNF['TABLE_JOR_REACTIONS']}` 
                                        WHERE 
                                            `{$CNF['FIELD_REACT_JOT_ID']}` = :jot_id
                                        ", array(
            'jot_id' => $iJotId
        ));
    }

    function updateReaction($iJotId, $iProfileId, $sEmojiId, $sAction = BX_JOT_REACTION_ADD){
        $CNF = &$this->_oConfig->CNF;
        if ($sAction === BX_JOT_REACTION_ADD) {
           $sNative = $this->getOne("SELECT `{$CNF['FIELD_REACT_NATIVE']}` 
                                                FROM `{$CNF['TABLE_JOR_REACTIONS']}` 
                                                WHERE `{$CNF['FIELD_REACT_JOT_ID']}` = :jot_id LIMIT 1", array( 'jot_id' => $iJotId ));
            return $this-> addJotReaction($iJotId, $iProfileId, array('native' => $sNative, 'id' => $sEmojiId));
        }

        return $this->deleteReaction($iJotId, $iProfileId, $sEmojiId);
    }

    function getJotReactions($iJotId){
        $CNF = &$this->_oConfig->CNF;
        return $this->getAll("SELECT * FROM `{$CNF['TABLE_JOR_REACTIONS']}` 
                                        WHERE 
                                            `{$CNF['FIELD_REACT_JOT_ID']}` = :jot_id
                                        ORDER BY `{$CNF['FIELD_REACT_ADDED']}`", array( 'jot_id' => $iJotId ));
    }

    public function findInHistory($iLotId = 0, $sText = '', $iStart = 0, $iLimit = 10, $sOrder = 'DESC')
    {
        if (!$sText)
            return array();

        $aWhere[] = "`j`.`{$this->CNF['FIELD_MESSAGE']}` LIKE :message";
        $aParams['message'] = "%{$sText}%";

        if ($iLotId)
        {
            $aWhere[] = " `l`.`{$this->CNF['FIELD_ID']}`=:id ";
            $aParams['id'] = $iLotId;
        }

        $sLimit = '';
        $sOrder = $sOrder === 'ASC' ? 'ASC' : 'DESC';
        if ($iLimit) {
            $aParams['start'] = (int)$iStart;
            $aParams['limit'] = (int)$iLimit;
            $sLimit = "LIMIT :start, :limit";
        }

        if (!empty($aWhere))
            $sWhere = implode(' AND ', $aWhere);

        return $this-> getAll("SELECT 
			`l`.`{$this->CNF['FIELD_TITLE']}`,
			`l`.`{$this->CNF['FIELD_TYPE']}`,
			`l`.`{$this->CNF['FIELD_ADDED']}` as `talk_added`,
			`l`.`{$this->CNF['FIELD_AUTHOR']}` as `talk_author`,
			`l`.`{$this->CNF['FIELD_PARTICIPANTS']}`,
			`j`.`{$this->CNF['FIELD_MESSAGE']}`,
			`j`.`{$this->CNF['FIELD_MESSAGE_ID']}` as `message_id`,	
			`j`.`{$this->CNF['FIELD_ADDED']}` as `message_added`,			
			`j`.`{$this->CNF['FIELD_MESSAGE_AUTHOR']}` as `message_author`			
			FROM `{$this->CNF['TABLE_ENTRIES']}` as `l`
			LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}`
			WHERE {$sWhere}
			ORDER BY `j`.`{$this->CNF['FIELD_MESSAGE_ID']}` {$sOrder}
			{$sLimit}", $aParams);
    }

    public function markNotificationAsRead($iRecipientId, $iLotId)
    {
        if (!$this->isModuleByName('bx_notifications'))
            return false;

        $aEvents = $this -> getAll("SELECT `e`.`id` 
                                                FROM `bx_notifications_events` AS `e`
                                                LEFT JOIN `bx_notifications_queue` AS `q` ON `q`.`event_id` = `e`.`id`                                       
                                                WHERE `action`='got_jot_ntfs' 
                                                    AND `e`.`type`=:type                                               
                                                    AND `e`.`object_owner_id`=:object_owner_id
                                                    AND `e`.`object_id`=:lot_id",
            array(
                'type' => $this->_oConfig->getName(),
                'lot_id' => $iLotId,
                'object_owner_id' => $iRecipientId
            ));

        if (empty($aEvents))
            return false;

        foreach($aEvents as &$aEvent) {
            if ($this->query("DELETE FROM `bx_notifications_events` WHERE `id`=:id",
                array(
                    'id' => $aEvent['id']
                )))
               $this->query("DELETE FROM `bx_notifications_queue` WHERE `profile_id`=:profile_id AND `event_id`=:event_id", array(
                    'profile_id' => $iRecipientId,
                    'event_id' => $aEvent['id']
               ));
        }
    }

    function getPublicRoomParticipants($sRoom){
        $aRoom = $this->getPublicVideoRoom($sRoom);
        if (empty($aRoom) || !$aRoom[$this->CNF['FPJVC_PARTS']])
            return array();

        return explode(',', $aRoom[$this->CNF['FPJVC_PARTS']]) ;
    }

    function createPublicVideoRoom($sRoom, $iProfileId){
        $CNF = &$this->_oConfig->CNF;
        $aRoom = $this->getPublicVideoRoom($sRoom);

        if (!empty($aRoom)) {
            if (!(int)$aRoom[$CNF['FPJVC_STATUS']])
                return $this->query("UPDATE `{$this->CNF['TABLE_PUBLIC_JVC']}` 
                                    SET                                         
                                        `{$this->CNF['FPJVC_CREATED']}`=UNIX_TIMESTAMP(),
                                        `{$this->CNF['FPJVC_STATUS']}`=1,
                                        `{$this->CNF['FPJVC_PARTS']}`=:part
                                    WHERE `{$this->CNF['FPJVC_ROOM']}`=:room                                         
                                   ", array('room' => $sRoom, 'part' => $iProfileId));
            else
                return $this->updatePublicVideoRoom($sRoom, $iProfileId, BX_JOT_PUBLIC_JITSI_JOIN);

        }

        return $this->query("REPLACE INTO `{$this->CNF['TABLE_PUBLIC_JVC']}` 
                                    SET 
                                        `{$this->CNF['FPJVC_ROOM']}`=:room,
                                        `{$this->CNF['FPJVC_CREATED']}`=UNIX_TIMESTAMP(),
                                        `{$this->CNF['FPJVC_PARTS']}`=:part                                     
                                   ", array('room' => $sRoom, 'part' => $iProfileId));
    }

    function getPublicVideoRoom($sRoom){
        if (!$sRoom)
            return false;

        return $this->getRow("SELECT * FROM `{$this->CNF['TABLE_PUBLIC_JVC']}` 
                                        WHERE `{$this->CNF['FPJVC_ROOM']}`=:room LIMIT 1", array('room' => $sRoom));
    }

    function updatePublicVideoRoom($sRoom, $iProfileId, $sAction){
        $aRoom = $this->getPublicVideoRoom($sRoom);
        if (empty($aRoom))
            return false;

        if ($sAction == BX_JOT_PUBLIC_JITSI_CLOSE)
            return $this->query("UPDATE `{$this->CNF['TABLE_PUBLIC_JVC']}` 
                                    SET
                                        `{$this->CNF['FPJVC_STATUS']}`=0
                                    WHERE `{$this->CNF['FPJVC_ROOM']}`=:room                                         
                                   ", array('room' => $sRoom));

        $aParticipants = array();
        if ($aRoom[$this->CNF['FPJVC_PARTS']])
            $aParticipants = explode(',', $aRoom[$this->CNF['FPJVC_PARTS']]);

        if ($sAction == BX_JOT_PUBLIC_JITSI_JOIN && !in_array($iProfileId, $aParticipants))
            $aParticipants[] = $iProfileId;

        if ($sAction == BX_JOT_PUBLIC_JITSI_LEAVE)
            $aParticipants = array_diff( $aParticipants, array($iProfileId) );

        return $this->query("UPDATE `{$this->CNF['TABLE_PUBLIC_JVC']}` 
                                    SET
                                        `{$this->CNF['FPJVC_PARTS']}`=:parts,
                                        `{$this->CNF['FPJVC_STATUS']}`=:status
                                    WHERE `{$this->CNF['FPJVC_ROOM']}`=:room                                         
                                   ", array(
                                                'room' => $sRoom,
                                                'parts' => implode(',', $aParticipants),
                                                'status' => (int)!empty($aParticipants)
                                            )
                             );
    }

    function saveLotSettings($iLotId, $aOptions){
        if (!$iLotId)
            return false;

        if (empty($aOptions))
            $aOptions = array();

        return $this->query("REPLACE INTO `{$this->CNF['TABLE_LOT_SETTINGS']}` 
                                            SET 
                                                `{$this->CNF['FLS_SETTINGS']}` = :values, 
                                                `{$this->CNF['FLS_ID']}` = :id", array('values' => serialize($aOptions), 'id' => $iLotId));
    }

    function isActionAllowed($iLotId, $sAction = BX_MSG_SETTING_MSG){
        $mixedOptions = $this->getLotSettings($iLotId);
        if ($mixedOptions === false)
            return true;

        return in_array($sAction, $mixedOptions);
    }

    function getLotSettings($iLotId){
        if (!$iLotId || !($aOptions = $this -> getOne("SELECT `{$this->CNF['FLS_SETTINGS']}`                             
                                               FROM `{$this->CNF['TABLE_LOT_SETTINGS']}` 
                                               
                                               WHERE `{$this->CNF['FLS_ID']}` = :id", array('id' => $iLotId))))
            return false;

        return @unserialize($aOptions);
    }
}

/** @} */
