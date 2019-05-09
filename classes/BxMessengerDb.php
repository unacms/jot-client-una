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
			$this -> removeFilesByJotId($aJot[$this->CNF['FIELD_MESSAGE_ID']]);

        $iResult = $this -> query("DELETE FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `{$this->CNF['FIELD_ID']}` = :id", array('id' => $iLotId));
		$iResult += $this -> query("DELETE FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :id", array('id' => $iLotId));
		return $iResult;
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
			$this -> removeFilesByJotId($iJotId);
			return $this -> query("DELETE FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId));
		}		
		
		return $this -> query("UPDATE `{$this->CNF['TABLE_MESSAGES']}` 
								SET 
									`{$this->CNF['FIELD_MESSAGE_TRASH']}` = 1,
									`{$this->CNF['FIELD_MESSAGE_LAST_EDIT']}` = UNIX_TIMESTAMP(),
									`{$this->CNF['FIELD_MESSAGE_EDIT_BY']}` = :profile
								WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId, 'profile' => $iProfileId));
	}	
	
	/**
	* Removes participant from the participants list
	*@param int $iLotId lot id
	*@param int $iParticipant profile id
	*@return int/false 
	*/	
	public function leaveLot($iLotId, $iParticipant){
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
		$iMute = $this -> getParams($iLotId, $iParticipant, 'notification');
		return $iMute; 
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

	/**
	* Save message for participants to database
	*@param array $aData lot settings
	*@param array $aParticipants participants list, if it empty then used default for lot
	*@return int affected rows
	*/
	public function saveMessage($aData, $aParticipants = array()) {
		$aLot = array();
		if ((int)$aData['lot'])
			$aLot = $this -> getLotInfoById($aData['lot']);
		
		if (($aData['type'] != BX_IM_TYPE_PRIVATE || $this -> isAuthor($aData['lot'], $aData['member_id'])) && !$this -> isParticipant($aData['lot'], $aData['member_id'], true))
			$this -> addMemberToParticipantsList($aData['lot'], $aData['member_id']);
				
		if (empty($aParticipants) && (int)$aData['lot']) 
			$aParticipants = $this -> getParticipantsList($aData['lot']);	
		
		$aLot = !empty($aLot) ? $aLot : $this -> getLotByUrlAndPariticipantsList($aData['url'], $aParticipants, $aData['type']);
		if (empty($aLot)){
			$iLotID = $this -> createNewLot($aData['member_id'], $aData['title'], $aData['type'], $aData['url'], $aParticipants);  
		} else 
			$iLotID = $aLot[$this->CNF['FIELD_ID']];
		
		return $this -> addNewJot($iLotID, $aData['message'], $aData['member_id']);
	}	

	/**
	* Save message for participants to database
	*@param string $sUrl of the page with lot
	*@param array $aParticipants participants list
	*@param int $iType lot type
	*@return array Lot info
	*/
	public function getLotByUrlAndPariticipantsList($sUrl = '', $aParicipants = array(), $iType = BX_IM_TYPE_PRIVATE){
		if ($iType != BX_IM_TYPE_PRIVATE && $sUrl && $aLot = $this -> getLotByUrl($sUrl)) 
			return $aLot;
		
		$aResult = array();
		if (!empty($aParicipants)){
			$sWhere = " AND `{$this->CNF['FIELD_AUTHOR']}` IN (" . $this -> implode_escape($aParicipants) . ")";

			$aLots = $this -> getAll("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` WHERE `type` = :type {$sWhere}", array('type' => $iType));

			if (!empty($aLots))
			{
				foreach($aLots as $iKey => $aValue)
				{
					 $aPerticipantsList = $this -> getParticipantsList($aValue[$this->CNF['FIELD_ID']]);
					 if (empty($aPerticipantsList) || count($aPerticipantsList) != count($aParicipants)) continue;			
					 
					 sort($aPerticipantsList);
					 sort($aParicipants);
					 
					 if (array_values($aPerticipantsList) == array_values($aParicipants)){ 
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
		$aNotViewed = $this -> getRow("SELECT `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}`, `{$this->CNF['FIELD_MESSAGE_FK']}`, `{$this->CNF['FIELD_MESSAGE_AUTHOR']}`  
										FROM `{$this->CNF['TABLE_MESSAGES']}`
										WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('id' => $iJotId));
		if ($aNotViewed[$this->CNF['FIELD_MESSAGE_NEW_FOR']])
		{
			$aParticipants = explode(',', $aNotViewed[$this->CNF['FIELD_MESSAGE_NEW_FOR']]);
			$iKey = array_search($iProfileId, $aParticipants);
			if ($iKey !== FALSE)
			{
				unset($aParticipants[$iKey]);
				$sNewList = count($aParticipants) > 0 ? implode(',', $aParticipants) : '';
				$this -> query("UPDATE `{$this->CNF['TABLE_MESSAGES']}` SET `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}` = :part WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('part' => $sNewList, 'id' => $iJotId));
                bx_alert($this->_oConfig->getObject('alert'), 'read_jot', $iJotId, $iProfileId, array('author_id' => $aNotViewed[$this->CNF['FIELD_MESSAGE_AUTHOR']], 'lot_id' => $aNotViewed[$this->CNF['FIELD_MESSAGE_FK']]));
                bx_alert($this->_oConfig->getObject('alert'), 'delete_jot_ntfs', $aNotViewed[$this->CNF['FIELD_MESSAGE_FK']],  $iProfileId, array('subobject_id' => $iJotId));
			} 			
		}
	}

	/**
	* Mark all message as read in lot history for member
	*@param int $iLot lot id
	*@param int iProfileId	
	*/
	public function readAllMessages($iLot, $iProfileId){
		$aAll = $this-> getAll("SELECT * FROM `{$this->CNF['TABLE_MESSAGES']}` WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :id AND FIND_IN_SET(:user, `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}`)", array('id' => $iLot, 'user' => $iProfileId));
		foreach($aAll as $iKey => $aValue){
			$aParticipants = explode(',', $aValue[$this->CNF['FIELD_MESSAGE_NEW_FOR']]);
			$iPos = array_search($iProfileId, $aParticipants);
			if ($iPos !== FALSE){
				unset($aParticipants[$iPos]);
				$sNewList = count($aParticipants) > 0 ? implode(',', $aParticipants) : '';
				$this -> query("UPDATE `{$this->CNF['TABLE_MESSAGES']}` SET `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}` = :part WHERE `{$this->CNF['FIELD_MESSAGE_ID']}` = :id", array('part' => $sNewList, 'id' => $aValue[$this->CNF['FIELD_MESSAGE_ID']]));
                bx_alert($this->_oConfig->getObject('alert'), 'read_jot', $aValue[$this->CNF['FIELD_MESSAGE_ID']], $iProfileId, array('author_id' => $aValue[$this->CNF['FIELD_MESSAGE_AUTHOR']], 'lot_id' => $iLot));
                bx_alert($this->_oConfig->getObject('alert'), 'delete_jot_ntfs', $aValue[$this->CNF['FIELD_MESSAGE_FK']], $iProfileId, array('subobject_id' => $aValue[$this->CNF['FIELD_MESSAGE_ID']]));
			} 			
		}		
	}

	/**
	*Add new new jot to database  
	*@param int $iLotID  lot id
	*@param string $sMessage posted message 
	*@param int iProfile Id	owner of the message
	*@return  int affected rows
	*/
	private function addNewJot($iLotID, $sMessage, $iProfileId)
	{
		$sParticipants = $this -> getParticipantsList($iLotID, false /* as string list*/, $iProfileId);
		$sQuery = $this->prepare("INSERT INTO `{$this->CNF['TABLE_MESSAGES']}` 
												SET  `{$this->CNF['FIELD_MESSAGE']}` = ?, 
													 `{$this->CNF['FIELD_MESSAGE_FK']}` = ?, 
													 `{$this->CNF['FIELD_MESSAGE_AUTHOR']}` = ?,
													 `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}` = ?,
													 `{$this->CNF['FIELD_MESSAGE_ADDED']}` = UNIX_TIMESTAMP()", $sMessage, $iLotID, $iProfileId, $sParticipants);
		
		return $this->query($sQuery) ? $this -> lastId() : false;
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
	private function addMemberToParticipantsList($iLotId, $iParticipantId){
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
		$sInsideOrder = 'DESC';

		if ($iStart)
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
			return array();
		
		return $bIdOnly ? $iLotId : $this -> getRow("SELECT * FROM `{$this->CNF['TABLE_ENTRIES']}` 
						WHERE `{$this->CNF['FIELD_ID']}` = :lot", array('lot' => $iLotId));
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
		if (!(int)$iLotId) return false;
		
		$aJots = $this -> getMyJots($iProfileId, true, $iLotId);
		return count($aJots);
	}
	
	public function getLeftJots($iLotId, $iJotId = 0){
		if (!(int)$iLotId) return false;
		
		$sWhere = '';
		$aWhere['lot'] = $iLotId;
		if ($iJotId)
		{
			$sWhere = ' AND `id` > :jot';
			$aWhere['jot'] = $iJotId;
		}
		
		return $this->getOne("SELECT COUNT(*) FROM `{$this->CNF['TABLE_MESSAGES']}` 
								WHERE `{$this->CNF['FIELD_MESSAGE_FK']}` = :lot {$sWhere}
								ORDER BY `{$this->CNF['FIELD_MESSAGE_ID']}`", $aWhere);
	}

	/**
	* Get all jots of the member
	*@param int $iProfileId
	*@param boolean $bUnread return only unread member
	*@return array list of jots
	*/
	public function getMyJots($iProfileId, $bUnread = false, $iLotId = 0){
		$sWhere = '';
		$aWhere['profile'] = $iProfileId;
		
		if ($bUnread)
		{
			$sWhere = " AND FIND_IN_SET(:parts, `j`.`{$this->CNF['FIELD_MESSAGE_NEW_FOR']}`)";
			$aWhere['parts'] = $iProfileId;
		}
		
		if ($iLotId){
			$sWhere .= " AND `l`.`{$this->CNF['FIELD_ID']}` = :lot";
			$aWhere['lot'] = $iLotId;
		}

		return $this-> getAll("SELECT `j`.*
			FROM `{$this->CNF['TABLE_ENTRIES']}` as `l`
			LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}` 
			WHERE FIND_IN_SET(:profile, `l`.`{$this->CNF['FIELD_PARTICIPANTS']}`) {$sWhere}
			ORDER BY `j`.`{$this->CNF['FIELD_MESSAGE_ADDED']}` ASC", $aWhere);
	}

	/**
	* Get all member's lots
	*@param int $iProfileId
	*@param int $iType
	*@param string $sParam search keyword
	*@param boolean $bUnread get lots with unread jots only
	*@param int $iLotId lot id 
	*@return array list of lots
	*/
	public function getMyLots($iProfileId, $iType = 0, $sParam = '', $bUnread = false, $iLotId = 0, $iStar = 0)
	{
		$sJOIN = $sHaving = $sWhere = '';
		$aSWhere = array();
		$aWhere['parts'] = $aWhere['profile'] = $iProfileId;
		
		if ($sParam)
		{
			$sParamWhere = "`j`.`{$this->CNF['FIELD_MESSAGE']}` LIKE :message OR `l`.`{$this->CNF['FIELD_TITLE']}` LIKE :title";
			$aWhere['title'] = "%{$sParam}%";
			$aWhere['message'] = "%{$sParam}%";
			$aProfiles = BxDolService::call('system', 'profiles_search', array($sParam), 'TemplServiceProfiles');
			if (!empty($aProfiles))
            {
                $aRegexp = array();
                foreach($aProfiles as &$aProfile)
                    $aRegexp[] = "(^|,){$aProfile['value']}(,|$)";

                if (!empty($aRegexp)) {
                    $aWhere['part_search'] = implode('|', $aRegexp);
                    $sParamWhere .= " OR `participants` REGEXP :part_search";
                }
            }

            $aSWhere[] = "({$sParamWhere})";
		}

		if ($iType)
		{
			$aSWhere[] = " `l`.`{$this->CNF['FIELD_TYPE']}` = :type ";
			$aWhere['type'] = $iType;
		}
		
		if ($bUnread)
		{
			$sHaving = "HAVING `unread_num` != 0";
		}
		
		if ($iLotId)
		{
			$aSWhere[] = " `l`.`{$this->CNF['FIELD_ID']}` = :id ";
			$aWhere['id'] = $iLotId;	
		}
		
		if ($iStar)
		{
			$sJOIN = "INNER JOIN `{$this->CNF['TABLE_USERS_INFO']}` as `u` ON `u`.`{$this->CNF['FIELD_INFO_LOT_ID']}` = `l`.`{$this->CNF['FIELD_ID']}`";
			$aSWhere[] = "`u`.`{$this->CNF['FIELD_INFO_STAR']}` = 1";
		}
		
		if (!empty($aSWhere))
				$sWhere = ' AND ' . implode(' AND ', $aSWhere);

		return $this-> getAll("SELECT 
			`l`.*,
			`p`.`count` as `unread_num`,
			`p`.`{$this->CNF['FIELD_MESSAGE_ADDED']}` as `last_created`,
			MAX(`j`.`{$this->CNF['FIELD_MESSAGE_ADDED']}`) as `last_jot_created`
			FROM `{$this->CNF['TABLE_ENTRIES']}` as `l`
			{$sJOIN}
			LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}`			
			LEFT JOIN (
						SELECT 
							`{$this->CNF['FIELD_MESSAGE_FK']}`,
							COUNT(*) as `count`, 
							MAX(`{$this->CNF['FIELD_MESSAGE_ADDED']}`) as `{$this->CNF['FIELD_MESSAGE_ADDED']}`
						FROM `{$this->CNF['TABLE_MESSAGES']}` 
						WHERE FIND_IN_SET(:parts, `{$this->CNF['FIELD_MESSAGE_NEW_FOR']}`)
						GROUP BY `{$this->CNF['FIELD_MESSAGE_FK']}`
					  ) as `p` ON `p`.`{$this->CNF['FIELD_MESSAGE_FK']}` = `l`.`{$this->CNF['FIELD_ID']}`
			WHERE (FIND_IN_SET(:profile, `l`.`{$this->CNF['FIELD_PARTICIPANTS']}`) OR `l`.`{$this->CNF['FIELD_AUTHOR']}`=:profile) {$sWhere} 
			GROUP BY `l`.`{$this->CNF['FIELD_ID']}`
			{$sHaving}
			ORDER BY `last_created` DESC, `last_jot_created` DESC", $aWhere);
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
			$this -> removeFilesByJotId($aJot[$this->CNF['FIELD_MESSAGE_ID']]);	
			
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
		$aJotInfo = array();

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
	
	public function updateFiles($iJotId, $sField = 'jot_id', $sValue){
		$sQuery = $this->prepare("UPDATE `{$this->CNF['OBJECT_STORAGE']}`
									SET  `{$sField}` = ?
									WHERE `{$this->CNF['FIELD_ST_ID']}` = ?", $sValue, $iJotId);
		
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
		$iJotId = 0;
		
		if (!$iLotId)
			return $iJotId;
		
		$aUnreadJot = $this -> getMyJots($iProfileId, true, $iLotId);
		if (!empty($aUnreadJot))
			$iJotId = $aUnreadJot[0][$this->CNF['FIELD_MESSAGE_ID']];
		
		return $iJotId;
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

    public function getLotFiles($iLotId){
        return $this->getAll("SELECT `s`.* 
                                         FROM `{$this->CNF['OBJECT_STORAGE']}` as `s`
                                         LEFT JOIN `{$this->CNF['TABLE_MESSAGES']}` as `j` ON `s`.`{$this->CNF['FIELD_ST_JOT']}` = `j`.`{$this->CNF['FIELD_MESSAGE_ID']}`
                                         LEFT JOIN `{$this->CNF['TABLE_ENTRIES']}` as `l` ON `l`.`{$this->CNF['FIELD_ID']}` = `j`.`{$this->CNF['FIELD_MESSAGE_FK']}` 
                                         WHERE `l`.`{$this->CNF['FIELD_ID']}` = :id", array('id' => $iLotId));
    }

    public function isPushNotificationsEnabled(){
        if (!$this->isModuleByName('bx_notifications'))
            return false;

        return $this -> getOne("SELECT `s`.`active`
                                        FROM `bx_notifications_handlers` as `h` 
                                        LEFT JOIN `bx_notifications_settings` as `s` ON `h`.`id` = `s`.`handler_id`
                                        WHERE `h`.`group`=:module AND `h`.`type`='insert' AND `s`.`delivery` = 'push'", array('module' => $this->_oConfig->getName())) == 1;
    }

    public function isAllowedToSendNtfs($iProfileId, $iLotId){
        if (!$this->isModuleByName('bx_notifications'))
            return true;

        $iNumber = (int)$this->CNF['MAX_NTFS_NUMBER'];
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
                                    )) < $iNumber;
    }

    /**
     * @param int $iJotId id of the message
     * @param int $iProfileId viewer id
     * @param int $iJotAuthor message's author
     * @param int $iLotAuthorId author id
     * @return bool
     */
    public function isAllowedToDeleteJot($iJotId, $iProfileId=0, $iJotAuthor=0, $iLotAuthorId=0){
        $oCNF = &$this -> _oConfig -> CNF;
        if (!$iJotId)
            return true;

        if (!$iProfileId)
            $iProfileId = bx_get_logged_profile_id();

        if (!$iJotAuthor){
            $aJot = $this->getJotById($iJotId);
            $iJotAuthor = $aJot[$oCNF['FIELD_MESSAGE_AUTHOR']];
        }

        if (!$iLotAuthorId) {
            $iLotId = $this->getLotByJotId($iJotId);
            $bIsLotAuthor = $this -> isAuthor($iLotId, $iProfileId);
        }
        else
            $bIsLotAuthor = $iLotAuthorId == $iProfileId;


        return ($oCNF['ALLOW_TO_REMOVE_MESSAGE'] && $iJotAuthor == $iProfileId) || $bIsLotAuthor || isAdmin();
    }
}

/** @} */
