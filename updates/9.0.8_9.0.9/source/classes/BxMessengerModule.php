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
 
require_once('BxMessengerStorage.php');

define('BX_IM_TYPE_PUBLIC', 1);
define('BX_IM_TYPE_PRIVATE', 2);
define('BX_IM_TYPE_SETS', 3);
define('BX_IM_TYPE_GROUPS', 4);
define('BX_IM_TYPE_EVENTS', 5);
define('BX_IM_EMPTY_URL', '');
define('BX_IM_EMPTY', 0);
define('BX_ATT_TYPE_FILES', 'files');
define('BX_ATT_TYPE_REPOST', 'repost');

/**
 * Messenger module
 */
class BxMessengerModule extends BxBaseModTextModule
{
	private $_iUserId = 0;
	private $_iJotId = 0;
	function __construct(&$aModule)
	{
		parent::__construct($aModule);
		$this -> _iUserId = bx_get_logged_profile_id();		
	}
	/**
	* Returns left side block for messenger page and loads config data
	*/
	public function serviceGetBlockInbox()
	{
		if (!$this -> isLogged())
			return '';	   

		$iProfile = bx_get('profile_id');
		$iProfile = $iProfile == $this -> _iUserId ? 0 : $iProfile;
		
		$iLotId = 0;
		if ($this -> _iJotId)
		{
			$iLotId = $this -> _oDb -> getLotByJotId($this -> _iJotId);
			if (!empty($iLotId) && !$this ->_oDb -> isParticipant($iLotId, $this -> _iUserId))
			{
				$this -> _iJotId = BX_IM_EMPTY;
				$iLotId	= BX_IM_EMPTY;
			}
		}
		
		return	$this -> _oTemplate -> getLotsColumn($iLotId, $this -> _iJotId, $this -> _iUserId, (int)$iProfile).
				$this -> _oTemplate -> loadConfig($this -> _iUserId);
	}
	/**
	* Returns right side block for messenger page
	*/
	public function serviceGetBlockLot()
	{
		if (!$this -> isLogged())
			return '';
		
		$iProfile = bx_get('profile_id');
		$iProfile = $iProfile == $this -> _iUserId ? 0 : $iProfile;	   
		return $this -> _oTemplate -> getLotWindow($iProfile, BX_IM_EMPTY, true, $this -> _iJotId);
	}
	/**
	* Returns block with messenger for any page
	*@param string $sModule module name
    *@return string block's content
	*/
	public function serviceGetBlockMessenger($sModule)
	{		
		$this->_oTemplate-> loadCssJs('view');
		
		$sUrl = $this -> _oConfig -> getPageIdent();
		$aLotInfo = $this -> _oDb -> getLotByUrl($sUrl);
		if (empty($aLotInfo) && $sModule)
			$aLotInfo = $this -> _oDb -> getLotByClass($sModule);
	   
		$sConfig = $this -> _oTemplate -> loadConfig($this -> _iUserId);
		$aBlock = $this -> _oTemplate -> getTalkBlock($this -> _iUserId, !empty($aLotInfo) ?
				(int)$aLotInfo[$this -> _oConfig -> CNF['FIELD_ID']] : BX_IM_EMPTY,
				BX_IM_EMPTY, $this -> _oConfig -> getTalkType($sModule), true /* create messenger window even if chat doesn't exist yet */);

		return $sConfig . $aBlock['content'];
	}
   
	/**
	* Adds messenger block to all pages with comments and trigger pages during installation
	*/
	public function serviceAddMessengerBlocks(){
		if (!isAdmin()) return '';
	   
		$aPages = $this -> _oDb -> getPagesWithComments();
	   
		$aUrl = array();
		foreach($aPages as $sModule => $sPage){
			$sParams = parse_url($sPage, PHP_URL_QUERY);
			if (!empty($sParams))
					parse_str($sParams, $aUrl);
			   
			if (isset($aUrl['i'])){
				$sPage = BxDolPageQuery::getPageObjectNameByURI($aUrl['i']);   
				if (!$this -> _oDb -> isBlockAdded($sPage))
						$this -> _oDb -> addMessengerBlock($sPage);
			}
		}	   
	}

	/**
	* Builds main messenger page
	*/   
	public function actionHome()
	{
		if (!$this -> isLogged())
			bx_login_form();
	  
		$oPage = BxDolPage::getObjectInstance('bx_messenger_main');

		if (!$oPage) {
			$this->_oTemplate->displayPageNotFound();
			exit;
		}
	   
		$s = $oPage->getCode();

		$this->_oTemplate = BxDolTemplate::getInstance();
		$this->_oTemplate->setPageNameIndex (BX_PAGE_DEFAULT);
		$this->_oTemplate->setPageContent ('page_main_code', $s);
		$this->_oTemplate->getPageCode();
	}
	
	public function actionArchive($iJotId)
	{
		$this -> _iJotId = $iJotId;
		$this -> actionHome();
	}
   
	/**
	* Create List of participants received from request (POST, GET)
	* @param mixed $mixedPartisipants participants list
	* @return array  participants list
	*/
	private function getParticipantsList($mixedPartisipants){
		if (empty($mixedPartisipants)) 
			return array();
		$aParticipants = is_array($mixedPartisipants) ? $mixedPartisipants : array(intval($mixedPartisipants));
		$aParticipants[] = $this -> _iUserId;
		return array_unique($aParticipants, SORT_NUMERIC);
	}   
   
	/**
	* Send function occurs when member posts a message
	* @return array json result
	*/
	public function actionSend(){	   
		$sUrl =	$sTitle = '';
		$sMessage = trim(bx_get('message'));		
		$iLotId = (int)bx_get('lot');	   
		$iType = bx_get('type');
		$iTmpId = bx_get('tmp_id');
		$aFiles = bx_get('files');

		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_only_for_logged')));

		if (!$sMessage && empty($aFiles))
			return echoJson(array('code' => 2, 'message' => _t('_bx_messenger_send_message_no_data')));
	   
		$iType = $this -> _oDb -> isLotType($iType) ? $iType : BX_IM_TYPE_PUBLIC;	   
		if ($iType != BX_IM_TYPE_PRIVATE)
		{
			$sUrl = bx_get('url');
			$sTitle = bx_get('title');
		}	   
	   
		// prepare participants list
		$aParticipants = $this -> getParticipantsList(bx_get('participants'));	   
		if (!$iLotId && empty($aParticipants) && $iType == BX_IM_TYPE_PRIVATE)
			return echoJson(array('code' => 2, 'message' => _t('_bx_messenger_send_message_no_data')));
	   
		if ($sMessage)
		{
			$sMessage = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $sMessage);
			$sMessage = htmlspecialchars_adv($sMessage);
			$sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sMessage, (int)$this->_oConfig-> CNF['MAX_SEND_SYMBOLS']);
			
			if ($iType != BX_IM_TYPE_PRIVATE && $sUrl)
					$sUrl = $this -> getPreparedUrl($sUrl);
		}

		$aResult = array('code' => 0);
		if (($sMessage || !empty($aFiles)) && ($iId = $this -> _oDb -> saveMessage(array(
												'message'	=> $sMessage,
												'type'		=> $iType,
												'member_id' => $this -> _iUserId,
												'url' => $sUrl,
												'title'	=> $sTitle,
												'lot' => $iLotId
											), $aParticipants)))
		{		   
			if (!$iLotId)
				$aResult['lot_id'] = $this -> _oDb -> getLotByJotId($iId);
				
			if (!empty($aFiles))
			{
				$oStorage = BxDolStorage::getObjectInstance($this->_oConfig-> CNF['OBJECT_STORAGE']);
				$aFilesNames = array();
				foreach($aFiles as $iKey => $sName)
				{
					$iFile = $oStorage -> storeFileFromPath(BX_DIRECTORY_PATH_TMP . $sName, $iType == BX_IM_TYPE_PRIVATE, $this -> _iUserId, (int)$iId);
					if ($iFile)
					{
						$oStorage -> afterUploadCleanup($iFile, $this -> _iUserId);
						$this -> _oDb -> updateFiles($iFile, $this->_oConfig-> CNF['FIELD_ST_JOT'], $iId);
						$aFilesNames[] = $sName;
					}
					else 
						$aResult = array('code' => 2, 'message' => $oStorage->getErrorString());
				}
				
				if (!empty($aFilesNames))
					$this -> _oDb -> addAttachment($iId, implode(',', $aFilesNames), BX_ATT_TYPE_FILES);
			}
				
			$aResult['jot_id'] =  $iId;
			$aResult['tmp_id'] =  $iTmpId;
            $this -> onSendJot($iId);

        }
		else
			$aResult = array('code' => 2, 'message' => _t('_bx_messenger_send_message_save_error'));
	   
		BxDolSession::getInstance()-> exists($this -> _iUserId);			   
		echoJson($aResult);
	}
   
	/**
	* Loads talk to the right side block when member choose conversation or when open messenger page
	* @return array with json result
	*/
	public function actionLoadTalk(){
		$iId = (int)bx_get('lot_id');
		$iJotId = (int)bx_get('jot_id');
		
		if (!$this -> isLogged() || !$iId || !$this -> _oDb -> isParticipant($iId, $this -> _iUserId)){
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
		};
	   	
		if ((int)bx_get('mark_as_read'))
			$this -> _oDb -> readAllMessages($iId, $this -> _iUserId);

        $sTitle = '';
		$aBlock = $this -> _oTemplate -> getTalkBlock($this -> _iUserId, $iId, $iJotId, BX_IM_TYPE_PUBLIC, false, $sTitle);
		echoJson(array('code' => 0, 'html' =>  $aBlock['content'], 'title' => $aBlock['title']));
	}
   
	/**
	* Loads messages for specified lot(conversation)
	* @return array with json
	*/
	public function actionLoadJots(){	   
		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
	   
		$iId = (int)bx_get('id');
	   
		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
	   
		$sContent = $this -> _oTemplate -> getPostBoxWithHistory($this -> _iUserId, (int)$iId > 0 ? $iId : BX_IM_EMPTY, BX_IM_TYPE_PRIVATE);
	   
		echoJson(array('code' => 0, 'html' =>  $sContent));
	}
   
	/**
	* Search for Lots by keywords in the right side block
	* @return string with json
	*/
	public function actionSearch(){	   
		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
	   
		$sParam = bx_get('param');
		$iType = bx_get('type');
		$iStarred = bx_get('starred');
	   
		$aMyLots = $this -> _oDb -> getMyLots($this -> _iUserId, $iType, $sParam, BX_IM_EMPTY, BX_IM_EMPTY, $iStarred);
		if (empty($aMyLots))
				$sContent  = MsgBox(_t('_bx_messenger_txt_msg_no_results'));
			else	   
				$sContent = $this -> _oTemplate -> getLotsPreview($this -> _iUserId, $aMyLots);
			   
		echoJson(array('code' => 0, 'html' =>  $sContent));
	}
   
	/**
	* Update brief of the specified lot in the lots list
	* @return string with json
	*/
	public function actionUpdateLotBrief(){
		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
	   
		$iLotId = (int)bx_get('lot_id');	   
		if (!$this -> isLogged() || !$iLotId)
			return echoJson(array('code' => 1));
	   
		$aMyLots = $this -> _oDb -> getMyLots($this -> _iUserId, BX_IM_EMPTY, BX_IM_EMPTY, BX_IM_EMPTY, $iLotId);		
		if (!empty($aMyLots))
		{
			$sContent = $this -> _oTemplate -> getLotsPreview($this -> _iUserId, $aMyLots);
			return echoJson(array('code' => 0, 'html' =>  $sContent));
		}
		
		echoJson(array('code' => 1));
	}
   
	/**
	* Prepare url for Lot title if if was created on separated page
	* @param string URL
	* @return string URL
	*/
	private function getPreparedUrl($sUrl)
	{
		if (!$sUrl) return false;
		$aUrl = parse_url($sUrl);
		return strtolower($aUrl['path'] . (isset($aUrl['query']) ? '?' . $aUrl['query'] : ''));
	}
   
	/**
	* Loads messages for  lot(conversation) (when member wants to view history or get new messages from participants)
	* @return string with json
	*/
	public function actionUpdate(){	   
		$sUrl = bx_get('url');
		$iJot = (int)bx_get('jot');
		$iLotId = (int)bx_get('lot');
		$sLoad = bx_get('load');
	   
		if ($sLoad == 'new' && !(int)$iJot)
		{
			$aMyLatestJot = $this -> _oDb -> getLatestJot($iLotId, $this -> _iUserId);
			if (empty($aMyLatestJot))
				return echoJson(array('code' => 1));
			else
				$iJot = (int)$aMyLatestJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']];
		}   
		   
	   	$sUrl = $sUrl ? $this -> getPreparedUrl($sUrl) : '';
		$sContent = '';
		switch($sLoad)
		{
			case 'new':
			case 'prev':
					$aOptions = array(
								'lot_id' => $iLotId,
								'url' => $sUrl,
								'start'	=> $iJot,
								'load' => $sLoad,
								'limit'	=> ($sLoad != 'new' ? $this -> _oConfig -> CNF['MAX_JOTS_LOAD_HISTORY'] : 0)								
							 );
					$sContent = $this -> _oTemplate -> getJotsOfLot($this -> _iUserId, $aOptions);
				break;
			case 'edit':
					$aJotInfo = $this -> _oDb -> getJotById($iJot);
					$sContent = $aJotInfo[$this -> _oConfig -> CNF['FIELD_MESSAGE']];
			case 'delete':
					$sContent .= $this -> _oTemplate -> getMessageIcons($iJot, $sLoad, $this -> _oDb -> isAuthor($iLotId, $this -> _iUserId) || isAdmin());
				break;
		}
			
		$aResult = array('code' => 0, 'html' => $sContent);
	   
		// update session
		if ($this -> _iUserId)
			BxDolSession::getInstance()-> exists($this -> _iUserId);
		
		echoJson($aResult);
	}
   
	/**
	* Occurs when member wants to create new conversation(lot)
	* @return array with json
	*/
	public function actionCreateLot(){
		if (!$this -> isLogged())
			return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
	   
		$iProfileId = (int)bx_get('profile');
		$iLot = (int)bx_get('lot');
		echoJson(array('title' => _t('_bx_messenger_lots_menu_create_lot_title'), 'html' => $this -> _oTemplate -> getLotWindow($iProfileId, $iLot, false)));
	}
   
	/**
	* Occurs when member adds or edit participants list for new of specified lot
	* @return string with json code
	*/
	public function actionGetAutoComplete(){
		$sExcept = bx_get('except');
		$aResult = $aExcept = array();
		if ($sExcept)
			$aExcept = explode(',', $sExcept);
		
		$aUsers = BxDolService::call('system', 'profiles_search', array(bx_get('term'), $this -> _oConfig -> CNF['PARAM_SEARCH_DEFAULT_USERS'] + sizeof($aExcept)), 'TemplServiceProfiles');
		if (empty($aUsers))
			return echoJson(array('items' => $aResult));

		foreach($aUsers as $iKey => $aValue){
				if ($aValue['value'] == $this -> _iUserId || in_array($aValue['value'], $aExcept) || !$this -> onCheckContact($this -> _iUserId, $aValue['value'])) continue;
			   
				$oProfile = BxDolProfile::getInstance($aValue['value']);
				$aProfileInfo = $oProfile -> getInfo();	
				$aProfileInfoDetails = BxDolService::call($aProfileInfo['type'], 'get_content_info_by_id', array($aProfileInfo['content_id']));	
				$oAccountInfo = BxDolAccount::getInstance($aProfileInfo['account_id']);	
            if ($oProfile && !empty($aProfileInfoDetails) && !empty($oAccountInfo))
					$aResult[] = array(
							'value' => $oProfile -> getDisplayName(),
							'icon' => $oProfile -> getThumb(),
							'id' => $oProfile -> id(),
							'description' => _t('_bx_messenger_search_desc', 
													bx_process_output($oAccountInfo -> getInfo()['logged'], BX_DATA_DATE_TS), 
													bx_process_output($aProfileInfoDetails['added'], BX_DATA_DATE_TS))
					);
		}
			   
		echoJson(array('items' => $aResult));
	}
	
	/**
	* Returns processed videos by received videos ids
	* @return string with json
	*/
	public function actionGetProcessedVideos(){
		$aVideos = bx_get('videos');
		$aResult = array();
		
		if (empty($aVideos))
			return echoJson($aResult);
		
		$aTranscodersVideo = array();
		if (isset($this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS']) && $this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS'])
			$aTranscodersVideo = array(
				'poster' => BxDolTranscoderImage::getObjectInstance($this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS']['poster']),
				'mp4' => BxDolTranscoderVideo::getObjectInstance($this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS']['mp4']),
				'webm' => BxDolTranscoderVideo::getObjectInstance($this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS']['webm']),
			);
		
		if (empty($aTranscodersVideo))
			return echoJson($aResult);
				
		foreach($aVideos as $iKey => $iValue)
		{
			$sPoster = $aTranscodersVideo['poster']->getFileUrl($iValue);
			$sMp4 = $aTranscodersVideo['mp4']->getFileUrl($iValue);
			$sWebM = $aTranscodersVideo['webm']->getFileUrl($iValue);
														
			if ($aTranscodersVideo['poster'] -> isFileReady($iValue) && $aTranscodersVideo['mp4'] -> isFileReady($iValue) && $aTranscodersVideo['webm'] -> isFileReady($iValue))
				$aResult[$iValue] = BxTemplFunctions::getInstance()->videoPlayer(
																					$sPoster,
																					$sMp4,
																					$sWebM,
																					false,
																					''
																				);
		}
			   
		echoJson($aResult);
	}
   
	/**
	* Search for lot by participants list and occurs when member edit participants list
	* @return string with json
	*/
	public function actionFindLot(){
		$aParticipants = $this -> getParticipantsList(bx_get('participants'));

		$aResult = array('lotId' => 0);
		if (!empty($aParticipants) && ($aChat = $this -> _oDb -> getLotByUrlAndPariticipantsList(BX_IM_EMPTY_URL, $aParticipants, BX_IM_TYPE_PRIVATE)))
				$aResult['lotId'] = $aChat[$this -> _oConfig -> CNF['FIELD_ID']];
		   
		echoJson($aResult);
	}

	/**
	* Updats participants list (occurs when create new lost with specified participants or update already existed list)
	* @return string with json
	*/
	public function actionSaveLotsParts(){
		$iLotId = bx_get('lot');   
		$aParticipants = $this -> getParticipantsList(bx_get('participants'));
			
		$aResult = array('message' => _t('_bx_messenger_save_part_failed'), 'code' => 1);
		if (($iLotId && !($this -> _oDb -> isAuthor($iLotId, $this -> _iUserId) || isAdmin())) || (empty($aParticipants) && !$iLotId)){
			return echoJson($aResult);
		}
		
		$aLot = $this -> _oDb -> getLotByUrlAndPariticipantsList(BX_IM_EMPTY_URL, $aParticipants, BX_IM_TYPE_PRIVATE);
		if (!empty($aLot))
			$iLotId = $aLot[$this -> _oConfig -> CNF['FIELD_ID']];
		
        $oOriginalParts = $this -> _oDb -> getParticipantsList($iLotId);
		$aNewParticipants = $aParticipants;
        $aRemoveParticipants = array();

		$aResult = array('message' => _t('_bx_messenger_save_part_success'), 'code' => 0);
		if (!$iLotId)
		{
			$iLotId = $this -> _oDb -> createNewLot($this -> _iUserId, BX_IM_EMPTY_URL, BX_IM_TYPE_PRIVATE, BX_IM_EMPTY_URL, $aParticipants);
			$aResult['lot'] = $iLotId;
            $this -> onCreateLot($iLotId);
		}
		else {
			if(!$this -> _oDb -> savePariticipantsList($iLotId, $aParticipants))
				$aResult = array('code' => 2, 'lot' => $iLotId);
		   
            $aRemoveParticipants = array_diff($oOriginalParts, $aParticipants);
            $aNewParticipants = array_diff($aParticipants, $oOriginalParts);
        }

        foreach($aNewParticipants as &$iPartId)
            $this->onAddNewParticipant($iLotId, $iPartId);

        foreach($aRemoveParticipants as &$iPartId)
            $this->onRemoveParticipant($iLotId, $iPartId);

		echoJson($aResult);
	}
   
	/**
	* Removes specified lot
	* @return string with json
	*/
	public function actionDelete(){
		$iLotId = bx_get('lot');
		$aResult = array('message' => _t('_bx_messenger_can_not_delete'), 'code' => 1);

		if (!$iLotId || !($this -> _oDb -> isAuthor($iLotId, $this -> _iUserId) || isAdmin())){
			return echoJson($aResult);
		}

        $aLotInfo = $this -> _oDb -> getLotInfoById($iLotId);
        $JotsList = $this -> getJotsByLotId($iLotId);
        $CNF = &$this->_oConfig->CNF;

		if ($this -> _oDb -> deleteLot($iLotId)) {
				$aResult = array('code' => 0);
            $this -> onDeleteLot($iLotId, $aLotInfo[$CNF['FIELD_AUTHOR']]);

            foreach($JotsList as &$aJot)
              $this->onDeleteJot($aJot[$CNF['FIELD_MESSAGE_FK']], $aJot[$CNF['FIELD_MESSAGE_ID']], $aJot[$CNF['FIELD_MESSAGE_AUTHOR']]);
        }
		   
		echoJson($aResult);
	}
	
	/**
	* Removes specified jot
	* @return string with json
	*/
	public function actionDeleteJot(){
		$iJotId = bx_get('jot');
		$bCompletely = (int)bx_get('completely');
		$aJotInfo = $this -> _oDb -> getJotById($iJotId);
        $CNF = &$this->_oConfig->CNF;

		$aResult = array('code' => 1);
		if (empty($aJotInfo))
			return echoJson($aResult);
		
		$bIsLotAuthor = $this -> _oDb -> isAuthor($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this -> _iUserId);
		if (!(isAdmin() || (!$bCompletely && $this -> _oDb -> isAuthor($iJotId, $this -> _iUserId, false)) || $bIsLotAuthor))
			return echoJson($aResult);
					
		if ($this -> _oDb -> deleteJot($iJotId, $this -> _iUserId, $bCompletely)) {
			$aResult = array('code' => 0, 'html' => !$bCompletely ? $this -> _oTemplate -> getMessageIcons($iJotId, 'delete', isAdmin() || $bIsLotAuthor) : '');
            $this -> onDeleteJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $iJotId, $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']]);
        }
	   
		echoJson($aResult);
	}
	
	/**
	* Get body of the jot
	* @return string with json
	*/
	public function actionViewJot(){
		$iJotId = bx_get('jot');
		$aJotInfo = $this -> _oDb -> getJotById($iJotId);
		if (empty($aJotInfo) || !(isAdmin() || $this -> _oDb -> isAuthor($aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $this -> _iUserId)))
			return echoJson(array('code' => 1));
		
		$aResult = array('code' => 0, 'html' => $this -> _oTemplate -> getJotsBody($iJotId));  
		echoJson($aResult);
	}
	
	/**
	* Jot edit panel 
	* @return string with json
	*/
	public function actionEditJotForm(){
		$iJotId = bx_get('jot');
		$aResult = array('code' => 1);
		$aJotInfo = $this -> _oDb -> getJotById($iJotId);
		
		if (empty($aJotInfo) || !(isAdmin() || $this -> _oDb -> isAuthor($iJotId, $this -> _iUserId, false) || $this -> _oDb -> isAuthor($aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $this -> _iUserId))){
			return echoJson($aResult);
		}
		
		$aResult = array('code' => 0, 'html' => $this -> _oTemplate-> getEditJotArea($iJotId));
		echoJson($aResult);
	}
	
	public function actionEditJot(){
		$iJotId = bx_get('jot');
		$aJotInfo = $this -> _oDb -> getJotById($iJotId);		
		
		$sMessage = preg_replace('/\<br(\s*)?\/?\>/i', "\n", bx_get('message'));
		$sMessage = htmlspecialchars_adv($sMessage);
		$sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sMessage, (int)$this->_oConfig-> CNF['MAX_SEND_SYMBOLS']);

		$aResult = array('code' => 1);
		if (empty($aJotInfo) || !(isAdmin() || $this -> _oDb -> isAuthor($iJotId, $this -> _iUserId, false) || $this -> _oDb -> isAuthor($aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $this -> _iUserId)))
			return echoJson($aResult);
		
		if ($this -> _oDb -> editJot($iJotId, $this -> _iUserId, $sMessage)) {
			$aResult = array('code' => 0, 'html' => $this -> _oTemplate -> getMessageIcons($iJotId, 'edit'));
            $this->onUpdateJot($aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $iJotId, $aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_AUTHOR']]);
        }
		
		echoJson($aResult);
	}
	
	/**
	* Removes specified jot
	* @return string with json
	*/
	public function actionDeleteFile(){
		$iFileId = bx_get('id');
		$aResult = array('code' => 1, 'message' => _t('_bx_messenger_post_file_not_found'));
		if (!$iFileId)
			return echoJson($aResult);
		 
		$CNF = &$this -> _oConfig -> CNF;
		$oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
		$aFile = $oStorage -> getFile($iFileId);
		
		if ($aFile[$CNF['FIELD_ST_AUTHOR']] != $this -> _iUserId && !isAdmin())
			return echoJson($aResult);
		
		if ($oStorage -> deleteFile($iFileId, $this -> _iUserId))
		{
			$aResult = array('code' => 0);
			
			$aJotInfo = $this -> _oDb -> getJotById($aFile[$CNF['FIELD_ST_JOT']]);
			$aJotFiles = $this -> _oDb -> getJotFiles($aFile[$CNF['FIELD_ST_JOT']]);
			
			if (count($aJotFiles) == 0 && !$aJotInfo[$CNF['FIELD_MESSAGE']] && $this -> _oDb -> deleteJot($aJotInfo[$CNF['FIELD_MESSAGE_ID']], $this -> _iUserId)){
				$aResult['empty_jot'] = 1;
                $this->onDeleteJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $aJotInfo[$CNF['FIELD_MESSAGE_ID']], $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']]);
			}
			else
                $this->onUpdateJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $aJotInfo[$CNF['FIELD_MESSAGE_ID']], $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']]);
		}

		echoJson($aResult);
	}
 
	  /**
	* Remove member from participants list
	* @return string with json
	*/
	public function actionLeave(){
		$iLotId = bx_get('lot');

		if (!$iLotId || !$this -> _oDb -> isParticipant($iLotId, $this -> _iUserId)){
			return echoJson(array('message' => _t('_bx_messenger_not_participant'), 'code' => 1));
		}

		if ($this -> _oDb -> isAuthor($iLotId, $this -> _iUserId))
			return echoJson(array('message' => _t('_bx_messenger_cant_leave'), 'code' => 1));


		if ($this -> _oDb -> leaveLot($iLotId, $this -> _iUserId))
			return echoJson(array('message' => _t('_bx_messenger_successfully_left'), 'code' => 0));   
	}

	/**
	* Block notifications from specified lot(conversation)
	* @return string with json
	*/
	public function actionMute(){
		$iLotId = bx_get('lot');

		if ($iLotId && $this -> _oDb -> isParticipant($iLotId, $this -> _iUserId)){
			$bMuted = $this -> _oDb -> muteLot($iLotId, $this -> _iUserId);
			return echoJson(array('code' => $bMuted, 'title' => $bMuted ? _t('_bx_messenger_lots_menu_mute_info_on') : _t('_bx_messenger_lots_menu_mute_info_off')));
		}
	}
	
	/**
	* Mark lot with star
	* @return string with json
	*/
	public function actionStar(){
		$iLotId = bx_get('lot');

		if ($iLotId && $this -> _oDb -> isParticipant($iLotId, $this -> _iUserId)){
			$bStar = $this -> _oDb -> starLot($iLotId, $this -> _iUserId);
			return echoJson(array('code' => $bStar, 'title' => !$bStar ? _t('_bx_messenger_lots_menu_star_on') : _t('_bx_messenger_lots_menu_star_off')));
		}
	}
	
	/**
	* Returns number of lots with at least one unread message
	* @return int
	*/   
	public function serviceGetUpdatedLotsNum($iProfileId = 0)
	{
        if (!$this -> isLogged() && !(int)$iProfileId)
            return 0;

        $aLots = $this -> _oDb -> getMyLots($iProfileId  ? (int)$iProfileId : $this -> _iUserId, BX_IM_EMPTY, BX_IM_EMPTY, true);
		return sizeof($aLots);
	}
   
	/**
	* Sends push notifications for participants of specified lot
	* @return boolean TRUE on success or FALSE on failure
	*/
	public function actionSendPushNotification(){
		$iLotId = (int)bx_get('lot');
		$aSent = is_array(bx_get('sent')) ? bx_get('sent') : array();

		if ($this -> _oDb -> isPushNotificationsEnabled())
		    return false;

		if (!$this -> isLogged() || !$this->_oConfig-> CNF['IS_PUSH_ENABLED'] || !$iLotId || !$this -> _oDb -> isParticipant($iLotId, $this -> _iUserId)) 
			return false;
		
		$bIsGlobalSettings = $this->_oConfig-> CNF['IS_PUSH_ENABLED'] && getParam('sys_push_app_id');
		
		$aLot = $this -> _oDb -> getLotInfoById($iLotId);
		if (empty($aLot)) return false;	   
		   
		$aParticipantList = $this -> _oDb -> getParticipantsList($aLot[$this -> _oConfig -> CNF['FIELD_ID']], true, $this -> _iUserId);
		if (empty($aParticipantList)) return false;
	   
		$oLanguage = BxDolStudioLanguagesUtils::getInstance();
		$sLanguage = $oLanguage->getCurrentLangName(false);

		$aLatestJot = $this -> _oDb -> getLatestJot($iLotId, $this -> _iUserId);
		$sMessage = $aLatestJot[$this -> _oConfig -> CNF['FIELD_MESSAGE']];
		if ($sMessage)
			$sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength(html_entity_decode($sMessage), (int)$this->_oConfig->CNF['PARAM_PUSH_NOTIFICATIONS_DEFAULT_SYMBOLS_NUM']);

		if (!$sMessage && $aLatestJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
			$sMessage = _t('_bx_messenger_attached_files_message', $this -> _oDb -> getJotFiles($aLatestJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']], true));

		$aContent = array(
			'en' => $sMessage,
		);
		
		if (!$aContent[$sLanguage]) 
			 $aContent[$sLanguage] = $sMessage;

		$oProfile = BxDolProfile::getInstance($this -> _iUserId);
		if($oProfile)
			$aHeadings = array(
				 $sLanguage => _t('_bx_messenger_push_message_title', $oProfile -> getDisplayName())
			);
		else
			return false;
	   	    
		
		$aWhere = array();
		$aInfo = array(
					'contents' => $aContent,
					'headings' => $aHeadings,
					'url' => $this->_oConfig->CNF['URL_REPOST'] . $aLatestJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']]
				);
				
		foreach($aParticipantList as $iKey => $iValue)
		{   
			if (array_search($iValue, $aSent) !== FALSE || $this -> _oDb -> isMuted($aLot[$this -> _oConfig -> CNF['FIELD_ID']], $iValue))
				continue;
			
			if ($bIsGlobalSettings)
			{
				BxDolPush::getInstance()->send($iValue, array_merge($aInfo, array('icon' => $oProfile->getThumb())), true);
			}
			else
			{
				$aWhere[] = array("field" => "tag", "key" => "user", "relation" => "=", "value" => $iValue);
				$aWhere[] = array("operator" => "OR");
			}
		}   
		
		
		if ($bIsGlobalSettings)
			return true;
		
		unset($aWhere[count($aWhere) - 1]);

		$aFields = array_merge(array(
			'app_id' => $this->_oConfig-> CNF['PUSH_APP_ID'],
			'filters' => $aWhere,			
			'chrome_web_icon' => $oProfile->getThumb()
		), $aInfo);
	   
	   	$aFields = json_encode($aFields);
	   
		$oCh = curl_init();
		curl_setopt($oCh, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($oCh, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
													'Authorization: Basic ' . $this->_oConfig->CNF['PUSH_REST_API']));
		curl_setopt($oCh, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($oCh, CURLOPT_HEADER, FALSE);
		curl_setopt($oCh, CURLOPT_POST, TRUE);
		curl_setopt($oCh, CURLOPT_POSTFIELDS, $aFields);
		curl_setopt($oCh, CURLOPT_SSL_VERIFYPEER, FALSE);

		$sResult = curl_exec($oCh);
		curl_close($oCh);
	   
		return $sResult;
	}   

	/**
	* Creates template with member's avatar, name and etc... It is used when member posts a message to add message to member history immediately
	* @return string json
	*/
	public function actionLoadMembersTemplate(){
		if (!$this -> isLogged()) return '';
		echoJson(array('data' => $this -> _oTemplate -> getMembersJotTemplate($this -> _iUserId)));
	}

	/**
	 * Delete all content by profile ID
	 * @param object oAlert
	 * @return boolean
	*/   
	public function serviceDeleteHistoryByAuthor($oAlert){
		return $oAlert -> iObject && $oAlert -> aExtras['delete_with_content'] ?
				$this -> _oDb -> deleteProfileInfo($oAlert -> iObject) : false;
	}

	/**
	 * Parse jot's link (repost)
	 * @param object oAlert
	 * @return string json
	*/
	public function actionParseLink(){
		$sUrl = bx_get('link');
		$iJotId = (int)bx_get('jot_id');
		$bDontAttach = (int)bx_get('dont_attach');
		
		$aUrl = $this -> _oConfig -> isJotLink($sUrl);
		if (!empty($aUrl))
		{
			$aJotInfo = $this -> _oDb -> getJotById($aUrl['id']);
			if (!$this -> _oDb -> isParticipant($aJotInfo[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $this -> _iUserId))
				return echoJson(array('code' => 0));
			
			$sHTML = $this -> _oTemplate -> getJotAsAttachment($aUrl['id']);
			if ($sHTML && !$bDontAttach)
				$this -> _oDb -> addAttachment($iJotId, $aUrl['id']);
			
			return echoJson(array('code' => 0, 'html' => $sHTML));
		}
		
		echoJson(array('code' => 1));
	}
	
	/**
	 * Parses link from the message
	 * @param object oAlert
	 * @return json
	*/
	public function actionGetAttachment(){
		$iJotId = (int)bx_get('jot_id');
		
		if ($iJotId)
		{
			$aJot = $this -> _oDb -> getJotById($iJotId);
			if ($this -> _oDb -> isParticipant($aJot[$this->_oConfig-> CNF['FIELD_MESSAGE_FK']], $this -> _iUserId)){
				$sHTML = $this -> _oTemplate -> getAttachment($aJot);
				if ($sHTML)
					return echoJson(array('code' => 0, 'html' => $sHTML));
			}
		}		
		
		echoJson(array('code' => 1));
	}
	
	public function actionGetUploadFilesForm(){
		header('Content-type: text/html; charset=utf-8');
		echo $this -> _oTemplate-> getFilesUploadingForm($this -> _iUserId);
		exit;		
	}
	
	public function actionGetRecordVideoForm(){
		header('Content-type: text/html; charset=utf-8');
		echo $this -> _oTemplate-> getVideoRecordingForm($this -> _iUserId);
		exit;
	}
	
	public function actionUploadTempFile(){		
		$oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
		if (!$oStorage){
			echo 0;
			exit;
		}
		
		if (!empty($_FILES) && $_FILES['file'] && $oStorage -> isValidFileExt($_FILES['file']['name'])){ 
			$sTempFile = $_FILES['file']['tmp_name'];
			$sTargetFile =  BX_DIRECTORY_PATH_TMP . $_FILES['file']['name']; 
			move_uploaded_file($sTempFile, $sTargetFile);
		}		
	}
	
	public function actionUploadVideoFile(){
		$oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
	
		if (!$oStorage || !isset($_POST['name']) || empty($_FILES)){
			return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_video')));
		}
	
		if (!empty($_FILES) && $_FILES['file'] && $oStorage -> isValidFileExt($_FILES['file']['name'])){ 
			$sTempFile = $_FILES['file']['tmp_name'];
			$sTargetFile =  BX_DIRECTORY_PATH_TMP . $_POST['name']; 
			
			if (move_uploaded_file($sTempFile, $sTargetFile))
				return echoJson(array('code' => 0));
		}

		echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_video')));
	}
	
	public function actionIsValidFile(){
		$oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
		if (!$oStorage)
			return echoJson(array('code' => 1));

		$sFileName = bx_get('name');
		if ($sFileName && (int)$oStorage -> isValidFileExt($sFileName)){
			$sIconFile = $oStorage -> getFontIconNameByFileName($sFileName);
			return echoJson(array('code' => 0, 'thumbnail' => $sIconFile ? $sIconFile : '', 'is_image' => (int)$oStorage -> isImageExt($sFileName)));
		}
					
		echoJson(array('code' => 1));
	}
	
		
	public function actionDownloadFile($iFileId){
		$aResult = array('code' => 1, 'message' => _t('_bx_messenger_post_file_not_found'));
		if (!$iFileId)
			return echoJson($aResult);
		 
		$aFile = BxDolStorage::getObjectInstance($this->_oConfig-> CNF['OBJECT_STORAGE'])->getFile((int)$iFileId);
		$sFileName = BX_DIRECTORY_STORAGE . $this->_oConfig-> CNF['OBJECT_STORAGE'] . '/' . $aFile['path'];
		if (!empty($aFile) && file_exists(BX_DIRECTORY_STORAGE . $this->_oConfig-> CNF['OBJECT_STORAGE'] . '/' . $aFile['path'])){
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download');
			header("Content-Disposition: attachment; filename=\"" . $aFile['file_name'] . "\";");
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . $aFile['size']);
			ob_clean();
			flush();
			readfile($sFileName);
			exit;
		}
		echoJson($aResult);
	}
	
	function actionRemoveTemporaryFile(){
		if (!bx_get('name')) return false;
		return @unlink(BX_DIRECTORY_PATH_TMP . bx_get('name'));
	}	
	
	/**
	* Returns big image for popup whem member click on small icon in talk history
	*@param int $iStorageId file id
	*@param int $iWidth widht of the window
	*@param int $iHeight height of the window
    *@return string html content
	*/
	function actionGetBigImage($iStorageId, $iWidth, $iHeight){
		if (!$iStorageId) return '';
		
		$iWidth = (int)$iWidth * 0.9;
		$iHeight = (int)$iHeight * 0.9;
		
		$aFile = BxDolStorage::getObjectInstance($this->_oConfig-> CNF['OBJECT_STORAGE'])->getFile((int)$iStorageId);
		$sImagePath = BX_DIRECTORY_STORAGE . $this->_oConfig-> CNF['OBJECT_STORAGE'] . '/' . $aFile['path'];
		
		if (!empty($aFile) && file_exists($sImagePath))
		{
			$aInfo = getimagesize($sImagePath);
			
			if ($aInfo[0] <= $iWidth && $aInfo[1] <= $iHeight){
				$iWidth = (int)$aInfo[0];
				$iHeight = (int)$aInfo[1];
			}
			else
			{
				$fImageRatio = (int)$aInfo[0]/(int)$aInfo[1];
				$fXRatio = $aInfo[0]/$iWidth;
				$fYRatio = $aInfo[1]/$iHeight;
		
				if ($fXRatio > $fYRatio)
					$iHeight = 'auto';
				else
					$iWidth = 'auto';
			}
		}

		echo $this -> _oTemplate -> parseHtmlByName('big_image.html', array(
																'height' => $iHeight,
																'width'	=> $iWidth,
																'url'	=> BxDolStorage::getObjectInstance($this->_oConfig-> CNF['OBJECT_STORAGE'])->getFileUrlById((int)$iStorageId)
															));
		exit;
	}
	
    public function serviceGetNotificationsData()
    {
        $sModule = $this->_aModule['name'];

        $aResult = array(
          'handlers' => array(
              array('group' => $sModule, 'type' => 'insert', 'alert_unit' => $sModule, 'alert_action' => 'got_jot_ntfs', 'module_name' => $sModule, 'module_method' => 'get_message_content', 'module_class' => 'Module'),
              array('group' => $sModule, 'type' => 'delete', 'alert_unit' => $sModule, 'alert_action' => 'delete_jot_ntfs')
          ),
          'settings' => array(
              array('group' => 'content', 'unit' => $sModule, 'action' => 'got_jot_ntfs', 'types' => array('personal'))
          ),
          'alerts'  => array(
              array('unit' => $sModule, 'action' => 'got_jot_ntfs'),
              array('unit' => $sModule, 'action' => 'delete_jot_ntfs')
          )
        );

        return $aResult;
    }

    /**
     * Jot info for Notifications module
     * @param array Input alert params
     * @return array with info for notifications
     */
    public function serviceGetMessageContent($aEvent)
    {
        $CNF = &$this->_oConfig->CNF;

        $aJotInfo = $this->_oDb->getJotById($aEvent['subobject_id']);
        $aLotInfo = $this->_oDb->getLotByJotId($aEvent['subobject_id'], false);
        if(empty($aJotInfo) || empty($aLotInfo))
            return array();

        $sEntryUrl = $CNF['URL_REPOST'] . $aJotInfo[$CNF['FIELD_MESSAGE_ID']];
        $iType = $aLotInfo[$CNF['FIELD_TYPE']];

        $sTitle = isset($aLotInfo[$CNF['FIELD_TITLE']]) && $aLotInfo[$CNF['FIELD_TITLE']] ?
                        $aLotInfo[$CNF['FIELD_TITLE']] : _t('_bx_messenger_lots_private_lot');

        if ($this -> _oDb -> isLinkedTitle($iType)) {
            $sTitle = $this->_oDb->isLinkedTitle($iType) ? _t('_bx_messenger_linked_title', $sTitle) : _t($sTitle);
            $sEntryUrl = $this->_oConfig->getPageLink($aLotInfo[$CNF['FIELD_URL']]);
        }

        $sMessage = $aJotInfo[$CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES ?
                    _t('_bx_messenger_txt_sample_comment_file_single', $this -> _oDb -> getJotFiles($aJotInfo[$CNF['FIELD_MESSAGE_ID']], true))  :
                    _t('_bx_messenger_txt_sample_comment_single', strmaxtextlen($aJotInfo[$CNF['FIELD_MESSAGE']], $CNF['PARAM_MAX_JOT_NTFS_MESSAGE_LENGTH']));

       return array(
            'entry_sample' => _t('_bx_messenger_message'),
            'entry_url' => $sEntryUrl,
            'entry_caption' => $sTitle,
            'entry_author' => $aEvent['object_owner_id'],
            'subentry_sample' => $sMessage,
            'lang_key'=> '_bx_messenger_txt_subobject_added'
        );
    }

    /**
     * Check if the user can read Lot messages
     * @param array $aLot contains Lot details
     * @param boolean $isPerformAction used for compatibility with parent method
     * @return int
     */
    public function checkAllowedView($aLot, $isPerformAction = false)
    {
        $CNF = &$this->_oConfig->CNF;
        if (empty($aEvent))
           return CHECK_ACTION_RESULT_ALLOWED;

        return $this->_oDb->isParticipant($aEvent[$CNF['FIELD_ID']], $this -> _iUserId, true) === TRUE ? CHECK_ACTION_RESULT_ALLOWED : CHECK_ACTION_RESULT_NOT_ALLOWED;
    }

    public function onSendJot($iJotId)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'send_jot', $iJotId, $this -> _iUserId);

        $iLotId = $this->_oDb->getLotByJotId($iJotId);
        if (!$iLotId)
            return false;

        $aPartList = $this -> _oDb -> getParticipantsList($iLotId, true, $this -> _iUserId);
        if (empty($aPartList))
            return false;

        foreach($aPartList as $iKey => $iPart) {
            if ($this -> _oDb -> isAllowedToSendNtfs($this->_iUserId, $iLotId))
                bx_alert($this->_oConfig->getObject('alert'), 'got_jot_ntfs', $iLotId, $this->_iUserId, array('object_author_id' => $iPart, 'recipient_id' => $iPart, 'subobject_id' => $iJotId));

            bx_alert($this->_oConfig->getObject('alert'), 'got_jot', $iLotId, $this->_iUserId, array('recipient_id' => $iPart, 'subobject_id' => $iJotId));
        }

    }

    public function onDeleteJot($iLotId, $iJotId, $iProfileId = 0)
    {
        $iProfileId = $iProfileId ? $iProfileId : $this -> _iUserId;
        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot', $iJotId, $this -> _iUserId, array('author_id' => $iProfileId, 'lot_id' => $iLotId));
        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot_ntfs', $iLotId, $iProfileId, array('subobject_id' => $iJotId));
    }

    public function onUpdateJot($iLotId, $iJotId, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'update_jot', $iJotId, $this -> _iUserId, array('author_id' => $iProfileId, 'lot_id' => $iLotId));
    }

    public function onCreateLot($iLotId)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'create_lot', $iLotId, $this -> _iUserId);
    }

    public function onDeleteLot($iLotId, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'delete_lot', $iLotId, $this -> _iUserId, array('author_id' => $iProfileId));
    }

    public function onAddNewParticipant($iLotId, $iParticipant, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'add_part', $iParticipant, $this -> _iUserId, array('lot_id' => $iLotId, 'author_id' => $iProfileId));
    }

    public function onRemoveParticipant($iLotId, $iParticipant, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'remove_part', $iParticipant, $this -> _iUserId, array('lot_id' => $iLotId, 'author_id' => $iProfileId));
    }

    public function onCheckContact($iSender, $iRecipient){
        $bCanContact = true;
        bx_alert($this->_oConfig->getObject('alert'), 'check_contact', 0, false, array('can_contact' => &$bCanContact, 'sender' => $iSender, 'recipient' => $iRecipient, 'where' => $this->_oConfig->getName()));
        return $bCanContact;
    }


    public function serviceIsContactAllowed($mixedObject){
        if (!$this -> _iUserId)
            return false;
        return $this -> onCheckContact($this -> _iUserId, (int)$mixedObject);
    }

	public function serviceGetLiveUpdates($aMenuItemParent, $aMenuItemChild, $iCount = 0)
    {
		$aLots = $this -> _oDb -> getMyLots($this -> _iUserId, BX_IM_EMPTY, BX_IM_EMPTY, true);
		$iCountNew = sizeof($aLots);
        if($iCountNew == $iCount)
			return false;

        return array(
    		'count' => $iCountNew,
    		'method' => 'bx_menu_show_live_update(oData)',
    		'data' => array(
    			'code' => BxDolTemplate::getInstance()->parseHtmlByTemplateName('menu_item_addon', array(
    				'content' => '{count}'
                )),
                'mi_parent' => $aMenuItemParent,
                'mi_child' => $aMenuItemChild
    		),  
    	);
    }

    /**
     * @param int $iObjectId item's id
     * @param strign $sType name of te module
     * @param int $iProfileId comments' author, if this param is empty, then get all comments
     * @param int $iStart get comments from position
     * @param int $iPerPage number of the comments to get at once
     * @return array comments list
     */
    public function serviceGetLiveComments($iObjectId, $sType, $iProfileId = 0, $iStart = 0, $iPerPage = 0){
        $aComments = $this -> _oDb -> getLiveComments($iObjectId, $sType, $iProfileId, $iStart, $iPerPage);
        if (!(int)$aComments['total'])
            return array();

        $aResult = array();
        foreach($aComments['result'] as $iKey => $aValue) {
            $oProfile = $this -> _oTemplate -> getObjectUser($aValue[$this->_oConfig->CNF['FIELD_LCMTS_AUTHOR']]);
            $aResult[$aValue[$this->_oConfig->CNF['FIELD_LCMTS_ID']]] = array(
                'text' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_TEXT']],
                'author' => array(
                    'id' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_AUTHOR']],
                    'name' => $oProfile -> getDisplayName(),
                    'icon' => $oProfile -> getAvatar(),
                    'url' => $oProfile -> getUrl()
                ),
                'id' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_ID']],
                'date' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_DATE']],
            );
        }

        return array('items' => $aResult, 'id' => $iObjectId, 'type' => $sType, 'total' => (int)$aComments['total']);
    }

    /**
     * @param string $sText text of the comment
     * @param int $iObjectId item's id
     * @param string $sType $sType name of te module
     * @param int $iProfileId comment's author
     * @param int $iDate client's time when message is sent
     * @return int added object's id
     */
    public function servicePostLiveComments($sText, $iObjectId, $sType, $iProfileId, $iDate = 0){
        if (!$sText || !$iObjectId || !$sType || !$iProfileId)
            return 0;

        $aResult = array('code' => 1);
        if ($iId = $this -> _oDb -> addLiveComment($sText, $iObjectId, $sType, $iProfileId, $iDate))
            $aResult = array('code' => 0, 'id' => $iId, 'date' => $iDate ? $iDate : time());

        return $aResult;
    }

    public function serviceRemoveLiveComment($iObjectId, $iProfileId){
        if (!$iObjectId || !$iProfileId)
            return false;

        $aResult = array('code' => 0, 'id' => $iObjectId);
        return $this -> _oDb -> removeLiveComment($iObjectId, $iProfileId) ? $aResult : array('code' => 1);
    }

    public function serviceGetLotsList($iProfileId){
        if (!$iProfileId)
            return '';

        $aLots = $this -> _oDb -> getMyLots($iProfileId);
        if (empty($aLots))
            return array();

        $aLotsList = array();
        foreach($aLots as $iKey => $aLot)
        {
            $aParticipantsList = $this -> _oDb -> getParticipantsList($aLot[$this -> _oConfig -> CNF['FIELD_ID']], true, $iProfileId);

            $iParticipantsCount = count($aParticipantsList);
            $aParticipantsList = $iParticipantsCount ? array_slice($aParticipantsList, 0, $this -> _oConfig -> CNF['PARAM_ICONS_NUMBER']) : array($iProfileId);

            $aNickNames = array();
            foreach($aParticipantsList as $iParticipant){
                $oProfile = $this -> _oTemplate -> getObjectUser($iParticipant);
                if ($oProfile) {
                    $aNickNames[] = array(
                        'name' => $oProfile->getDisplayName(),
                        'url' => $oProfile->getUrl(),
                        'thumb' => $oProfile->getThumb()
                    );
                }
            }

            if (!empty($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]))
                $sTitle = _t($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]);
            else
            {
                if ($iParticipantsCount > 3)
                    $sTitle = implode(', ', array_slice($aNickNames, 0, $this -> _oConfig -> CNF['PARAM_ICONS_NUMBER'])) . '...';
                else {

                    $aTitle = array();

                    foreach($aNickNames as $iKey => $aValue)
                        $aTitle[] = $aValue['name'];

                    $sTitle = implode(', ', $aTitle);
                }
            }

            $sStatus = '';
            if ($iParticipantsCount == 1 && $oProfile && empty($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]))
                $sStatus = (int)(method_exists($oProfile, 'isOnline') ? $oProfile -> isOnline() : false);
            else
                $sStatus = $iParticipantsCount;

            $aLatestJots = $this -> _oDb -> getLatestJot($aLot[$this -> _oConfig -> CNF['FIELD_ID']]);
            $iTime = $aLot[$this -> _oConfig -> CNF['FIELD_ADDED']];

            $oSender = $sSender = $sMessage = '';
            if (!empty($aLatestJots))
            {
                if (isset($aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE']]))
                {
                    $sMessage = $aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE']];
                    if ($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_REPOST)
                    {
                        $sMessage = $this -> _oConfig -> cleanRepostLinks($sMessage, $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT']]);
                        $sMessage = $sMessage ? $sMessage : _t('_bx_messenger_repost_message');
                    }

                    $sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sMessage, $this->_oConfig-> CNF['MAX_PREV_JOTS_SYMBOLS']);
                }

                if (!$sMessage && $aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
                    $sMessage = _t('_bx_messenger_attached_files_message', $this -> _oDb -> getJotFiles($aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']], true));

                $sSender = '';
                if ($oSender = $this -> _oTemplate -> getObjectUser($aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE_AUTHOR']]))
                    $sSender = $oSender -> id() == $iProfileId ? _t('_bx_messenger_you_username_title') : $oSender -> getDisplayName();

                $iTime = $aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE_ADDED']];
            }

            $aLotsList[] = array(
                'participants_count' => $iParticipantsCount,
                'participants_list' => $aNickNames,
                'title' => $sTitle,
                'lot_id' => $aLot[$this -> _oConfig -> CNF['FIELD_ID']],
                'status' => $sStatus,
                'latest_jot_message' => $sMessage,
                'latest_jot_message_author' => $sSender,
                'latest_jot_message_date' => $iTime,
                'latest_jot_message_author_thumb' => $oSender ? $oSender -> getThumb() : '',
            );
        }

        return $aLotsList;
    }

    public function servicePerformConnAction($iInitiator, $iContent, $sAction = 'add', $sConnectionObject = 'sys_profiles_subscriptions'){
        require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

        if (!$iInitiator || !$iContent)
            return array();

        $oConnections = BxDolConnection::getObjectInstance($sConnectionObject);
        $sMethod = "{$sAction}Connection";
        if (empty($oConnections) || !method_exists($oConnections, $sMethod))
            return array();

        return array(
                       'result' => (int)$oConnections -> $sMethod($iInitiator, $iContent),
                       'content_id' => (int)$iContent,
                       'action' => $sAction
                     );
    }

    public function serviceProfileConnections($sType = 'friends', $iContentId = 0, $iStart = 0, $iPerPage = 0, $iMutual = false){
        $iProfileId = $iContentId ? $iContentId : $this -> _iUserId;

        if (!$iProfileId)
            return array();

        $aMembers = array();
        $oFriends = BxDolConnection::getObjectInstance('sys_profiles_friends');
        $oFollowers = BxDolConnection::getObjectInstance('sys_profiles_subscriptions');

        switch($sType){
            case 'friends':
                if (!$oFriends)
                    return array();

                $aFriends = $oFriends->getConnectedContent($iProfileId, true, $iStart, $iPerPage);
                if (!empty($aFriends))
                    $aMembers = array_combine($aFriends, $aFriends);

                break;
            case 'connections':
                if (!$oFollowers)
                    return array();

                $aFollowers = $oFollowers->getConnectedContent($iProfileId, false, $iStart, $iPerPage);
                if (!empty($aFollowers))
                    $aMembers = array_combine($aFollowers, $aFollowers);

                break;
            case 'recent':
            case 'active':
            case 'online':
            case 'top':
                if (BxDolRequest::serviceExists('bx_persons', 'get_members')) {
                    $aPersons = BxDolService::call('bx_persons', 'get_members', array($sType, $iStart, $iPerPage));
                    foreach($aPersons as &$aPerson)
                        $aMembers[$aPerson['author']] = $aPerson;
                }
                break;
        }

        if (empty($aMembers))
            return $aMembers;

        $aRet = array();
        foreach ($aMembers as $iId => $aInfo) {
            if (($oProfile = BxDolProfile::getInstance($iId))) {
                $aRet[] = array(
                    'name' => $oProfile->getDisplayName(),
                    'id' => $iId,
                    'url' => $oProfile->getUrl(),
                    'thumb' => $oProfile->getThumb(),
                    'picture' => $oProfile->getPicture(),
                    'avatar' => $oProfile->getAvatar(),
                    'is_online' => $oProfile->isOnline(),
                    'status' => $oProfile->getStatus(),
                    'followers' => (int)$oFollowers->getConnectedInitiatorsCount($iId),
                    'friends' => (int)$oFriends->getConnectedContentCount($iId, true),
                    'is_friend' => (int)$oFriends->isConnected($iProfileId, $iId, true),
                    'is_follow' => (int)$oFollowers->isConnected($iProfileId, $iId)
                );
            }
        }

        return $aRet;
    }
}

/** @} */
