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
define('BX_ATT_TYPE_GIPHY', 'giphy');
define('BX_ATT_TYPE_REPOST', 'repost');

define('BX_JOT_REACTION_ADD', 'add');
define('BX_JOT_REACTION_REMOVE', 'remove');

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
        $this->_iUserId = bx_get_logged_profile_id();
    }

    /**
     * Returns left side block for messenger page and loads config data
     */
    public function serviceGetBlockInbox()
    {
        if (!$this->isLogged())
            return '';

        $iProfile = bx_get('profile_id');
        $iProfile = $iProfile == $this->_iUserId ? 0 : $iProfile;

        $iLotId = 0;
		if ($this -> _iJotId)
		{
            $iLotId = $this->_oDb->getLotByJotId($this->_iJotId);
			if (!empty($iLotId) && !$this ->_oDb -> isParticipant($iLotId, $this -> _iUserId))
			{
                $this->_iJotId = BX_IM_EMPTY;
                $iLotId = BX_IM_EMPTY;
            }
        }

		$sConfig = $this -> _oTemplate -> loadConfig($this -> _iUserId);
		return	$sConfig . $this -> _oTemplate -> getLotsColumn($iLotId, $this -> _iJotId, $this -> _iUserId, (int)$iProfile);
    }
    /**
     * Returns right side block for messenger page
     */
    public function serviceGetBlockLot()
    {
        if (!$this->isLogged())
            return '';

        $iProfile = bx_get('profile_id');
        $iProfile = $iProfile == $this->_iUserId ? 0 : $iProfile;
        return $this->_oTemplate->getLotWindow($iProfile, BX_IM_EMPTY, true, $this->_iJotId);
    }

    /**
     * Returns block with messenger for any page
     * @param string $sModule module name
     * @return string block's content
     */
    public function serviceGetBlockMessenger($sModule)
    {
        $this->_oTemplate->loadCssJs('view');
        $aLotInfo = $this->_oDb->getLotByClass($sModule);
        if (empty($aLotInfo) && $sModule) {
          $sUrl = $this->_oConfig->getPageIdent();
          $aLotInfo = $this->_oDb->getLotByUrl($sUrl);
        }

        $sConfig = $this->_oTemplate->loadConfig($this->_iUserId);
        $aBlock = $this->_oTemplate->getTalkBlock($this->_iUserId, !empty($aLotInfo) ?
            (int)$aLotInfo[$this->_oConfig->CNF['FIELD_ID']] : BX_IM_EMPTY,
            BX_IM_EMPTY, $this->_oConfig->getTalkType($sModule), true /* create messenger window even if chat doesn't exist yet */);

        return $sConfig . $aBlock['content'];
    }

    /**
     * Adds messenger block to all pages with comments and trigger pages during installation
     */
    public function serviceAddMessengerBlocks()
    {
        if (!isAdmin()) return '';

        $aPages = $this->_oDb->getPagesWithComments();

        $aUrl = array();
        foreach ($aPages as $sModule => $sPage) {
            $sParams = parse_url($sPage, PHP_URL_QUERY);
            if (!empty($sParams))
                parse_str($sParams, $aUrl);

            if (isset($aUrl['i'])) {
                $sPage = BxDolPageQuery::getPageObjectNameByURI($aUrl['i']);
                if (!$this->_oDb->isBlockAdded($sPage))
                    $this->_oDb->addMessengerBlock($sPage);
            }
        }
    }

    /**
     * Builds main messenger page
     */
    public function actionHome()
    {
        if (!$this->isLogged())
            bx_login_form();

        $oPage = BxDolPage::getObjectInstance('bx_messenger_main');

        if (!$oPage) {
            $this->_oTemplate->displayPageNotFound();
            exit;
        }

        $s = $oPage->getCode();

        $this->_oTemplate = BxDolTemplate::getInstance();
        $this->_oTemplate->setPageNameIndex(BX_PAGE_DEFAULT);
        $this->_oTemplate->setPageContent('page_main_code', $s);
        $this->_oTemplate->getPageCode();
    }

    public function actionArchive($iJotId)
    {
        $this->_iJotId = $iJotId;
        $this->actionHome();
    }

    /**
     * Create List of participants received from request (POST, GET)
     * @param mixed $mixedPartisipants participants list
     * @return array  participants list
     */
    private function getParticipantsList($mixedPartisipants)
    {
        if (empty($mixedPartisipants))
            return array();
        $aParticipants = is_array($mixedPartisipants) ? $mixedPartisipants : array(intval($mixedPartisipants));
        $aParticipants[] = $this->_iUserId;
        return array_unique($aParticipants, SORT_NUMERIC);
    }

    /**
     * Send function occurs when member posts a message
     * @return array json result
     */
    public function actionSend()
    {
        $sUrl = $sTitle = '';
        $sMessage = trim(bx_get('message'));
        $iLotId = (int)bx_get('lot');
        $iType = bx_get('type');
        $iTmpId = bx_get('tmp_id');
        $aFiles = bx_get(BX_ATT_TYPE_FILES);
        $aGiphy = bx_get(BX_ATT_TYPE_GIPHY);
        $CNF = &$this->_oConfig->CNF;

        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_only_for_logged')));

        if (!$sMessage && empty($aFiles) && empty($aGiphy))
            return echoJson(array('code' => 2, 'message' => _t('_bx_messenger_send_message_no_data')));

        $iType = $this->_oDb->isLotType($iType) ? $iType : BX_IM_TYPE_PUBLIC;
        if ($iType != BX_IM_TYPE_PRIVATE) {
            $sUrl = bx_get('url');
            $sTitle = bx_get('title');
        }

        // prepare participants list
        $aParticipants = $this->getParticipantsList(bx_get('participants'));
        if (!$iLotId && empty($aParticipants) && $iType == BX_IM_TYPE_PRIVATE)
            return echoJson(array('code' => 2, 'message' => _t('_bx_messenger_send_message_no_data')));

		if ($sMessage)
		{
            $sMessage = $this -> prepareMessageToDb($sMessage);
            if ($iType != BX_IM_TYPE_PRIVATE && $sUrl)
                $sUrl = $this->getPreparedUrl($sUrl);
        }

        $aResult = array('code' => 0, 'tmp_id' => $iTmpId);
        if (($sMessage || !empty($aFiles) || !empty($aGiphy)) && ($iId = $this->_oDb->saveMessage(
            array(
                    'message' => $sMessage,
                    'type' => $iType,
                    'member_id' => $this->_iUserId,
                    'url' => $sUrl,
                    'title' => $sTitle,
                    'lot' => $iLotId
            ), $aParticipants)))
        {
            if (!$iLotId)
                $aResult['lot_id'] = $this->_oDb->getLotByJotId($iId);

			if (!empty($aFiles))
			{
                $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
                $aFilesNames = array();
				foreach($aFiles as $iKey => $sName)
				{
                    $iFile = $oStorage->storeFileFromPath(BX_DIRECTORY_PATH_TMP . $sName, $iType == BX_IM_TYPE_PRIVATE, $this->_iUserId, (int)$iId);
					if ($iFile)
					{
                        $oStorage->afterUploadCleanup($iFile, $this->_iUserId);
                        $this->_oDb->updateFiles($iFile, $CNF['FIELD_ST_JOT'], $iId);
                        $aFilesNames[] = $sName;
					}
					else 
                        $aResult = array('code' => 2, 'message' => $oStorage->getErrorString());
                }

                if (!empty($aFilesNames))
                    $this->_oDb->addAttachment($iId, implode(',', $aFilesNames), BX_ATT_TYPE_FILES);
            }

			if (is_array($aGiphy) && !empty($aGiphy))
               $this->_oDb->addAttachment($iId, current($aGiphy), BX_ATT_TYPE_GIPHY);

            $aResult['jot_id'] = $iId;
			$aJot = $this->_oDb->getJotById($iId);
			if (!empty($aJot))
                $aResult['time'] = bx_time_utc($aJot[$CNF['FIELD_MESSAGE_ADDED']]);

            $this->onSendJot($iId);

        }
		else
            $aResult = array('code' => 2, 'message' => _t('_bx_messenger_send_message_save_error'));

        BxDolSession::getInstance()->exists($this->_iUserId);
        echoJson($aResult);
    }

    /**
     * Loads talk to the right side block when member choose conversation or when open messenger page
     * @return array with json result
     */
	public function actionLoadTalk(){
        $iId = (int)bx_get('lot_id');
        $iJotId = (int)bx_get('jot_id');

        if (!$this->isLogged() || !$iId || !$this->_oDb->isParticipant($iId, $this->_iUserId)) {
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
        };

        if ((int)bx_get('mark_as_read'))
            $this->_oDb->readAllMessages($iId, $this->_iUserId);

        $sTitle = '';
        $aBlock = $this->_oTemplate->getTalkBlock($this->_iUserId, $iId, $iJotId, BX_IM_TYPE_PUBLIC, false, $sTitle);
        echoJson(array('code' => 0, 'html' => $aBlock['content'], 'title' => $aBlock['title']));
    }

    public function actionMarkJotsAsRead(){
        $iId = (int)bx_get('lot');
        if (!$this->isLogged() || !$this->_oDb->isParticipant($iId, $this->_iUserId)) {
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));
        };

        $this->_oDb->readAllMessages($iId, $this->_iUserId);
        echoJson(array('code' => 0));
    }

    /**
     * Loads messages for specified lot(conversation)
     * @return array with json
     */
	public function actionLoadJots(){	   
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

        $iId = (int)bx_get('id');

        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

        $sContent = $this->_oTemplate->getPostBoxWithHistory($this->_iUserId, (int)$iId > 0 ? $iId : BX_IM_EMPTY, BX_IM_TYPE_PRIVATE);

        echoJson(array('code' => 0, 'html' => $sContent));
    }

    /**
     * Search for Lots by keywords in the right side block
     * @return string with json
     */
	public function actionSearch(){	   
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

        $sParam = bx_get('param');
        $iType = bx_get('type');
        $iStarred = bx_get('starred');

        $aMyLots = $this->_oDb->getMyLots($this->_iUserId, $iType, $sParam, BX_IM_EMPTY, BX_IM_EMPTY, $iStarred);
        if (empty($aMyLots))
            $sContent = MsgBox(_t('_bx_messenger_txt_msg_no_results'));
        else
            $sContent = $this->_oTemplate->getLotsPreview($this->_iUserId, $aMyLots);

        echoJson(array('code' => 0, 'html' => $sContent));
    }

    /**
     * Update brief of the specified lot in the lots list
     * @return string with json
     */
	public function actionUpdateLotBrief(){
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

        $iLotId = (int)bx_get('lot_id');
        if (!$this->isLogged() || !$iLotId)
            return echoJson(array('code' => 1));

        $aMyLots = $this->_oDb->getMyLots($this->_iUserId, BX_IM_EMPTY, BX_IM_EMPTY, BX_IM_EMPTY, $iLotId);
		if (!empty($aMyLots))
		{
            $sContent = $this->_oTemplate->getLotsPreview($this->_iUserId, $aMyLots);
            return echoJson(array('code' => 0, 'html' => $sContent));
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
        if (!$sUrl)
            return false;

        $aUrl = parse_url($sUrl);

        return strtolower($aUrl['path'] . (isset($aUrl['query']) ? '?' . $aUrl['query'] : ''));
    }

    /**
     * Loads messages for  lot(conversation) (when member wants to view history or get new messages from participants)
     * @return string with json
     */
	public function actionUpdate(){	   
        $CNF = &$this->_oConfig->CNF;
        $sUrl = bx_get('url');
        $iJot = (int)bx_get('jot');
        $iLotId = (int)bx_get('lot');
        $sLoad = bx_get('load');
        $bRead = filter_var(bx_get('read'), FILTER_VALIDATE_BOOLEAN);

		if ($sLoad == 'new' && !(int)$iJot)
		{
            $aMyLatestJot = $this->_oDb->getLatestJot($iLotId, $this->_iUserId);
            if (empty($aMyLatestJot))
                return echoJson(array('code' => 1));
            else
                $iJot = (int)$aMyLatestJot[$CNF['FIELD_MESSAGE_ID']];
        }

        $sUrl = $sUrl ? $this->getPreparedUrl($sUrl) : '';
        $sContent = '';
		switch($sLoad)
		{
            case 'new':
            case 'prev':
                $aOptions = array(
                    'lot_id' => $iLotId,
                    'url' => $sUrl,
                    'start' => $iJot,
                    'load' => $sLoad,
                    'limit' => ($sLoad != 'new' ? $CNF['MAX_JOTS_LOAD_HISTORY'] : 0),
                    'read' => $bRead,
                    'views' => true,
                    'dynamic' => true
                );
                $sContent = $this->_oTemplate->getJotsOfLot($this->_iUserId, $aOptions);
                break;
            case 'edit':
                $aJotInfo = $this->_oDb->getJotById($iJot);
                $sContent = $aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE']];
            case 'delete':
                $sContent .= $this->_oTemplate->getMessageIcons($iJot, $sLoad, $this->_oDb->isAuthor($iLotId, $this->_iUserId) || isAdmin());
                break;
            case 'check_viewed':
                if ($iLotId && $iJot)
                      $sContent = $this->_oTemplate->getViewedJotProfiles($iJot, $this->_iUserId);
                break;
            case 'reaction':
                if ($iJot) {
                    $aJotInfo = $this->_oDb->getJotById($iJot);
                    if (!empty($aJotInfo))
                        $sContent = $this->_oTemplate->getJotReactions($iJot);
                }
            break;
        }

        $aResult = array('code' => 0, 'html' => $sContent);

        // update session
        if ($this->_iUserId)
            BxDolSession::getInstance()->exists($this->_iUserId);

        echoJson($aResult);
    }

    /**
     * Occurs when member wants to create new conversation(lot)
     * @return array with json
     */
	public function actionCreateLot(){
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

        $iProfileId = (int)bx_get('profile');
        $iLot = (int)bx_get('lot');
        echoJson(array('title' => _t('_bx_messenger_lots_menu_create_lot_title'), 'html' => $this->_oTemplate->getLotWindow($iProfileId, $iLot, false)));
    }

    public function searchProfiles($sTerm, $aExcept = array(), $iLimit = 10){
        $aResult = array();
        $aModules = BxDolService::call('system', 'get_profiles_modules', array(), 'TemplServiceProfiles');
        if (empty($aModules))
            return $aResult;

        // search in each module
        $a = array();
        foreach ($aModules as $aModule) {
            if (!BxDolService::call($aModule['name'], 'act_as_profile'))
                continue;
            $a = array_merge($a, BxDolService::call($aModule['name'], 'profiles_search', array('%' . $sTerm, $iLimit + count($aExcept))));
        }

        // sort result
        usort($a, function($r1, $r2) {
            return strcmp($r1['label'], $r2['label']);
        });

        if (!empty($aExcept))
            $a = array_filter($a, function($a) use ($aExcept) {
               return !in_array($a['value'], $aExcept);
            });

        return array_slice($a, 0, $iLimit);
    }
    /**
     * Occurs when member adds or edit participants list for new of specified lot
     * @return string with json code
     */
	public function actionGetAutoComplete(){
        $aResult = $aExcept = array();
        $sExcept = bx_get('except');
	    if ($sExcept)
            $aExcept = explode(',', $sExcept);

	    $aUsers = $this->searchProfiles(bx_get('term'), $aExcept,  $this->_oConfig->CNF['PARAM_SEARCH_DEFAULT_USERS']);
        if (empty($aUsers))
            return echoJson(array('items' => $aUsers));

        foreach ($aUsers as $iKey => $aValue) {
            if ($aValue['value'] == $this->_iUserId  || !$this->onCheckContact($this->_iUserId, $aValue['value'])) continue;

            $oProfile = BxDolProfile::getInstance($aValue['value']);
            $aProfileInfo = $oProfile->getInfo();
            $aProfileInfoDetails = BxDolService::call($aProfileInfo['type'], 'get_content_info_by_id', array($aProfileInfo['content_id']));
            $oAccountInfo = BxDolAccount::getInstance($aProfileInfo['account_id']);
            if ($oProfile && !empty($aProfileInfoDetails) && !empty($oAccountInfo))
                $aResult[] = array(
                    'value' => $oProfile->getDisplayName(),
                    'icon' => $oProfile->getThumb(),
                    'id' => $oProfile->id(),
                    'description' => _t('_bx_messenger_search_desc',
                        bx_process_output($oAccountInfo->getInfo()['logged'], BX_DATA_DATE_TS),
                        bx_process_output($aProfileInfoDetails['added'], BX_DATA_DATE_TS))
                );
        }

        echoJson(array('items' => $aResult));
    }

    /**
     * Returns processed videos by received videos ids
     * @return array with json
     */
	public function actionGetProcessedMedia(){
        $CNF = &$this->_oConfig->CNF;
        $aMedia = bx_get('media');
        $aResult = array();

        if (empty($aMedia))
            return echoJson($aResult);

        $oTranscoderAudio = BxDolTranscoderAudio::getObjectInstance($CNF['OBJECT_MP3_TRANSCODER']);
        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        if (empty($oStorage))
            return echoJson($aResult);

		foreach($aMedia as &$iMedia)
		{
            $aFile = $oStorage->getFile($iMedia);
            if ($oTranscoderAudio->isMimeTypeSupported($aFile['mime_type']) && $oTranscoderAudio->isFileReady($iMedia))
                $aResult[$iMedia] = $this->_oTemplate->audioPlayer($oTranscoderAudio->getFileUrl($aFile[$CNF['FIELD_ST_ID']]), true);

            if (!strncmp('video/', $aFile['mime_type'], 6))
                $aResult[$iMedia] = $this->_oTemplate->getVideoFilesToPlay($aFile);
        }

        echoJson($aResult);
    }

    /**
     * Search for lot by participants list and occurs when member edit participants list
     * @return string with json
     */
    public function actionFindLot()
    {
        $aParticipants = $this->getParticipantsList(bx_get('participants'));

        $aResult = array('lotId' => 0);
        if (!empty($aParticipants) && ($aChat = $this->_oDb->getLotByUrlAndPariticipantsList(BX_IM_EMPTY_URL, $aParticipants, BX_IM_TYPE_PRIVATE)))
            $aResult['lotId'] = $aChat[$this->_oConfig->CNF['FIELD_ID']];

        echoJson($aResult);
    }

    /**
     * Updats participants list (occurs when create new lost with specified participants or update already existed list)
     * @return string with json
     */
    public function actionSaveLotsParts()
    {
        $iLotId = bx_get('lot');
        $aParticipants = $this->getParticipantsList(bx_get('participants'));

        $aResult = array('message' => _t('_bx_messenger_save_part_failed'), 'code' => 1);
        if (($iLotId && !($this->_oDb->isAuthor($iLotId, $this->_iUserId) || isAdmin())) || (empty($aParticipants) && !$iLotId)) {
            return echoJson($aResult);
        }

        $aLot = $this->_oDb->getLotByUrlAndPariticipantsList(BX_IM_EMPTY_URL, $aParticipants, BX_IM_TYPE_PRIVATE);
        if (!empty($aLot))
            $iLotId = $aLot[$this->_oConfig->CNF['FIELD_ID']];

        $oOriginalParts = $this->_oDb->getParticipantsList($iLotId);
        $aNewParticipants = $aParticipants;
        $aRemoveParticipants = array();

        $aResult = array('message' => _t('_bx_messenger_save_part_success'), 'code' => 0);
		if (!$iLotId)
		{
            $iLotId = $this->_oDb->createNewLot($this->_iUserId, BX_IM_EMPTY_URL, BX_IM_TYPE_PRIVATE, BX_IM_EMPTY_URL, $aParticipants);
            $aResult['lot'] = $iLotId;
            $this->onCreateLot($iLotId);
		}
		else {
            if (!$this->_oDb->savePariticipantsList($iLotId, $aParticipants))
                $aResult = array('code' => 2, 'lot' => $iLotId);

            $aRemoveParticipants = array_diff($oOriginalParts, $aParticipants);
            $aNewParticipants = array_diff($aParticipants, $oOriginalParts);
        }

        foreach ($aNewParticipants as &$iPartId)
            $this->onAddNewParticipant($iLotId, $iPartId);

        foreach ($aRemoveParticipants as &$iPartId)
            $this->onRemoveParticipant($iLotId, $iPartId);

        echoJson($aResult);
    }

    /**
     * Removes specified lot
     * @return string with json
     */
    public function actionDelete()
    {
        $iLotId = bx_get('lot');
        $aResult = array('message' => _t('_bx_messenger_can_not_delete'), 'code' => 1);

        if (!$iLotId || !($this->_oDb->isAuthor($iLotId, $this->_iUserId) || isAdmin())) {
            return echoJson($aResult);
        }

        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        $aJotsList = $this->_oDb->getJotsByLotId($iLotId);
        $CNF = &$this->_oConfig->CNF;

        if ($this->_oDb->deleteLot($iLotId)) {
            $aResult = array('code' => 0);
            $this->onDeleteLot($iLotId, $aLotInfo[$CNF['FIELD_AUTHOR']]);

            foreach ($aJotsList as &$aJot)
                $this->onDeleteJot($aJot[$CNF['FIELD_MESSAGE_FK']], $aJot[$CNF['FIELD_MESSAGE_ID']], $aJot[$CNF['FIELD_MESSAGE_AUTHOR']]);
        }

        echoJson($aResult);
    }

    /**
     * Removes specified jot
     * @return string with json
     */
    public function actionDeleteJot()
    {
        $iJotId = bx_get('jot');
        $bCompletely = (int)bx_get('completely');
        $aJotInfo = $this->_oDb->getJotById($iJotId);

        $aResult = array('code' => 1);
        $CNF = &$this->_oConfig->CNF;

        if (empty($aJotInfo))
            return echoJson($aResult);

        $bIsLotAuthor = $this->_oDb->isAuthor($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iUserId);
        $bIsAllowedToDelete = $this->_oDb->isAllowedToDeleteJot($iJotId, $this->_iUserId, $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']], $bIsLotAuthor);
        if (!(isAdmin() || $bIsLotAuthor || ((!$bCompletely || $CNF['REMOVE_MESSAGE_IMMEDIATELY']) && $bIsAllowedToDelete)))
            return echoJson($aResult);

        if ($this->_oDb->deleteJot($iJotId, $this->_iUserId, $bCompletely || $CNF['REMOVE_MESSAGE_IMMEDIATELY'])) {
            $this->onDeleteJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $iJotId, $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']]);
            $aResult = array('code' => 0, 'html' => !$bCompletely ? $this->_oTemplate->getMessageIcons($iJotId, 'delete', $bIsLotAuthor || isAdmin()) : '');
        }

        echoJson($aResult);
    }

    /**
     * React on defined jot
     * @return string with json
     */
    public function actionJotReaction()
    {
        $iJotId = bx_get('jot');
        $aEmoji = bx_get('emoji');
        $sAction = bx_get('action');
        $aJotInfo = $this->_oDb->getJotById($iJotId);

        $aResult = array('code' => 1);
        $CNF = &$this->_oConfig->CNF;

        if (empty($aJotInfo))
            return echoJson($aResult);

        $bIsParticipant = $this->_oDb->isParticipant($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId);
        if (!$bIsParticipant)
            return echoJson($aResult);

        if ($sAction == BX_JOT_REACTION_ADD && $this->_oDb->addJotReaction($iJotId, $this->_iProfileId, $aEmoji)) {
            $aLotInfo = $this->_oDb->getLotInfoById($aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
            $this->onReactJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $iJotId, $aLotInfo[$CNF['FIELD_AUTHOR']]);
            $aResult = array('code' => 0, 'html' => $this->_oTemplate->getJotReactions($iJotId));
        }

        echoJson($aResult);
    }

    public function actionUpdateReaction()
    {
        $iJotId = bx_get('jot');
        $sEmoji = bx_get('emoji_id');
        $sAction = bx_get('action');
        $aJotInfo = $this->_oDb->getJotById($iJotId);

        $aResult = array('code' => 1);
        $CNF = &$this->_oConfig->CNF;

        if (empty($aJotInfo))
            return echoJson($aResult);

        $bIsParticipant = $this->_oDb->isParticipant($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId);
        if (!$bIsParticipant)
            return echoJson($aResult);

        if ($this->_oDb->updateReaction($iJotId, $this->_iProfileId, $sEmoji, $sAction)){
            $aLotInfo = $this->_oDb->getLotInfoById($aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
            $this->onReactJot(
                $aJotInfo[$CNF['FIELD_MESSAGE_FK']],
                $iJotId,
                $aLotInfo[$CNF['FIELD_AUTHOR']], $sAction === BX_JOT_REACTION_ADD ? BX_JOT_REACTION_ADD : BX_JOT_REACTION_REMOVE
            );

            $aResult = array('code' => 0);
        }

        echoJson($aResult);
    }

    /**
     * Get body of the jot
     * @return string with json
     */
    public function actionViewJot()
    {
        $iJotId = bx_get('jot');
        $aJotInfo = $this->_oDb->getJotById($iJotId);
        if (empty($aJotInfo) || !(isAdmin() || $this->_oDb->isAuthor($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iUserId)))
            return echoJson(array('code' => 1));

        $aResult = array('code' => 0, 'html' => $this->_oTemplate->getJotsBody($iJotId));
        echoJson($aResult);
    }

    /**
     * Jot edit panel
     * @return string with json
     */
    public function actionEditJotForm()
    {
        $iJotId = bx_get('jot');
        $aResult = array('code' => 1);
        $aJotInfo = $this->_oDb->getJotById($iJotId);

        if (empty($aJotInfo) || !(isAdmin() || $this->_oDb->isAuthor($iJotId, $this->_iUserId, false) || $this->_oDb->isAuthor($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iUserId))) {
            return echoJson($aResult);
        }

        $aResult = array('code' => 0, 'html' => $this->_oTemplate->getEditJotArea($iJotId));
        echoJson($aResult);
    }

    public function actionEditJot()
    {
        $iJotId = bx_get('jot');
        $aJotInfo = $this->_oDb->getJotById($iJotId);

        $sMessage = preg_replace(array('/\<p>/i', '/\<\/p>/i'), array("", "<br/>"), bx_get('message'));
        $aResult = array('code' => 1);
        if (empty($aJotInfo) || !(isAdmin() || $this->_oDb->isAuthor($iJotId, $this->_iUserId, false) || $this->_oDb->isAuthor($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iUserId)))
            return echoJson($aResult);

        if ($this->_oDb->editJot($iJotId, $this->_iUserId, $sMessage)) {
            $aResult = array('code' => 0, 'html' => $this->_oTemplate->getMessageIcons($iJotId, 'edit'));
            $this->onUpdateJot($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $iJotId, $aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_AUTHOR']]);
        }

        echoJson($aResult);
    }

    /**
     * Removes specified jot
     * @return string with json
     */
    public function actionDeleteFile()
    {
        $iFileId = bx_get('id');
        $aResult = array('code' => 1, 'message' => _t('_bx_messenger_post_file_not_found'));
        if (!$iFileId)
            return echoJson($aResult);

        $CNF = &$this->_oConfig->CNF;
        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        $aFile = $oStorage->getFile($iFileId);

        $bIsAllowedToDelete = $this->_oDb->isAllowedToDeleteJot($aFile[$this->_oConfig->CNF['FIELD_ST_JOT']], $this->_iUserId);
        if (!$bIsAllowedToDelete && !isAdmin())
            return echoJson($aResult);

		if ($oStorage -> deleteFile($iFileId, $this -> _iUserId))
		{
            $aResult = array('code' => 0);

            $aJotInfo = $this->_oDb->getJotById($aFile[$CNF['FIELD_ST_JOT']]);
            $aJotFiles = $this->_oDb->getJotFiles($aFile[$CNF['FIELD_ST_JOT']]);

            if (count($aJotFiles) == 0 && !$aJotInfo[$CNF['FIELD_MESSAGE']] && $this->_oDb->deleteJot($aJotInfo[$CNF['FIELD_MESSAGE_ID']], $this->_iUserId)) {
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

        if (!$iLotId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId)) {
            return echoJson(array('message' => _t('_bx_messenger_not_participant'), 'code' => 1));
        }

        if ($this->_oDb->isAuthor($iLotId, $this->_iUserId))
            return echoJson(array('message' => _t('_bx_messenger_cant_leave'), 'code' => 1));


        if ($this->_oDb->leaveLot($iLotId, $this->_iUserId))
            return echoJson(array('message' => _t('_bx_messenger_successfully_left'), 'code' => 0));
    }

    /**
     * Block notifications from specified lot(conversation)
     * @return string with json
     */
    public function actionMute()
    {
        $iLotId = bx_get('lot');

        if ($iLotId && $this->_oDb->isParticipant($iLotId, $this->_iUserId)) {
            $bMuted = $this->_oDb->muteLot($iLotId, $this->_iUserId);
            return echoJson(array('code' => $bMuted, 'title' => $bMuted ? _t('_bx_messenger_lots_menu_mute_info_on') : _t('_bx_messenger_lots_menu_mute_info_off')));
        }
    }

    /**
     * Mark lot with star
     * @return string with json
     */
    public function actionStar()
    {
        $iLotId = bx_get('lot');

        if ($iLotId && $this->_oDb->isParticipant($iLotId, $this->_iUserId)) {
            $bStar = $this->_oDb->starLot($iLotId, $this->_iUserId);
            return echoJson(array('code' => $bStar, 'title' => !$bStar ? _t('_bx_messenger_lots_menu_star_on') : _t('_bx_messenger_lots_menu_star_off')));
        }
    }

    /**
     * Returns number of lots with at least one unread message
     * @return int
     */
    public function serviceGetUpdatedLotsNum($iProfileId = 0)
    {
        if (!$this->isLogged() && !(int)$iProfileId)
            return 0;

        $aLots = $this->_oDb->getMyLots($iProfileId ? (int)$iProfileId : $this->_iUserId, BX_IM_EMPTY, BX_IM_EMPTY, true);
        return sizeof($aLots);
    }

    /**
     * Sends push notifications for participants of specified lot
     * @return boolean TRUE on success or FALSE on failure
     */
    public function actionSendPushNotification()
    {
        $iLotId = (int)bx_get('lot');
        $aSent = is_array(bx_get('sent')) ? bx_get('sent') : array();

        if ($this->_oDb->isPushNotificationsEnabled())
            return false;

        if (!$this->isLogged() || !$this->_oConfig->CNF['IS_PUSH_ENABLED'] || !$iLotId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId))
            return false;

        $bIsGlobalSettings = $this->_oConfig->CNF['IS_PUSH_ENABLED'] && getParam('sys_push_app_id');

        $aLot = $this->_oDb->getLotInfoById($iLotId);
        if (empty($aLot)) return false;

        $aParticipantList = $this->_oDb->getParticipantsList($aLot[$this->_oConfig->CNF['FIELD_ID']], true, $this->_iUserId);
        if (empty($aParticipantList)) return false;

        $oLanguage = BxDolStudioLanguagesUtils::getInstance();
        $sLanguage = $oLanguage->getCurrentLangName(false);

        $aLatestJot = $this->_oDb->getLatestJot($iLotId, $this->_iUserId);
        $sMessage = $aLatestJot[$this->_oConfig->CNF['FIELD_MESSAGE']];
        if ($sMessage)
            $sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength(html_entity_decode($sMessage), (int)$this->_oConfig->CNF['PARAM_PUSH_NOTIFICATIONS_DEFAULT_SYMBOLS_NUM']);

        if (!$sMessage && $aLatestJot[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
            $sMessage = _t('_bx_messenger_attached_files_message', $this->_oDb->getJotFiles($aLatestJot[$this->_oConfig->CNF['FIELD_MESSAGE_ID']], true));

        $aContent = array(
            'en' => $sMessage,
        );

        if (!$aContent[$sLanguage])
            $aContent[$sLanguage] = $sMessage;

        $oProfile = BxDolProfile::getInstance($this->_iUserId);
        if ($oProfile)
            $aHeadings = array(
                $sLanguage => _t('_bx_messenger_push_message_title', $oProfile->getDisplayName())
            );
        else
            return false;

        $aWhere = array();
        $aInfo = array(
            'contents' => $aContent,
            'headings' => $aHeadings,
            'url' => $this->_oConfig->getRepostUrl($aLatestJot[$this->_oConfig->CNF['FIELD_MESSAGE_ID']])
        );

		foreach($aParticipantList as $iKey => $iValue)
		{   
            if (array_search($iValue, $aSent) !== FALSE || $this->_oDb->isMuted($aLot[$this->_oConfig->CNF['FIELD_ID']], $iValue))
                continue;

			if ($bIsGlobalSettings)
	            BxDolPush::getInstance()->send($iValue, array_merge($aInfo, array('icon' => $oProfile->getThumb())), true);
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
            'app_id' => $this->_oConfig->CNF['PUSH_APP_ID'],
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
    public function actionLoadMembersTemplate()
    {
        if (!$this->isLogged())
            return '';
        echoJson(
            array(
                'data' => $this->_oTemplate->getMembersJotTemplate($this->_iUserId)
            ));
    }

    /**
     * Delete all content by profile ID
     * @param object oAlert
     * @return boolean
     */
    public function serviceDeleteHistoryByAuthor($oAlert)
    {
        return $oAlert->iObject && $oAlert->aExtras['delete_with_content'] ?
            $this->_oDb->deleteProfileInfo($oAlert->iObject) : false;
    }

    /**
     * Parse jot's link (repost)
     * @param object oAlert
     * @return string json
     */
    public function actionParseLink()
    {
        $sUrl = bx_get('link');
        $iJotId = (int)bx_get('jot_id');
        $bDontAttach = (int)bx_get('dont_attach');

        $aUrl = $this->_oConfig->isJotLink($sUrl);
		if (!empty($aUrl))
		{
            $aJotInfo = $this->_oDb->getJotById($aUrl['id']);
            if (!$this->_oDb->isParticipant($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iUserId))
                return echoJson(array('code' => 0));

            $sHTML = $this->_oTemplate->getJotAsAttachment($aUrl['id']);
            if ($sHTML && !$bDontAttach)
                $this->_oDb->addAttachment($iJotId, $aUrl['id']);

            return echoJson(array('code' => 0, 'html' => $sHTML));
        }

        echoJson(array('code' => 1));
    }

    /**
     * Parses link from the message
     * @param object oAlert
     * @return json
     */
    public function actionGetAttachment()
    {
        $iJotId = (int)bx_get('jot_id');

		if ($iJotId)
		{
            $aJot = $this->_oDb->getJotById($iJotId);
            if ($this->_oDb->isParticipant($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iUserId)) {
                $sHTML = $this->_oTemplate->getAttachment($aJot, true, true);
                if ($sHTML)
                    return echoJson(array('code' => 0, 'html' => $sHTML));
            }
        }

        echoJson(array('code' => 1));
    }

    public function actionGetUploadFilesForm()
    {
        header('Content-type: text/html; charset=utf-8');
        echo $this->_oTemplate->getFilesUploadingForm($this->_iUserId);
        exit;
    }

    public function actionGetRecordVideoForm()
    {
        header('Content-type: text/html; charset=utf-8');
        echo $this->_oTemplate->getVideoRecordingForm($this->_iUserId);
        exit;
    }

    public function actionUploadTempFile()
    {
        $CNF = &$this->_oConfig->CNF;

        switch($_SERVER['REQUEST_METHOD']){
            case 'DELETE':
                if ($sName = @file_get_contents("php://input"))
                    parse_str($sName, $aName);

                if (!empty($aName) && @unlink(BX_DIRECTORY_PATH_TMP . $aName['name']))
                    echo 'OK';

                break;
            case 'POST':
                $oStorage = new BxMessengerStorage($CNF['OBJECT_STORAGE']);
                if (!$oStorage) {
                    echo 0;
                    exit;
                }

                if (($oFiles = $_FILES[$CNF['FILES_UPLOADER']]) && $oStorage->isValidFileExt($oFiles['name'])) {
                    $sTempFile = $oFiles['tmp_name'];
                    $sTargetFile = BX_DIRECTORY_PATH_TMP . $oFiles['name'];
                    move_uploaded_file($sTempFile, $sTargetFile);
                    echo 'OK';
                }
        }

        return '';
    }

    public function actionUploadVideoFile()
    {
        $oStorage = new BxMessengerStorage($this->_oConfig->CNF['OBJECT_STORAGE']);
        if (!$oStorage || !isset($_POST['name']) || empty($_FILES)) {
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_video')));
        }

        if ($_FILES['file'] && $oStorage->isValidFileExt($_FILES['file']['name'])) {
            $sTempFile = $_FILES['file']['tmp_name'];
            $sTargetFile = BX_DIRECTORY_PATH_TMP . $_POST['name'];

            if (move_uploaded_file($sTempFile, $sTargetFile))
                return echoJson(array('code' => 0));
        }

        echoJson(array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_video')));
    }

    public function actionIsValidFile()
    {
        $oStorage = new BxMessengerStorage($this->_oConfig->CNF['OBJECT_STORAGE']);
        if (!$oStorage)
            return echoJson(array('code' => 1));

        $sFileName = bx_get('name');
        if ($sFileName && (int)$oStorage->isValidFileExt($sFileName)) {
            $sIconFile = $oStorage->getFontIconNameByFileName($sFileName);
            return echoJson(array('code' => 0, 'thumbnail' => $sIconFile ? $sIconFile : '', 'is_image' => (int)$oStorage->isImageExt($sFileName)));
        }

        echoJson(array('code' => 1));
    }


    public function actionDownloadFile($iFileId)
    {
        $aResult = array('code' => 1, 'message' => _t('_bx_messenger_post_file_not_found'));
        if (!$iFileId)
            return echoJson($aResult);

        $aFile = BxDolStorage::getObjectInstance($this->_oConfig->CNF['OBJECT_STORAGE'])->getFile((int)$iFileId);
        $sFileName = BX_DIRECTORY_STORAGE . $this->_oConfig->CNF['OBJECT_STORAGE'] . '/' . $aFile['path'];
        if (!empty($aFile) && file_exists(BX_DIRECTORY_STORAGE . $this->_oConfig->CNF['OBJECT_STORAGE'] . '/' . $aFile['path'])) {
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

    /**
     * Returns big image for popup whem member click on small icon in talk history
     * @param int $iStorageId file id
     * @param int $iWidth widht of the window
     * @param int $iHeight height of the window
     * @return string html content
     */
    function actionGetBigImage($iStorageId, $iWidth, $iHeight)
    {
        if (!$iStorageId) return '';

        $iWidth = (int)$iWidth * 0.9;
        $iHeight = (int)$iHeight * 0.9;

        $aFile = BxDolStorage::getObjectInstance($this->_oConfig->CNF['OBJECT_STORAGE'])->getFile((int)$iStorageId);
        $sImagePath = BX_DIRECTORY_STORAGE . $this->_oConfig->CNF['OBJECT_STORAGE'] . '/' . $aFile['path'];

		if (!empty($aFile) && file_exists($sImagePath))
		{
            $aInfo = getimagesize($sImagePath);

            if ($aInfo[0] <= $iWidth && $aInfo[1] <= $iHeight) {
                $iWidth = (int)$aInfo[0];
                $iHeight = (int)$aInfo[1];
			}
			else
			{
                $fXRatio = $aInfo[0] / $iWidth;
                $fYRatio = $aInfo[1] / $iHeight;

                if ($fXRatio > $fYRatio)
                    $iHeight = 'auto';
                else
                    $iWidth = 'auto';
            }
        }

        echo $this->_oTemplate->parseHtmlByName('big_image.html', array(
            'height' => $iHeight,
            'width' => $iWidth,
            'url' => BxDolStorage::getObjectInstance($this->_oConfig->CNF['OBJECT_STORAGE'])->getFileUrlById((int)$iStorageId)
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
                array('group' => $sModule, 'unit' => $sModule, 'action' => 'got_jot_ntfs', 'types' => array('personal'))
            ),
            'alerts' => array(
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
        if (empty($aJotInfo) || empty($aLotInfo))
            return array();

        $sEntryUrl = $this->_oConfig->getRepostUrl($aJotInfo[$CNF['FIELD_MESSAGE_ID']]);
        $iType = $aLotInfo[$CNF['FIELD_TYPE']];

        $sTitle = isset($aLotInfo[$CNF['FIELD_TITLE']]) && $aLotInfo[$CNF['FIELD_TITLE']] ?
            $aLotInfo[$CNF['FIELD_TITLE']] : _t('_bx_messenger_lots_private_lot');

        if ($this->_oDb->isLinkedTitle($iType)) {
            $sTitle = $this->_oDb->isLinkedTitle($iType) ? _t('_bx_messenger_linked_title', $sTitle) : _t($sTitle);
            $sEntryUrl = $this->_oConfig->getPageLink($aLotInfo[$CNF['FIELD_URL']]);
        }

        // replace br to spaces and truncate the line
        $sTruncatedMessage = strmaxtextlen(preg_replace( '/<br\W*?\/>|\n/', " ", $aJotInfo[$CNF['FIELD_MESSAGE']]), $CNF['PARAM_MAX_JOT_NTFS_MESSAGE_LENGTH']);
        $sMessage = _t('_bx_messenger_txt_sample_comment_single', $sTruncatedMessage);
        switch($aJotInfo[$CNF['FIELD_MESSAGE_AT_TYPE']]){
            case BX_ATT_TYPE_FILES:
                $sMessage = _t('_bx_messenger_txt_sample_comment_file_single', $this->_oDb->getJotFiles($aJotInfo[$CNF['FIELD_MESSAGE_ID']], true));
                break;
            case BX_ATT_TYPE_GIPHY:
                $sMessage = _t('_bx_messenger_txt_sample_comment_giphy_single');
        }

        $aResult = array(
            'entry_sample' => _t('_bx_messenger_message'),
            'entry_url' => $sEntryUrl,
            'entry_caption' => $sTitle,
            'entry_author' => $aEvent['object_owner_id'],
            'subentry_sample' => $sMessage,
            'lang_key' =>  $aJotInfo[$CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES
                            || $aJotInfo[$CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_GIPHY
                ? '_bx_messenger_txt_subobject_added_single' : '_bx_messenger_txt_subobject_added'
        );

        list($iNumber) = explode('.', bx_get_ver());
        if ((int)$iNumber > 10){
            $sSubject = _t('_bx_messenger_notification_subject', BxDolProfile::getInstanceMagic($aEvent['owner_id'])->getDisplayName());

            $sAlterBody = $sTruncatedMessage;
            switch($aJotInfo[$CNF['FIELD_MESSAGE_AT_TYPE']]){
                case BX_ATT_TYPE_FILES:
                case BX_ATT_TYPE_GIPHY:
                    $sAlterBody = _t('_bx_messenger_txt_sample_email_push', html2txt($sMessage));
            }

            $aResult['lang_key'] = array(
                    'site' => $aResult['lang_key'],
                    'email' => $sAlterBody,
                    'push' => $sAlterBody
        );

            $aResult['settings'] = array(
                    'email' => array(
                        'subject' => $sSubject
                    ),
                    'push' => array(
                        'subject' => $sSubject
                    )
                );
        }

        return $aResult;
    }

    /**
     * Check if the user can read Lot messages
     * @param array $aLot contains Lot details
     * @param boolean $isPerformAction used for compatibility with parent method
     * @return int
     */

    public function serviceCheckAllowedViewForProfile ($aDataEntry, $isPerformAction = false, $iProfileId = false){
        if (!$iProfileId)
            $iProfileId = $this->_iProfileId;

        $mixedResult = null;
        bx_alert('system', 'check_allowed_view', 0, 0, array('module' => $this->getName(), 'content_info' => $aDataEntry, 'profile_id' => $iProfileId, 'override_result' => &$mixedResult));
        if($mixedResult !== null)
            return $mixedResult;

        $CNF = &$this->_oConfig->CNF;
        if (empty($aDataEntry))
            return CHECK_ACTION_RESULT_ALLOWED;

        return $this->_oDb->isParticipant($aDataEntry[$CNF['FIELD_ID']], $this->_iProfileId, true) === TRUE
        || $aDataEntry[$CNF['FIELD_TYPE']] == BX_IM_TYPE_PUBLIC
        || $aDataEntry[$CNF['FIELD_TYPE']] == BX_IM_TYPE_SETS ? CHECK_ACTION_RESULT_ALLOWED : CHECK_ACTION_RESULT_NOT_ALLOWED;
    }

    public function onSendJot($iJotId)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'send_jot', $iJotId, $this->_iUserId);

        $iLotId = $this->_oDb->getLotByJotId($iJotId);
        if (!$iLotId)
            return false;

        $aPartList = $this->_oDb->getParticipantsList($iLotId, true, $this->_iUserId);
        if (empty($aPartList))
            return false;

        foreach ($aPartList as $iKey => $iPart) {
            if ($this->_oDb->isAllowedToSendNtfs($this->_iUserId, $iLotId))
                bx_alert($this->_oConfig->getObject('alert'), 'got_jot_ntfs', $iLotId, $this->_iUserId, array('object_author_id' => $iPart, 'recipient_id' => $iPart, 'subobject_id' => $iJotId));

            bx_alert($this->_oConfig->getObject('alert'), 'got_jot', $iLotId, $this->_iUserId, array('recipient_id' => $iPart, 'subobject_id' => $iJotId));
        }

    }

    public function onDeleteJot($iLotId, $iJotId, $iProfileId = 0)
    {
        $iProfileId = $iProfileId ? $iProfileId : $this->_iUserId;
        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot', $iJotId, $this->_iUserId, array('author_id' => $iProfileId, 'lot_id' => $iLotId));
        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot_ntfs', $iLotId, $iProfileId, array('subobject_id' => $iJotId));
    }

    public function onReactJot($iLotId, $iJotId, $iAuthor, $sAction = 'add_reaction')
    {
        bx_alert($this->_oConfig->getObject('alert'), $sAction, $iJotId, $this->_iProfileId, array('author_id' => $iAuthor, 'lot_id' => $iLotId));
    }

    public function onUpdateJot($iLotId, $iJotId, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'update_jot', $iJotId, $this->_iUserId, array('author_id' => $iProfileId, 'lot_id' => $iLotId));
    }

    public function onCreateLot($iLotId)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'create_lot', $iLotId, $this->_iUserId);
    }

    public function onDeleteLot($iLotId, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'delete_lot', $iLotId, $this->_iUserId, array('author_id' => $iProfileId));
    }

    public function onAddNewParticipant($iLotId, $iParticipant, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'add_part', $iParticipant, $this->_iUserId, array('lot_id' => $iLotId, 'author_id' => $iProfileId));
    }

    public function onRemoveParticipant($iLotId, $iParticipant, $iProfileId = 0)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'remove_part', $iParticipant, $this->_iUserId, array('lot_id' => $iLotId, 'author_id' => $iProfileId));
    }

    public function onCheckContact($iSender, $iRecipient)
    {
        if(method_exists('BxDolProfile', 'checkAllowedProfileContact')) {
            $mixedResult = BxDolProfile::getInstance($iSender)->checkAllowedProfileContact($iRecipient);
        if($mixedResult !== CHECK_ACTION_RESULT_ALLOWED)
                return false;
        }

        $bCanContact = true;
        bx_alert($this->_oConfig->getObject('alert'), 'check_contact', 0, false, array('can_contact' => &$bCanContact, 'sender' => $iSender, 'recipient' => $iRecipient, 'where' => $this->_oConfig->getName()));
        return $bCanContact;
    }

    public function serviceIsContactAllowed($mixedObject)
    {
        if (!$this->_iUserId)
            return false;
        return $this->onCheckContact($this->_iUserId, (int)$mixedObject);
    }

    public function serviceGetLiveUpdates($aMenuItemParent, $aMenuItemChild, $iCount = 0)
    {
        $aLots = $this->_oDb->getMyLots($this->_iUserId, BX_IM_EMPTY, BX_IM_EMPTY, true);
        $iCountNew = sizeof($aLots);
        if ($iCountNew == $iCount)
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
    public function serviceGetLiveComments($iObjectId, $sType, $iProfileId = 0, $iStart = 0, $iPerPage = 0)
    {
        $aComments = $this->_oDb->getLiveComments($iObjectId, $sType, $iProfileId, $iStart, $iPerPage);
        if (!(int)$aComments['total'])
            return array(
                            'items' => array(),
                            'id' => $iObjectId,
                            'type' => $sType,
                            'total' => 0
                        );

        $aResult = array();
        foreach ($aComments['result'] as $iKey => $aValue) {
            $oProfile = $this->_oTemplate->getObjectUser($aValue[$this->_oConfig->CNF['FIELD_LCMTS_AUTHOR']]);
            $aResult[$aValue[$this->_oConfig->CNF['FIELD_LCMTS_ID']]] = array(
                'text' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_TEXT']],
                'author' => array(
                    'id' => $aValue[$this->_oConfig->CNF['FIELD_LCMTS_AUTHOR']],
                    'name' => $oProfile->getDisplayName(),
                    'icon' => $oProfile->getAvatar(),
                    'url' => $oProfile->getUrl()
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
    public function servicePostLiveComments($sText, $iObjectId, $sType, $iProfileId, $iDate = 0)
    {
        if (!$sText || !$iObjectId || !$sType || !$iProfileId)
            return 0;

        $aResult = array('code' => 1);
        if ($iId = $this->_oDb->addLiveComment($sText, $iObjectId, $sType, $iProfileId, $iDate))
            $aResult = array('code' => 0, 'id' => $iId, 'date' => $iDate ? $iDate : time());

        return $aResult;
    }

    public function serviceRemoveLiveComment($iObjectId, $iProfileId)
    {
        if (!$iObjectId || !$iProfileId)
            return false;

        $aResult = array('code' => 0, 'id' => $iObjectId);
        return $this->_oDb->removeLiveComment($iObjectId, $iProfileId) ? $aResult : array('code' => 1);
    }

    public function actionGetFilesList($iLotId)
    {
        if (!$iLotId || !$this->_iUserId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId))
            return false;

        $aFiles = $this->_oDb->getLotFiles($iLotId);
        if (empty($aFiles))
            return array();

        $aResult = array();
        $CNF = &$this->_oConfig->CNF;
        foreach ($aFiles as $iKey => $aValue)
            $aResult[$aValue[$CNF['FIELD_ST_ID']]] = $this->_oTemplate->getFileContent($aValue);

        return $aResult;
    }

    public function actionGetLotTabs($iLotId, $sAction)
    {
        if (!$iLotId || !$this->_iUserId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId))
            return echoJson(array('code' => 1, 'msg' => _t('_bx_messenger_no_permissions')));

        $sHTML = '';
        switch ($sAction) {
            case 'files':
                $aFiles = $this->actionGetFilesList($iLotId);
                if (empty($aFiles))
                    $sHTML = MsgBox(_t('_bx_messenger_txt_msg_no_results'));
                else
                    $sHTML = $this->_oTemplate->parseHtmlByName('files_feeds.html', array('bx_repeat:files' => $aFiles));
                break;
        }

        header('Content-type: text/html; charset=utf-8');
        echo $sHTML;
        exit;
    }

    /************ REACT Jot Integration *************************************/

    public function serviceGetLotsList($iProfileId)
    {
        if (!$iProfileId)
            return '';

        $aLots = $this->_oDb->getMyLots($iProfileId);
        if (empty($aLots))
            return array();

        $aLotsList = array();
        foreach ($aLots as $iKey => $aLot) {
            $aParticipantsList = $this->_oDb->getParticipantsList($aLot[$this->_oConfig->CNF['FIELD_ID']], true, $iProfileId);

            $iParticipantsCount = count($aParticipantsList);
            $aParticipantsList = $iParticipantsCount ? array_slice($aParticipantsList, 0, $this->_oConfig->CNF['PARAM_ICONS_NUMBER']) : array($iProfileId);

            $aNickNames = array();
            foreach ($aParticipantsList as $iParticipant) {
                $oProfile = $this->_oTemplate->getObjectUser($iParticipant);
                if ($oProfile) {
                    $aNickNames[] = array(
                        'name' => $oProfile->getDisplayName(),
                        'url' => $oProfile->getUrl(),
                        'thumb' => $oProfile->getThumb()
                    );
                }
            }

            if (!empty($aLot[$this->_oConfig->CNF['FIELD_TITLE']]))
                $sTitle = _t($aLot[$this->_oConfig->CNF['FIELD_TITLE']]);
            else {
                    $aTitle = array();
                    $aTmpNickNames = $iParticipantsCount > 3 ? array_slice($aNickNames, 0, $this->_oConfig->CNF['PARAM_ICONS_NUMBER']) : $aNickNames;
                    foreach ($aTmpNickNames as &$aValue)
                        $aTitle[] = $aValue['name'];

                    $sTitle = implode(', ', $aTitle);
                }


            $sStatus = '';
            if ($iParticipantsCount == 1 && $oProfile && empty($aLot[$this->_oConfig->CNF['FIELD_TITLE']]))
                $sStatus = (int)(method_exists($oProfile, 'isOnline') ? $oProfile->isOnline() : false);
            else
                $sStatus = $iParticipantsCount;

            $aLatestJots = $this->_oDb->getLatestJot($aLot[$this->_oConfig->CNF['FIELD_ID']]);
            $iTime = $aLot[$this->_oConfig->CNF['FIELD_ADDED']];

            $oSender = $sSender = $sMessage = '';
            if (!empty($aLatestJots)) {
                if (isset($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE']])) {
                    $sMessage = $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE']];
                    if ($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_REPOST) {
                        $sMessage = $this->_oConfig->cleanRepostLinks($sMessage, $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT']]);
                        $sMessage = $sMessage ? $sMessage : _t('_bx_messenger_repost_message');
                    }

                    $sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sMessage, $this->_oConfig->CNF['MAX_PREV_JOTS_SYMBOLS']);
                }

                if (!$sMessage && $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
                    $sMessage = _t('_bx_messenger_attached_files_message', $this->_oDb->getJotFiles($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_ID']], true));

                $sSender = '';
                if ($oSender = $this->_oTemplate->getObjectUser($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AUTHOR']]))
                    $sSender = $oSender->id() == $iProfileId ? _t('_bx_messenger_you_username_title') : $oSender->getDisplayName();

                $iTime = $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_ADDED']];
            }

            $aLotsList[] = array(
                'participants_count' => $iParticipantsCount,
                'participants_list' => $aNickNames,
                'title' => $sTitle,
                'lot_id' => $aLot[$this->_oConfig->CNF['FIELD_ID']],
                'status' => $sStatus,
                'latest_jot_message' => $sMessage,
                'latest_jot_message_author' => $sSender,
                'latest_jot_message_date' => $iTime,
                'latest_jot_message_author_thumb' => $oSender ? $oSender->getThumb() : '',
            );
        }

        return $aLotsList;
    }

    public function servicePerformConnAction($iInitiator, $iContent, $sAction = 'add', $sConnectionType = 'follow')
    {
        require_once(BX_DIRECTORY_PATH_INC . "design.inc.php");

        if (!$iInitiator || !$iContent)
            return array();


        $oConnections = BxDolConnection::getObjectInstance($sConnectionType === 'follow' ? 'sys_profiles_subscriptions' : 'sys_profiles_friends');
        $sMethod = "{$sAction}Connection";
        if (empty($oConnections) || !method_exists($oConnections, $sMethod))
            return array();

        return array(
            'result' => (int)$oConnections->$sMethod($iInitiator, $iContent),
            'content_id' => (int)$iContent,
            'action' => $sAction
        );
    }

    public function serviceProfileConnections($sType = 'friends', $iContentId = 0, $iStart = 0, $iPerPage = 10, $iMutual = false)
    {
        $iProfileId = $iContentId ? $iContentId : $this->_iUserId;

        if (!$iProfileId)
            return array();

        $aMembers = array();
        $oFriends = BxDolConnection::getObjectInstance('sys_profiles_friends');
        $oFollowers = BxDolConnection::getObjectInstance('sys_profiles_subscriptions');

        switch ($sType) {
            case 'friends':
                if (!$oFriends)
                    return array();

                $aMembers = $oFriends->getConnectedContent($iProfileId, $iMutual, $iStart, $iPerPage);
                break;
            case 'connections':
                if (!$oFollowers)
                    return array();

                $aMembers = $oFollowers->getConnectedContent($iProfileId, $iMutual, $iStart, $iPerPage);

                break;
            case 'recent':
            case 'active':
            case 'online':
            case 'recommended':
            case 'top':
                if (BxDolRequest::serviceExists('bx_persons', 'get_members')) {
                    $aPersons = BxDolService::call('bx_persons', 'get_members', array($sType, $iStart, $iPerPage));
                    foreach ($aPersons as &$aPerson)
                        $aMembers[$aPerson['author']] = $aPerson;
                }
                break;
        }

        if (empty($aMembers))
            return $aMembers;

        $aRet = array();
        foreach ($aMembers as $iId => $aInfo) {
            if (($oProfile = BxDolProfile::getInstance($iId))) {
                $oAccount = $oProfile->getAccountObject();
                $aRet[$iId] = array(
                    'name' => $oProfile->getDisplayName(),
                    'profile_display_name' => $oProfile->getDisplayName(),
                    'email' => $oAccount->getEmail(),
                    'id' => $iId,
                    'url' => $oProfile->getUrl(),
                    'info' => $oProfile->getInfo(),
                    'thumb' => $oProfile->getThumb(),
                    'picture' => $oProfile->getPicture(),
                    'avatar' => $oProfile->getAvatar(),
                    'is_online' => $oProfile->isOnline(),
                    'cover' => $oProfile->getUnitCover(),
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

    public function serviceGetModulesList()
    {
        $aResult = array();
        if (!$this->isLogged())
            return $aResult;

        $aModules = $this->_oDb->getAllCmtsModule();
        foreach ($aModules as $sKey => $aModule) {
            if (!$aModule['icon']) {
                $aResult[$sKey] = array(
                    'icon' => 'world',
                    'icon_color' => 'green'
                );
                continue;
            }
            preg_match('/([a-z]+)(?:-[a-z]+)?\s+col-([a-z]+)/', $aModule['icon'], $aMatches);
            $aResult[$sKey] = array(
                'icon' => $aMatches[1],
                'icon_color' => $aMatches[2]
            );
        }

        return $aResult;
    }

    public function serviceSwitchProfile($iSwitchToProfileId)
    {
        $aResult = array('code' => 1);

        if ($iSwitchToProfileId) {
            $oProfile = BxDolProfile::getInstance($iSwitchToProfileId);

            if ($oProfile && BxDolService::call($oProfile->getModule(), 'act_as_profile')) {
                $iViewerAccountId = getLoggedId();
                $iSwitchToAccountId = $oProfile->getAccountId();
                $bCanSwitch = $iSwitchToAccountId == $iViewerAccountId;
                bx_alert('account', 'check_switch_context', $iSwitchToAccountId, bx_get_logged_profile_id(), array('switch_to_profile' => $iSwitchToProfileId, 'viewer_account' => $iViewerAccountId, 'override_result' => &$bCanSwitch));

                if ($bCanSwitch) {
                    $oAccount = BxDolAccount::getInstance();
                    if ($oAccount->updateProfileContext($iSwitchToProfileId))
                        $aResult = array('code' => 0, 'msg' => _t('_sys_txt_account_profile_context_changed_success', $oProfile->getDisplayName()));
                }
            }
        }

        return $aResult;
    }

    public function serviceGetLotInfo($iProfileId, $iLotId, $iJotsLimit = 0, $iStart = BX_IM_EMPTY, $sType = BX_JOT_TYPE_NEW)
    {
        $aResult = array('code' => 1);
        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        if (!$iLotId || empty($aLotInfo))
            return $aResult;

        if ($iProfileId !== $this->_iUserId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId))
            return array('code' => 1, _t('_bx_messenger_not_participant'));

        $CNF = $this->_oConfig->CNF;
        $iJotsLimit = $iJotsLimit ? $iJotsLimit : $CNF['MAX_JOTS_LOAD_HISTORY'];
        $aJotsList = $this->_oDb->getJotsByLotId($iLotId, $iStart, $sType, $iJotsLimit);

        $aJots = array();
        foreach ($aJotsList as &$aJot) {
            $aAuthor = BxDolProfile::getInstance($aJot[$CNF['FIELD_MESSAGE_AUTHOR']]);
            $aJots[] = array_merge($aJot, array(
                'thumb' => $aAuthor->getThumb(),
                'name' => $aAuthor->getDisplayName(),
                'files' => $this->_oTemplate->getAttachment($aJot, true)
            ));
        }

        return array('lot_id' => $iLotId, 'jots' => $aJots);
    }

    public function serviceSendMessage($iProfileId, $iLotId, $sMessage)
    {
        if ($iProfileId !== $this->_iUserId || !$this->_oDb->isParticipant($iLotId, $this->_iUserId))
            return array('code' => 1, _t('_bx_messenger_not_participant'));

        if ($sMessage = $this->prepareMessageToDb($sMessage))
            return array('code' => 0, 'id' => $this->_oDb->addNewJot($iLotId, $sMessage, $this->_iUserId));

        return array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_data'));
    }

    private function prepareMessageToDb($sMessage)
    {
        return $sMessage ? preg_replace(array(/*/\<a.*>/i', '/\<\/a>/i',*/ '/\<p>/i', '/\<\/p>/i', '/\<pre.*>/i'), array(/*'', '',*/ '', '<br/>', '<pre>'), $sMessage) : '';
    }

    function actionGetGiphy(){
        if (!$this->isLogged())
            return '';

        $aContent = $this->_oTemplate->getGiphyItems(bx_get('action'), urlencode(bx_get('filter')), (float)bx_get('height'), (int)bx_get('start'));
        if (isset($aContent['content']) && $aContent['content'])
            return echoJson(array('code' => 0, 'html' => $aContent['content'], 'total' => isset($aContent['pagination']) ? $aContent['pagination']['total_count'] : (int)bx_get('start')));

        return echoJson(array('code' => 1, 'message' => MsgBox(_t('_bx_messenger_giphy_gifs_nothing_found'))));
    }

    function actionGetTalkFiles(){
        $iLotId = (int)bx_get('lot_id');
        $iNumber = (int)bx_get('number');
        if (!$iLotId)
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_found'))));

        if (!($iTotal = $this->_oDb->getLotFilesCount($iLotId)))
            return echoJson(array('code' => 0, 'html' => MsgBox(_t('_bx_messenger_txt_msg_no_results'))));

        $sContent = $this->_oTemplate->getTalkFiles($this->_iProfileId, $iLotId, $iNumber);
        return echoJson(array('code' => 0, 'html' => $sContent, 'total' => $iTotal));
    }

    public function serviceGetLotStat($mixedLotId = ''){
        if (is_numeric($mixedLotId) && (int)$mixedLotId)
            $aLotInfo = $this->_oDb->getLotInfoById($mixedLotId);
        else
        {
            $sUrl = $mixedLotId ? $this->getPreparedUrl($mixedLotId) : $this->_oConfig->getPageIdent($mixedLotId);
            $aLotInfo = $this->_oDb->getLotByUrl($sUrl);
        }

        if (empty($aLotInfo))
            return $aLotInfo;

        $CNF = &$this -> _oConfig -> CNF;
        $aMessages = $this->_oDb->getLotJotsCount($aLotInfo[$CNF['FIELD_ID']]);

        return array(
          'title' => $aLotInfo[$CNF['FIELD_TITLE']],
          'created' => $aLotInfo[$CNF['FIELD_ADDED']],
          'participants' => $aLotInfo[$CNF['FIELD_PARTICIPANTS']] ? explode(',', $aLotInfo[$CNF['FIELD_PARTICIPANTS']]) : array(),
          'author' => $aLotInfo[$CNF['FIELD_AUTHOR']],
          'url' => $aLotInfo[$CNF['FIELD_URL']],
          'type' => $aLotInfo[$CNF['FIELD_TYPE']],
          'messages' => array_merge($aMessages, array(
              'total' => array_sum($aMessages)
          ))
        );
    }
}

/** @} */
