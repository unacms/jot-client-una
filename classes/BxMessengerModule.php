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
define('BX_IM_TYPE_BROADCAST', 6);
define('BX_IM_EMPTY_URL', '');
define('BX_IM_EMPTY', 0);

// Attachment types
define('BX_ATT_TYPE_FILES', 'files');
define('BX_ATT_TYPE_FILES_UPLOADING', 'uploading');
define('BX_ATT_TYPE_GIPHY', 'giphy');
define('BX_ATT_TYPE_REPOST', 'repost');
define('BX_ATT_TYPE_REPLY', 'reply');
define('BX_ATT_TYPE_VC', 'vc'); // video conference

// Reactions actions
define('BX_JOT_REACTION_ADD', 'add');
define('BX_JOT_REACTION_REMOVE', 'remove');

// Public jitsi actions
define('BX_JOT_PUBLIC_JITSI_LEAVE', 'leave');
define('BX_JOT_PUBLIC_JITSI_JOIN', 'join');
define('BX_JOT_PUBLIC_JITSI_CLOSE', 'close');

// Notifications types
define('BX_MSG_NTFS_MESSAGE', 'message');
define('BX_MSG_NTFS_MENTION', 'mention');

// Membership actions
define('BX_MSG_ACTION_ADMINISTRATE_MESSAGES', 'administrate_messages');
define('BX_MSG_ACTION_CREATE_TALKS', 'create_talks');
define('BX_MSG_ACTION_ADMINISTRATE_TALKS', 'administrate_talks');
define('BX_MSG_ACTION_SEND_MESSAGE', 'send_messages');
define('BX_MSG_ACTION_CREATE_VC', 'create_vc');
define('BX_MSG_ACTION_CREATE_IM_VC', 'video_conference');
define('BX_MSG_ACTION_VIDEO_RECORDER', 'video_recorder');
define('BX_MSG_ACTION_JOIN_IM_VC', 'join_personal_vc');
define('BX_MSG_ACTION_JOIN_TALK_VC', 'join_vc');

// Lot settings
define('BX_MSG_SETTING_MSG', 'msg'); // allow to send messages
define('BX_MSG_SETTING_GIPHY', 'giphy'); // allow to send giphy
define('BX_MSG_SETTING_FILES', 'files'); // allow to send files
define('BX_MSG_SETTING_VIDEO_RECORD', 'video_rec'); // allow to record videos
define('BX_MSG_SETTING_SMILES', 'smiles'); // allow to record videos

/**
 * Messenger module
 */
class BxMessengerModule extends BxBaseModGeneralModule
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

        $iProfileId = (int)bx_get('profile_id');
        if (!((int)$iProfileId && (int)$iProfileId != (int)$this->_iProfileId && $this->onCheckContact($this->_iProfileId, $iProfileId)))
            $iProfileId = 0;

        $iLotId = BX_IM_EMPTY;
        if (!$iProfileId) {
            if ($this->_iJotId) {
                $iLotId = $this->_oDb->getLotByJotId($this->_iJotId);
                if ($iLotId && !$this->_oDb->isParticipant($iLotId, $this->_iProfileId)) {
                    $this->_iJotId = BX_IM_EMPTY;
                    $iLotId = BX_IM_EMPTY;
                }
            } else {
                $aLotsList = $this->_oDb->getMyLots($this->_iProfileId);
                $iLotId = !empty($aLotsList) ? current($aLotsList)[$this->_oConfig->CNF['FIELD_ID']] : 0;
            }
        }

		$sConfig = $this->_oTemplate->loadConfig($this->_iProfileId, false, $iLotId, $this->_iJotId, $iProfileId);
        return	$sConfig . $this->_oTemplate->getLotsList($iLotId, $this->_iProfileId, $iProfileId);
    }
    /**
     * Returns right side block for messenger page
     */
    public function serviceGetBlockLot()
    {
        if (!$this->isLogged())
            return '';

        $CNF = &$this->_oConfig->CNF;
        if ($iViewedProfileId = (int)bx_get('profile_id')) {
            $oProfile = BxDolProfile::getInstance($iViewedProfileId);
            $sModule = $oProfile->getModule();
            if (BxDolRequest::serviceExists($sModule, 'is_group_profile') && BxDolService::call($sModule, 'is_group_profile')) {
                $aOwnerInfo = BxDolService::call($sModule, 'get_info', array($oProfile->getContentId(), false));
                if(!empty($aOwnerInfo) && is_array($aOwnerInfo) && BxDolService::call($sModule, 'check_allowed_view_for_profile', array($aOwnerInfo)) === CHECK_ACTION_RESULT_ALLOWED) {
                    $oModule = BxDolModule::getInstance($sModule);
                    if ($oModule->_oConfig) {
                        $oMCNF = $oModule->_oConfig->CNF;

                        $sUrl = "i={$oMCNF['URI_VIEW_ENTRY']}&id=" . $oProfile->getContentId();
                        if ($sUrl && $aTalk = $this->_oDb->getLotByUrl($sUrl))
                            return $this->_oTemplate->getTalkBlock($this->_iProfileId, $aTalk[$CNF['FIELD_ID']]);
                    }
                }
            }
            else
            {
                $aExistedTalk = $this->_oDb->getLotByUrlAndParticipantsList(BX_IM_EMPTY_URL, array($iViewedProfileId, $this->_iProfileId));
                if (!empty($aExistedTalk))
                    return $this->_oTemplate->getTalkBlock($this->_iProfileId, $aExistedTalk[$CNF['FIELD_ID']]);
                else if ($this->onCheckContact($this->_iProfileId, $iViewedProfileId))
                    return $this->_oTemplate->getTalkBlockByUserName($this->_iProfileId, $iViewedProfileId);
            }
        }

        if ($this->_iJotId && ($iLotId = $this->_oDb->getLotByJotId($this->_iJotId)))
            return $this->_oTemplate->getTalkBlock($this->_iProfileId, $iLotId, $this->_iJotId);

        $aLotsList = $this -> _oDb -> getMyLots($this->_iProfileId);
        if (!empty($aLotsList))
            return $this->_oTemplate->getTalkBlock($this->_iProfileId, current($aLotsList)[$CNF['FIELD_ID']]);

        return $this->_oTemplate->getCreateTalkForm($this->_iProfileId);
    }

    /**
     * Returns block with messenger for any page
     * @param string $sModule module name
     * @return string block's content
     */
    public function serviceGetBlockMessenger($sModule)
    {
        $CNF = &$this->_oConfig->CNF;

        $this->_oTemplate->loadCssJs('view');
        $aLotInfo = $this->_oDb->getLotByClass($sModule);
        $sUrl = $this->_oConfig->getPageIdent();
        $iType = $this->_oConfig->getTalkType($sModule);
        if (empty($aLotInfo))
            $aLotInfo = $this->_oDb->getLotByUrl($sUrl);

        $iLotId = !empty($aLotInfo) && isset($aLotInfo[$CNF['FIELD_ID']]) ? (int)$aLotInfo[$CNF['FIELD_ID']] : 0;
        if (!$iLotId) {
            $sTalkUrl = $this->getPreparedUrl($sUrl);
            $sTalkTitle = BxDolTemplate::getInstance()->getPageHeader();
            $iLotId = $this->_oDb->createLot($this->_iProfileId, $sTalkUrl, $sTalkTitle, $iType, array($this->_iProfileId));
        }

        $sConfig = $this->_oTemplate->loadConfig($this->_iProfileId, true, $iLotId, BX_IM_EMPTY, BX_IM_EMPTY, $this->_oConfig->getTalkType($sModule));
        $aHeader = $this->_oTemplate->getTalkHeader($iLotId, $this->_iProfileId, true, true);

        $sContent = $this->_oTemplate->parseHtmlByName('talk_body.html', array(
            'history' => $this->_oTemplate->getHistory($this->_iProfileId, $iLotId, BX_IM_EMPTY),
            'text_area' => $this->_oTemplate->getTextArea($this->_iProfileId, $iLotId)
        ));

        $oMenu = BxTemplMenu::getObjectInstance($CNF['OBJECT_MENU_ACTIONS_TALK_MENU']);
        $oMenu->setContentId($iLotId);

        return array(
            'title' => $aHeader['title'],
            'content' => $sConfig . $sContent,
            'menu' => $oMenu
        );
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
     * @param mixed $mixedParticipants participants list
     * @param bool $bExcludeLogged don't add logged profile to the participants list
     * @return array  participants list
     */
    private function getParticipantsList($mixedParticipants, $bExcludeLogged = false)
    {
        if (empty($mixedParticipants))
            return array();
        $aParticipants = is_array($mixedParticipants) ? $mixedParticipants : array(intval($mixedParticipants));

        if (!$bExcludeLogged)
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
        $iType = (int)bx_get('type');
        $iTmpId = bx_get('tmp_id');
        $aFiles = bx_get(BX_ATT_TYPE_FILES);
        $aGiphy = bx_get(BX_ATT_TYPE_GIPHY);
		$iReply = (int)bx_get('reply');
        
		$CNF = &$this->_oConfig->CNF;
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_SEND_MESSAGE, $this->_iProfileId);
        if ($mixedResult !== true)
            return echoJson(array('code' => 1, 'message' => $mixedResult));

        if (!$sMessage && empty($aFiles) && empty($aGiphy))
            return echoJson(array('code' => 2, 'message' => _t('_bx_messenger_send_message_no_data')));

        if ($iLotId) {
            $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
            $iType = $aLotInfo[$CNF['FIELD_TYPE']];
        } else
            $iType = ($iType && $this->_oDb->isLotType($iType)) ? $iType : BX_IM_TYPE_PRIVATE;

        if ($iType !== BX_IM_TYPE_PRIVATE) {
            $sUrl = bx_get('url');
            $sTitle = bx_get('title');
        }

        // prepare participants list
        $aParticipants = $this->getParticipantsList(bx_get('participants'));
        if (!$iLotId && empty($aParticipants) && $iType === BX_IM_TYPE_PRIVATE)
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
                    'member_id' => $this->_iProfileId,
                    'url' => $sUrl,
                    'title' => $sTitle,
                    'lot' => $iLotId,
					'reply' => $iReply
            ), $aParticipants)))
        {
            if (!$iLotId) {
                $aResult['lot_id'] = $this->_oDb->getLotByJotId($iId);
                $aResult['header'] = $this->_oTemplate->getTalkHeader($aResult['lot_id'], $this->_iProfileId);
            }

		    if (!empty($aFiles)) {
                $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
                $aUploadingFilesNames = $aCompleteFilesNames = array();
                foreach ($aFiles as &$aFile) {
                    if (!(int)$aFile['complete']) {
                        $aUploadingFilesNames[] = $aFile['realname'];
                        continue;
                    }

                    $iFile = $oStorage->storeFileFromPath(BX_DIRECTORY_PATH_TMP . $aFile['name'], $iType == BX_IM_TYPE_PRIVATE, $this->_iProfileId, (int)$iId);
                    if ($iFile) {
                        $oStorage->afterUploadCleanup($iFile, $this->_iUserId);
                        $this->_oDb->updateFiles($iFile, array(
                            $CNF['FIELD_ST_JOT'] => $iId,
                            $CNF['FIELD_ST_NAME'] => $aFile['realname']
                        ));
                        $aCompleteFilesNames[] = $aFile['realname'];
                    } else
                        $aResult = array('code' => 2, 'message' => $oStorage->getErrorString());
                }

                if (!empty($aCompleteFilesNames) || !empty($aUploadingFilesNames))
                    $this->_oDb->addAttachment($iId, implode(',', array_merge($aCompleteFilesNames, $aUploadingFilesNames)), !empty($aUploadingFilesNames) ? BX_ATT_TYPE_FILES_UPLOADING : BX_ATT_TYPE_FILES);               
            }

			if (is_array($aGiphy) && !empty($aGiphy))
               $this->_oDb->addAttachment($iId, current($aGiphy), BX_ATT_TYPE_GIPHY);
		   
			if ($iReply)
               $this->_oDb->addAttachment($iId, $iReply, BX_ATT_TYPE_REPLY);

            $aResult['jot_id'] = $iId;
			$aJot = $this->_oDb->getJotById($iId);
			if (!empty($aJot))
                $aResult['time'] = bx_time_utc($aJot[$CNF['FIELD_MESSAGE_ADDED']]);

            $this->onSendJot($iId);
        }
		else
            $aResult = array('code' => 2, 'message' => _t('_bx_messenger_send_message_save_error'));

        BxDolSession::getInstance()->exists($this->_iProfileId);
        echoJson($aResult);
    }

    /**
     * Loads talk to the right side block when member choose conversation or when open messenger page
     * @return array with json result
     */
	public function actionLoadTalk(){
        $iLotId = (int)bx_get('lot_id');
        $iJotId = (int)bx_get('jot_id');

        if (!$this->isLogged() || !$iLotId || !$this->_oDb->isParticipant($iLotId, $this->_iProfileId)) {
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged')), 'reload' => 1));
        };

        if ((int)bx_get('mark_as_read'))
            $this->_oDb->readAllMessages($iLotId, $this->_iProfileId);

        $CNF = &$this->_oConfig->CNF;
        $aUnreadJots = $this->_oDb->getNewJots($this->_iProfileId, $iLotId);
        $iUnreadLotsJots = !empty($aUnreadJots) ? (int)$aUnreadJots[$CNF['FIELD_NEW_UNREAD']] : 0;
        $iLastUnreadJot = !empty($aUnreadJots) ? (int)$aUnreadJots[$CNF['FIELD_NEW_JOT']] : 0;

        $sHeader = $this->_oTemplate->getTalkHeader($iLotId, $this->_iProfileId);
        $sHistory = $this->_oTemplate->getHistoryArea($this->_iProfileId, $iLotId, $iJotId ? $iJotId : $iLastUnreadJot, $iUnreadLotsJots && $iUnreadLotsJots < ($CNF['MAX_JOTS_BY_DEFAULT']/2));
        $sTextArea = $this->_oTemplate->getTextArea($this->_iProfileId, $iLotId);
        $aVars = array(
            'code' => 0,
            'header' => $sHeader,
            'history' => $sHistory,
            'text_area' => $sTextArea,
            'last_unread_jot' => $iLastUnreadJot,
            'unread_jots' => $iUnreadLotsJots,
            'muted' => (int)$this->_oDb->isMuted($iLotId, $this->_iProfileId)
        );

        BxDolSession::getInstance()->exists($this->_iProfileId);
        echoJson($aVars);
    }

    public function actionMarkJotsAsRead(){
        $iId = (int)bx_get('lot');
        if (!$this->isLogged() || !$this->_oDb->isParticipant($iId, $this->_iUserId)) {
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged')), 'reload' => 1));
        };

        $this->_oDb->readAllMessages($iId, $this->_iUserId);
        echoJson(array('code' => 0));
    }

    public function actionViewedJot(){
        $iJotId = (int)bx_get('jot_id');
        $aJotInfo = $this->_oDb->getJotById($iJotId);
        $aLotInfo = $this->_oDb->getLotByJotId($iJotId, false);
        $CNF = &$this->_oConfig->CNF;

        if (empty($aJotInfo) || !$this->isLogged() || ($aLotInfo[$CNF['FIELD_TYPE']] === BX_IM_TYPE_PRIVATE && !$this->_oDb->isParticipant($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId)))
            return echoJson(array('code' => 1));

        $this->_oDb->readMessage($iJotId, $this->_iProfileId);
        $this->_oDb->markNotificationAsRead($this->_iProfileId, $aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
        $aUnreadInfo = $this->_oDb->getNewJots($this->_iProfileId, $aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
        $iUnreadJotsNumber = $iLastUnreadJotId = 0;
        if (!empty($aUnreadInfo)) {
            $iLastUnreadJotId = (int)$aUnreadInfo[$CNF['FIELD_NEW_JOT']];
            $iUnreadJotsNumber = (int)$aUnreadInfo[$CNF['FIELD_NEW_UNREAD']];
        }

        echoJson(array('code' => 0, 'unread_jots' => $iUnreadJotsNumber, 'last_unread_jot' => $iLastUnreadJotId));
    }

    /**
     * Loads messages for specified lot(conversation)
     * @return array with json
     */
	public function actionLoadJots(){
        $iId = (int)bx_get('id');
	    if (!$this->isLogged() || !$iId)
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged'))));

	    if (!$this->_oDb->isParticipant($iId, $this->_iProfileId))
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_participant'))));

        $sContent = $this->_oTemplate->getHistoryArea($this->_iProfileId, (int)$iId ? $iId : BX_IM_EMPTY);
        echoJson(array('code' => 0, 'history' => $sContent));
    }

    /**
     * Search for Lots by keywords in the right side block
     * @return string with json
     */
	public function actionSearch(){	   
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged')), 'reload' => 1));

        $sParam = bx_get('param');
        $iStarred = bx_get('starred');
        $aParams = array('term' => $sParam, 'star' => (bool)$iStarred);
        $aMyLots = $this->_oDb->getMyLots($this->_iUserId, $aParams);
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
            return echoJson(array('code' => 1, 'html' => MsgBox(_t('_bx_messenger_not_logged')), 'reload' => 1));

        $iLotId = (int)bx_get('lot_id');
        if (!$iLotId || !$this->_oDb->isParticipant($iLotId, $this->_iProfileId))
            return echoJson(array('code' => 1));

        if ($aLots = $this->_oDb->getLotInfoById($iLotId))
	        return echoJson(array(
									'code' => 0,
									'html' => $this->_oTemplate->getLotsPreview($this->_iProfileId, array($aLots)),
									'muted'=> (int)$this->_oDb->isMuted($iLotId, $this->_iProfileId)
								));

        echoJson(array('code' => 1));
    }

    /**
     * Prepare url for Lot title if was created on separated page
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

        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $sUrl = bx_get('url');
        if ($sUrl)
            $sUrl = $this->getPreparedUrl($sUrl);

        $iJot = (int)bx_get('jot');
        $iLotId = (int)bx_get('lot');
        $sLoad = bx_get('load');
        $isFocused = (bool)bx_get('focus');
        $iRequestedJot = (int)bx_get('req_jot');
        $iLastViewedJot = (int)bx_get('last_viewed_jot');
        $bUpdateHistory = true;
        $sContent = '';
        $iUnreadJotsNumber = 0;
        $iLastUnreadJotId = 0;
        $bAttach = true;
        $bRemoveSeparator = false;
		switch($sLoad)
		{
		    case 'new':
                if (!$iJot)
                {
                    $iJot = $this->_oDb->getFirstUnreadJot($this->_iProfileId, $iLotId);
                    if ($iJot)
                        $iJot = $this->_oDb->getPrevJot($iLotId, $iJot);
                    else
                    {
                        $aLatestJot = $this->_oDb->getLatestJot($iLotId);
                        $iJot = !empty($aLatestJot) ? (int)$aLatestJot[$CNF['FIELD_MESSAGE_ID']] : 0;
                    }
                }

                if ($iRequestedJot) {
                    if ($this->_oDb->getJotsNumber($iLotId, $iJot, $iRequestedJot) >= $CNF['MAX_JOTS_LOAD_HISTORY'])
                        $bUpdateHistory = false;
                    else if ($isFocused)
                        $this->_oDb->readMessage($iRequestedJot, $this->_iProfileId);
                }

                if ($iLastViewedJot && $bUpdateHistory && $isFocused) {
                    $this->_oDb->readMessage($iLastViewedJot, $this->_iProfileId);
                }

                $bAttach = $iJot ? $this->_oDb->getJotsNumber($iLotId, $iJot) < $CNF['MAX_JOTS_LOAD_HISTORY'] : false;
            case 'all':
            case 'prev':
                $aOptions = array(
                    'lot_id' => $iLotId,
                    'url' => $sUrl,
                    'start' => $iJot,
                    'load' => $sLoad,
                    'limit' => $CNF['MAX_JOTS_LOAD_HISTORY'],
                    'views' => true,
                    'dynamic' => true
                );

                $iLastUnreadJotId = $iUnreadJotsNumber = 0;
                $aUnreadInfo = $this->_oDb->getNewJots($this->_iProfileId, $iLotId);
                if (!empty($aUnreadInfo)) {
                    $iLastUnreadJotId = (int)$aUnreadInfo[$CNF['FIELD_NEW_JOT']];
                    $iUnreadJotsNumber = (int)$aUnreadInfo[$CNF['FIELD_NEW_UNREAD']];
                }

                $sContent = '';
                if ($bUpdateHistory){
                   $aJots = $this->_oTemplate->getJotsOfLot($this->_iProfileId, $aOptions);
                   $sContent = $aJots['content'];

                    if (isset($aJots['first_jot']) && $iJot) {
                        $aJotInfo = $this->_oDb->getJotById($iJot);
                        $iFDate = strtotime(date("Y-m-d", $aJots['first_jot'][$CNF['FIELD_MESSAGE_ADDED']]));
                        $iSDate = strtotime(date("Y-m-d", $aJotInfo[$CNF['FIELD_MESSAGE_ADDED']]));
                        $bRemoveSeparator = +($iFDate == $iSDate);
                    }
                }
                
                break;
            case 'edit':
                  $aJotInfo = $this->_oDb->getJotById($iJot);
                  $sContent = $aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE']];
                break;
            case 'vc':
            case 'delete':
                  $sContent = $this->_oTemplate->getMessageIcons($iJot, $sLoad, $this->_oDb->isAuthor($iLotId, $this->_iProfileId) || isAdmin());
                break;
            case 'check_viewed':
                if ($iLotId && $iJot)
                    $sContent = $this->_oTemplate->getViewedJotProfiles($iJot, $this->_iProfileId);
                break;

            case 'reaction':
                if ($iJot) {
                    $aJotInfo = $this->_oDb->getJotById($iJot);
                    if (!empty($aJotInfo))
                        $sContent = $this->_oTemplate->getJotReactions($iJot);
                }
            break;
        }

        $aResult = array(
                            'code' => 0,
                            'html' => $sContent,
                            'unread_jots' => $iUnreadJotsNumber,
                            'last_unread_jot' => $iLastUnreadJotId,
                            'allow_attach' => +$bAttach,
                            'remove_separator' => +$bRemoveSeparator
                        );

        // update session
        if ($this->_iProfileId)
            BxDolSession::getInstance()->exists($this->_iProfileId);

        echoJson($aResult);
    }

    /**
     * Occurs when member wants to create new conversation(lot)
     * @return array with json
     */
	public function actionCreateLot(){
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_CREATE_TALKS, $this->_iProfileId);
        if ($mixedResult !== true)
            return echoJson(array('code' => 1, 'message' => $mixedResult));

        $iProfileId = (int)bx_get('profile');
        $sHeader = '';
        if ($iProfileId)
        {
            $aLotInfo = $this->_oDb->getLotByUrlAndParticipantsList(BX_IM_EMPTY_URL, array($this->_iProfileId, $iProfileId));
            $iLotId = empty($aLotInfo) ? BX_IM_EMPTY : $aLotInfo[$this -> _oConfig -> CNF['FIELD_ID']];
            $sHeader = $this->_oTemplate->getTalkHeaderForUsername($this->_iProfileId, $iProfileId);
        } else
            $iLotId = (int)bx_get('lot');

        if (!$sHeader)
            $sHeader = $this->_oTemplate->getEditTalkArea($iProfileId, $iLotId);

        echoJson(
            array(
                 'title' => _t('_bx_messenger_lots_menu_create_lot_title'),
                 'history' => $this->_oTemplate->getHistoryArea($this->_iProfileId, $iLotId),
                 'header' => $sHeader,
                 'text_area' => $this->_oTemplate->getTextArea($this->_iProfileId, $iLotId),
                 'code' => 0
            ));
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
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $aResult = $aExcept = array();
        $sExcept = bx_get('except');
	    if ($sExcept)
            $aExcept = explode(',', $sExcept);

	    $aUsers = $this->searchProfiles(bx_get('term'), $aExcept,  $this->_oConfig->CNF['PARAM_SEARCH_DEFAULT_USERS']);
	    if (empty($aUsers))
            return echoJson(array('items' => $aUsers));

        foreach ($aUsers as &$aValue) {
            if (!$this->onCheckContact($this->_iUserId, $aValue['value']))
                continue;

            if (!($oProfile = BxDolProfile::getInstance($aValue['value'])))
                continue;

            $aProfileInfo = $oProfile->getInfo();
            $aProfileInfoDetails = BxDolService::call($aProfileInfo['type'], 'get_content_info_by_id', array($aProfileInfo['content_id']));
            $oAccountInfo = BxDolAccount::getInstance($aProfileInfo['account_id']);

            $sThumb = $oProfile->getThumb();
            $bThumb = stripos($sThumb, 'no-picture') === FALSE;
            $sDisplayName = $oProfile->getDisplayName();

            if (!empty($aProfileInfoDetails) && !empty($oAccountInfo)) {
                $aResult[$aProfileInfo['type']]['results'][] = array(
                    'value' => $sDisplayName,
                    'icon' => $bThumb ? $sThumb : '',
                    'color' => implode(', ', BxDolTemplate::getColorCode($aValue['value'], 1.0)),
                    'letter' => mb_substr($sDisplayName, 0, 1),
                    'id' => $oProfile->id(),
                    'profile_url' => $oProfile->getUrl(),
                    'description' => _t('_bx_messenger_search_desc',
                        bx_process_output($oAccountInfo->getInfo()['logged'], BX_DATA_DATE_TS),
                        bx_process_output($aProfileInfoDetails['added'], BX_DATA_DATE_TS))
                );
            }
        }

        foreach($aResult as $sKey => $aValues){
            $aResult[$sKey]['name'] =_t("_{$sKey}");
        }

        echoJson(array('results' => $aResult));
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
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $aParticipants = $this->getParticipantsList(bx_get('participants'));

        $aResult = array('lot' => 0);
        if (!empty($aParticipants) && ($aChat = $this->_oDb->getLotByUrlAndParticipantsList(BX_IM_EMPTY_URL, $aParticipants, BX_IM_TYPE_PRIVATE)))
            $aResult['lot'] = $aChat[$this->_oConfig->CNF['FIELD_ID']];

        echoJson($aResult);
    }

    /**
     * Updats participants list (occurs when create new lost with specified participants or update already existed list)
     * @return string with json
     */
    public function actionSaveLotsParts()
    {
        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_logged'), 'reload' => 1));

        $iLotId = bx_get('lot');
        $bIsBlockVersion = +bx_get('is_block');
        $aParticipants = $this->getParticipantsList(bx_get('participants'), true);
        $aResult = array('message' => _t('_bx_messenger_save_part_failed'), 'code' => 1);

        $bCheckAction = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $this->_iProfileId) === true;
        if (($iLotId && !($this->_oDb->isAuthor($iLotId, $this->_iProfileId) || $bCheckAction)) || empty($aParticipants))
            return echoJson($aResult);

        if (!$iLotId) {
            $aLot = $this->_oDb->getLotByUrlAndParticipantsList(BX_IM_EMPTY_URL, $this->getParticipantsList(bx_get('participants')), BX_IM_TYPE_PRIVATE);
            if (!empty($aLot)) {
                if ($iLotId && $iLotId !== $aLot[$this->_oConfig->CNF['FIELD_ID']])
                    return echoJson(array('message' => _t('_bx_messenger_lot_parts_error'), 'code' => 1, 'lot' => $iLotId));

                $iLotId = $aLot[$this->_oConfig->CNF['FIELD_ID']];
            }
        }

        $oOriginalParts = array();
        $aResult = array('message' => _t('_bx_messenger_save_part_success'), 'code' => 0);
		if (!$iLotId)
		{
            $iLotId = $this->_oDb->createNewLot($this->_iProfileId, BX_IM_EMPTY_URL, BX_IM_TYPE_PRIVATE, BX_IM_EMPTY_URL, $this->getParticipantsList(bx_get('participants')));
            $aResult['lot'] = $iLotId;
            $this->onCreateLot($iLotId);
		}
		else
        {
            $oOriginalParts = $this->_oDb->getParticipantsList($iLotId);
            if (!$this->_oDb->saveParticipantsList($iLotId, $aParticipants))
                $aResult = array('code' => 2);
        }

        $aRemoveParticipants = array_diff($oOriginalParts, $aParticipants);
        $aNewParticipants = array_diff($aParticipants, $oOriginalParts);

        foreach ($aNewParticipants as &$iPartId)
            $this->onAddNewParticipant($iLotId, $iPartId);

        foreach ($aRemoveParticipants as &$iPartId) {
            $this->_oDb->deleteNewJot($iPartId, $iLotId);
            $this->onRemoveParticipant($iLotId, $iPartId);
        }

        if ($iLotId) {
            $aHeader = $this->_oTemplate->getTalkHeader($iLotId, $this->_iProfileId, $bIsBlockVersion, true);
            $aResult['header'] = $aHeader['title'];
        }

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
        $CNF = &$this->_oConfig->CNF;

        if ($this->_oDb->deleteLot($iLotId)) {
            $aResult = array('code' => 0);
            $this->onDeleteLot($iLotId, $aLotInfo[$CNF['FIELD_AUTHOR']]);
        }

        echoJson($aResult);
    }

    /**
     * Clear specified lot
     * @return string with json
     */
    public function actionClearHistory()
    {
        $iLotId = bx_get('lot');
        $aResult = array('code' => 1, 'message' => _t('_Empty'));
        $bAllowed = $this->_oDb->isAuthor($iLotId, $this->_iProfileId) || ($this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $this->_iProfileId) === true);
        if (!$iLotId || !$bAllowed) {
            return echoJson($aResult);
        }

        if ($this->_oDb->clearLot($iLotId))
            $aResult = array('code' => 0);

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

        $bIsAllowedToDelete = $this->_oDb->isAllowedToDeleteJot($iJotId, $this->_iProfileId, $aJotInfo[$CNF['FIELD_MESSAGE_AUTHOR']]);
        if (!$bIsAllowedToDelete)
            return echoJson($aResult);

        $bIsLotAuthor = $this->_oDb->isAuthor($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId);
        $bDelete = $bCompletely || $CNF['REMOVE_MESSAGE_IMMEDIATELY'];
        if ($this->_oDb->deleteJot($iJotId, $this->_iUserId, $bDelete)){
            if ($bDelete)
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

        $aLotInfo = $this->_oDb->getLotInfoById($aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
        if (!$this->_oDb->isParticipant($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId) && $aLotInfo[$CNF['FIELD_TYPE']] !== BX_IM_TYPE_PRIVATE)
            $this->_oDb->addMemberToParticipantsList($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId);

        if ($sAction == BX_JOT_REACTION_ADD && $this->_oDb->addJotReaction($iJotId, $this->_iProfileId, $aEmoji)) {
            $this->onReactJot($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $iJotId, $aLotInfo[$CNF['FIELD_AUTHOR']], $sAction);
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
        $aLotInfo = $this->_oDb->getLotInfoById($aJotInfo[$CNF['FIELD_MESSAGE_FK']]);
        if (!$bIsParticipant && $sAction == BX_JOT_REACTION_ADD && $aLotInfo[$CNF['FIELD_TYPE']] == BX_IM_TYPE_PRIVATE)
            return echoJson($aResult);

        if ($this->_oDb->updateReaction($iJotId, $this->_iProfileId, $sEmoji, $sAction)){
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
        if (empty($aJotInfo) || !(isAdmin() || $this->_oDb->isAuthor($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iProfileId)))
            return echoJson(array('code' => 1));

        $aResult = array('code' => 0, 'html' => $this->_oTemplate->getJotsBody($iJotId));
        echoJson($aResult);
    }

  /**
       * Get body of the jot
      * @return string with json
     */
    public function actionGetJotPreview()
    {
        $iJotId = bx_get('jot');
        $aJotInfo = $this->_oDb->getJotById($iJotId);
        if (empty($aJotInfo) || !(isAdmin() || $this->_oDb->isAuthor($aJotInfo[$this->_oConfig->CNF['FIELD_MESSAGE_FK']], $this->_iProfileId)))
            return echoJson(array('code' => 1));

        $aResult = array('code' => 0, 'html' => $this->_oTemplate->getReplyPreview($iJotId));
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

        $mixedResult = $this->_oDb->isAllowedToDeleteJot($iJotId, $this->_iProfileId);
        if (empty($aJotInfo) || $mixedResult !== true)
            return echoJson($aResult);

        $aResult = array('code' => 0, 'html' => $this->_oTemplate->getEditJotArea($iJotId));
        echoJson($aResult);
    }

    public function actionEditJot()
    {
        $iJotId = bx_get('jot');
        $aJotInfo = $this->_oDb->getJotById($iJotId);
        $aResult = array('code' => 1);

        $sMessage = preg_replace(array('/\<p>/i', '/\<\/p>/i'), array("", "<br/>"), bx_get('message'));
        $mixedResult = $this->_oDb->isAllowedToDeleteJot($iJotId, $this->_iProfileId);
        if (empty($aJotInfo) || $mixedResult !== true)
            return echoJson($aResult);

        if ($this->_oDb->editJot($iJotId, $this->_iProfileId, $sMessage)) {
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

        if ($iLotId) {
            $bMuted = $this->_oDb->muteLot($iLotId, $this->_iUserId);
            return echoJson(array('code' => $bMuted, 'title' => $bMuted ? _t('_bx_messenger_lots_menu_mute_info_on') : _t('_bx_messenger_lots_menu_mute_info_off')));
        }

        return echoJson(array('code' => 1));
    }

    /**
     * Mark lot with star
     * @return string with json
     */
    public function actionStar()
    {
        $iLotId = bx_get('lot');
        if ($iLotId) {
            $bStar = $this->_oDb->starLot($iLotId, $this->_iProfileId);
            return echoJson(array('code' => $bStar, 'title' => !$bStar ? _t('_bx_messenger_lots_menu_star_on') : _t('_bx_messenger_lots_menu_star_off')));
        }

        return echoJson(array('code' => 1));
    }

    /**
     * Returns number of lots with at least one unread message
     * @return int
     */
    public function serviceGetUpdatedLotsNum($iProfileId = 0)
    {
        if (!$this->isLogged())
            return array();

       $aLots = $this->_oDb->getLotsWithUnreadMessages($iProfileId ? $iProfileId : $this->_iProfileId);
       return sizeof($aLots);
    }

    private function sendMessageNotification($iLotId, $iJotId){
        $aReceived = array();
        if (!$iLotId || !$iJotId)
            return false;

        $CNF = &$this->_oConfig->CNF;
        $aJot = $this->_oDb->getJotById($iJotId);
        $sMessage = $aJot[$this->_oConfig->CNF['FIELD_MESSAGE']];
        if ($sMessage){
            $sMessage = preg_replace('/<br\s?\/?>/i', "\r\n", $sMessage);
            $sMessage = html2txt($sMessage);
            $sMessage = get_mb_substr($sMessage, 0, (int)$CNF['PARAM_PUSH_NOTIFICATIONS_DEFAULT_SYMBOLS_NUM']);

            // find mentions in the message and send notifications
            if ($CNF['USE_MENTIONS'] && preg_match_all('/<a[^>]*class="bx-mention[^"]*"[^>]*data-id="(\d+)".*?<\/a>/i', $aJot[$CNF['FIELD_MESSAGE']],$aMatches)) {
                list(, $aMentionedProfiles) = $aMatches;
                if ($aMentionedProfiles) {
                    $this->sendNotification($iLotId, $iJotId, $sMessage, $aReceived, $aMentionedProfiles, BX_MSG_NTFS_MENTION);
                    $aReceived = array_diff($aReceived, $aMentionedProfiles);
                }
            }
        }
        else
            if ($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
                $sMessage = _t('_bx_messenger_attached_files_message', $this->_oDb->getJotFiles($aJot[$CNF['FIELD_MESSAGE_ID']], true));

        return $this->sendNotification($iLotId, $iJotId, $sMessage, $aReceived);
    }

    private function sendNotification($iLotId, $iJotId, $sMessage, $aReceived = array(), $aRecipients = array(), $sType = BX_MSG_NTFS_MESSAGE){
        if (!$sMessage)
            return false;

        // check if the Notifications module is installed and send notifications through it
        if ($this->_oDb->isModuleByName('bx_notifications'))
            return $this->sendNotifications($iLotId, $iJotId, $aReceived, $aRecipients, $sType);

        if (!$this->_oConfig->isOneSignalEnabled())
            return false;

        $aParticipantList = $this->_oDb->getParticipantsList($iLotId, true);
        if (empty($aParticipantList) || !in_array($this->_iProfileId, $aParticipantList))
            return false;

        // use own push notifications ability
        $oLanguage = BxDolStudioLanguagesUtils::getInstance();
        $sLanguage = $oLanguage->getCurrentLangName(false);

        $aContent = array(
            'en' => $sMessage,
        );

        if (!$aContent[$sLanguage])
            $aContent[$sLanguage] = $sMessage;

        $oProfile = BxDolProfile::getInstance($this->_iProfileId);
        if ($oProfile)
            $aHeadings = array(
                $sLanguage => _t("_bx_messenger_notification_subject_{$sType}", $oProfile->getDisplayName())
            );
        else
            return false;

        $aInfo = array(
            'contents' => $aContent,
            'headings' => $aHeadings,
            'url' => $this->_oConfig->getRepostUrl($iJotId)
        );

        $aRecipients = empty($aRecipients) ? $aParticipantList : $aRecipients;
        $iKey = array_search($this->_iProfileId, $aRecipients);
        if (isset($aParticipantList[$iKey]))
            unset($aParticipantList[$iKey]);

        $aWhere = array();
        foreach($aRecipients as &$iValue)
        {
            if (array_search($iValue, $aReceived) !== FALSE || $this->_oDb->isMuted($iLotId, $iValue))
                continue;

            $aWhere[] = array("field" => "tag", "key" => "user", "relation" => "=", "value" => $iValue);
            $aWhere[] = array("operator" => "OR");
        }

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
        $sTemplate =  '';
        if ($this->isLogged())
            $sTemplate = $this->_oTemplate->getMembersJotTemplate($this->_iProfileId);

        echoJson(
            array(
                'data' => $sTemplate
            ));
    }

    /**
     * Delete all content by profile ID
     * @param object oAlert
     * @return boolean
     */
    public function serviceDeleteHistoryByAuthor($oAlert)
    {
        return $oAlert->iObject && !empty($oAlert->aExtras['delete_with_content']) ?
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

                $aFiles = array_filter($_FILES, function(&$aFile, $sName) use ($CNF) {
                    return stripos($sName, $CNF['FILES_UPLOADER']) !== FALSE;
                }, ARRAY_FILTER_USE_BOTH);

                $aUploader = current($aFiles);
                if (!empty($aUploader) && $oStorage->isValidFileExt($aUploader['name'])) {
                    $sTempFile = $aUploader['tmp_name'];
                    $sTargetFile = BX_DIRECTORY_PATH_TMP . $aUploader['name'];
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
        if (!$iFileId || !$this->_iProfileId)
            return '';

        $CNF = &$this->_oConfig->CNF;
        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        $aFile = $oStorage->getFile((int)$iFileId);
        if (empty($aFile)){
            echo _t('_bx_messenger_post_file_not_found');
            exit;
        }

        if (isset($aFile[$CNF['FIELD_ST_JOT']])){
            $iLotId = $this->_oDb->getLotByJotId($aFile[$CNF['FIELD_ST_JOT']], true);
            if (!$iLotId || !$this->_oDb->isParticipant($iLotId, $this->_iProfileId)) {
                echo _t('_bx_messenger_not_participant');
                exit;
            }
        }

        $sToken = $oStorage->genToken($iFileId);
        return $oStorage->download($aFile[$CNF['FIELD_ST_REMOTE']], $sToken);
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
        if (!$iStorageId)
            return '';

        $iWidth = (int)$iWidth * 0.9;
        $iHeight = (int)$iHeight * 0.9;

        $oStorage = BxDolStorage::getObjectInstance($this->_oConfig->CNF['OBJECT_STORAGE']);
        $aFile = $oStorage->getFile((int)$iStorageId);

        $sFileUrl = $oStorage->getFileUrlById($iStorageId);
    	if (!empty($aFile))
		{
            $aInfo = getimagesize($sFileUrl);
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
                array('group' => $sModule, 'type' => 'delete', 'alert_unit' => $sModule, 'alert_action' => 'delete_jot_ntfs'),
                array('group' => "{$sModule}_mention", 'type' => 'insert', 'alert_unit' => $sModule, 'alert_action' => 'got_mention_ntfs', 'module_name' => $sModule, 'module_method' => 'get_message_content', 'module_class' => 'Module'),
                array('group' => "{$sModule}_mention", 'type' => 'delete', 'alert_unit' => $sModule, 'alert_action' => 'delete_mention_ntfs')
            ),
            'settings' => array(
                array('group' => $sModule, 'unit' => $sModule, 'action' => 'got_jot_ntfs', 'types' => array('personal')),
                array('group' => "{$sModule}_mention", 'unit' => $sModule, 'action' => 'got_mention_ntfs', 'types' => array('personal'))
            ),
            'alerts' => array(
                array('unit' => $sModule, 'action' => 'got_jot_ntfs'),
                array('unit' => $sModule, 'action' => 'delete_jot_ntfs'),
                array('unit' => "{$sModule}_mention", 'action' => 'got_mention_ntfs'),
                array('unit' => "{$sModule}_mention", 'action' => 'delete_mention_ntfs')
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

        $sType = BX_MSG_NTFS_MESSAGE;
        if ($aEvent['action'] === 'got_mention_ntfs')
            $sType = BX_MSG_NTFS_MENTION;

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
                ? '_bx_messenger_txt_subobject_added_single' : "_bx_messenger_txt_subobject_added_{$sType}"
        );

        list($iNumber) = explode('.', bx_get_ver());
        if ((int)$iNumber > 10){
            $sSubject = _t("_bx_messenger_notification_subject_{$sType}", BxDolProfile::getInstanceMagic($aEvent['owner_id'])->getDisplayName());

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
     * @param array $aDataEntry contains Lot details
     * @param boolean $isPerformAction used for compatibility with parent method
     * @param bool $iProfileId profile id of the member show has to get notification
     * @return int
     */

    public function serviceCheckAllowedViewForProfile ($aDataEntry, $isPerformAction = false, $iProfileId = false){
        if (!$iProfileId)
            $iProfileId = $this->_iProfileId;

        $mixedResult = null;

        bx_alert('system', 'check_allowed_view', 0, 0, array(
            'module' => $this->getName(),
            'content_info' => $aDataEntry,
            'profile_id' => $iProfileId,
            'override_result' => &$mixedResult)
        );

        if($mixedResult !== null)
            return $mixedResult;

        $CNF = &$this->_oConfig->CNF;
        if (empty($aDataEntry))
            return CHECK_ACTION_RESULT_ALLOWED;

        return $this->_oDb->isParticipant($aDataEntry[$CNF['FIELD_ID']], $iProfileId, true) === TRUE
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

        foreach ($aPartList as &$iPart)
            bx_alert($this->_oConfig->getObject('alert'), 'got_jot', $iLotId, $this->_iUserId, array('recipient_id' => $iPart, 'subobject_id' => $iJotId));

        if (!$this->_oDb->getIntervalJotsCount($iLotId, $iJotId))
            $this->sendMessageNotification($iLotId, $iJotId);
    }

    public function sendNotifications($iLotId, $iJotId, $aOnlineUsers = array(), $aRecipients = array(), $sType = BX_MSG_NTFS_MESSAGE)
    {
        $CNF = &$this->_oConfig->CNF;
        $aPartList = $this->_oDb->getParticipantsList($iLotId, true, $this->_iProfileId);
        if (empty($aPartList))
            return false;

        if (!empty($aRecipients)){
            $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
            $aDiff = array_diff($aRecipients, $aPartList);

            if (!empty($aDiff) && (int)$aLotInfo[$CNF['FIELD_TYPE']] === BX_IM_TYPE_PRIVATE) // in case if the mentioned profiles are not in the participants list
                $aPartList = array_diff($aRecipients, $aDiff);
            else
                $aPartList = $aRecipients;
        }

        $bIsMention = ($sType == BX_MSG_NTFS_MENTION);
        foreach ($aPartList as &$iPart) {
            if (array_search($iPart, $aOnlineUsers) !== FALSE || $this->_oDb->isMuted($iLotId, $iPart))
                    continue;

            $iCount = $this->_oDb->getSentNtfsNumber($iPart, $iLotId);
            if ($iCount >= (int)$CNF['MAX_NTFS_NUMBER'] && !$bIsMention)
                continue;

            bx_alert($this->_oConfig->getObject('alert'), !$bIsMention ? 'got_jot_ntfs' : 'got_mention_ntfs', $iLotId, $this->_iProfileId, array(
                'object_author_id' => $iPart,
                'recipient_id' => $iPart,
                'subobject_id' => $iJotId
            ));
        }

        return true;
    }

    public function onDeleteJot($iLotId, $iJotId, $iProfileId = 0)
    {
        if (!$iProfileId)
            $iProfileId = $this->_iProfileId;

        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot', $iJotId, $this->_iProfileId, array('author_id' => $iProfileId, 'lot_id' => $iLotId));
        bx_alert($this->_oConfig->getObject('alert'), 'delete_jot_ntfs', $iLotId, $iProfileId, array('subobject_id' => $iJotId));
        bx_alert($this->_oConfig->getObject('alert'), 'delete_mention_ntfs', $iLotId, $iProfileId, array('subobject_id' => $iJotId));
    }

    public function onReactJot($iLotId, $iJotId, $iAuthor, $sAction = 'add')
    {
        bx_alert($this->_oConfig->getObject('alert'), "reaction_" . $sAction, $iJotId, $this->_iProfileId, array('author_id' => $iAuthor, 'lot_id' => $iLotId));
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
        $CNF = &$this->_oConfig->CNF;

        $oSenderProfile = BxDolProfile::getInstance($iSender);
        $oRecipientProfile = BxDolProfile::getInstance($iRecipient);
        if ($CNF['CONTACT-JOIN-ORGANIZATION'] && $oSenderProfile && $oRecipientProfile){
            $aSenderProfileInfo = $oSenderProfile->getInfo();
            $aRecipientProfileInfo = $oRecipientProfile->getInfo();

            if ($aSenderProfileInfo['type'] === 'bx_organizations' && $aRecipientProfileInfo['type'] != 'bx_organizations')
                return BxDolConnection::getObjectInstance("bx_organizations_fans")->isConnected($iRecipient, $iSender, true);
        }

       if ($CNF['DISABLE-PROFILE-PRIVACY'])
            return true;

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
        if (!$this->_iProfileId)
            return false;

        $oProfile = BxDolProfile::getInstance($mixedObject);
        $sModule = $oProfile->getModule();
        if (BxDolRequest::serviceExists($sModule, 'is_group_profile') && BxDolService::call($sModule, 'is_group_profile')) {
            $aOwnerInfo = BxDolService::call($sModule, 'get_info', array($oProfile->getContentId(), false));
            if(empty($aOwnerInfo) || !is_array($aOwnerInfo))
                return CHECK_ACTION_RESULT_ALLOWED;

            return BxDolService::call($sModule, 'check_allowed_view_for_profile', array($aOwnerInfo)) === CHECK_ACTION_RESULT_ALLOWED;
        }

        return $this->onCheckContact($this->_iProfileId, (int)$mixedObject);
    }

    public function serviceIsVideoConferenceAllowed($mixedObject)
    {
       if (!$this->_iProfileId || !$this->onCheckContact($this->_iProfileId, (int)$mixedObject))
            return false;

       $CNF = &$this->_oConfig->CNF;
       if (isset($_SERVER['REQUEST_URI'])) {
            $sRoom = $this->_oConfig->getRoomId($_SERVER['REQUEST_URI']);
            $aRoom = $this->_oDb->getPublicVideoRoom($sRoom);
            $this->_oConfig->isAllowedAction(BX_MSG_ACTION_CREATE_IM_VC, $this->_iProfileId);
            if ((empty($aRoom) || !(int)$aRoom[$CNF['FPJVC_STATUS']]) && $this->_oConfig->isAllowedAction(BX_MSG_ACTION_CREATE_IM_VC, $this->_iProfileId) !== true)
                return false;

            if (!empty($aRoom) && (int)$aRoom[$CNF['FPJVC_STATUS']] && (int)$mixedObject !== $this->_iProfileId && $this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_IM_VC, $this->_iProfileId) !== true)
                return false;
       } else
           return false;

        $this->_oTemplate->addCss(array('video-conference.css'));
        $this->_oTemplate->addJs('messenger-public-lib.js');
        return true;
    }

    private function sendProfilePush($iObjectId, $iSenderId, $iReceiverId, $sType = 'bx_persons'){
        $oLanguage = BxDolStudioLanguagesUtils::getInstance();
        $sLanguage = $oLanguage->getCurrentLangName(false);

        $oObject = BxDolProfile::getInstance($iObjectId);
        if (!$oObject)
            return false;

        $oReceiver = BxDolProfile::getInstance($iReceiverId);
        if (!$oReceiver)
            return false;

        $oSender = BxDolProfile::getInstance($iSenderId);
        if (!$oSender)
            return false;

        $sMessage = _t("_bx_messenger_public_vc_{$sType}_message", $sType === 'bx_persons' ? $oSender->getDisplayName() : $oObject->getDisplayName());

        $aContent = array(
          'en' => $sMessage,
        );

        if (!isset($aContent[$sLanguage]))
            $aContent[$sLanguage] = $sMessage;

        $aHeadings = array( $sLanguage => _t("_bx_messenger_push_vc_{$sType}_message_title"));

        $aInfo = array(
            'contents' => $aContent,
            'headings' => $aHeadings,
            'url' => $oObject->getUrl()
        );

        BxDolPush::getInstance()->send($iReceiverId, array_merge($aInfo, array(
                'icon' => $sType === 'bx_persons' ?
                                $oSender->getThumb() :
                                $oObject->getThumb())),
            true);
    }

    public function actionGetVideoConferenceForm($iProfileId)
    {
        $oProfile = BxDolProfile::getInstance($iProfileId);
        header('Content-type: text/html; charset=utf-8');

        $sContent = '';
        if (!$oProfile)
            $sContent =  MsgBox(_t('_bx_messenger_profile_not_found'));
        else
        {
            $aInfo = $oProfile->getInfo();
            if ($aInfo['type'] == 'bx_organizations') {
                $aFans = BxDolConnection::getObjectInstance("{$aInfo['type']}_fans")->getConnectedContent($iProfileId, true);
                if (!empty($aFans)){
                    foreach($aFans as &$iPart)
                        $this->sendProfilePush($iProfileId, $this->_iProfileId, $iPart, $aInfo['type']);
                }
            } else if ($this->_iProfileId != $iProfileId)
                $this->sendProfilePush($iProfileId, $this->_iProfileId, $iProfileId, $aInfo['type']);

            $CNF = &$this->_oConfig->CNF;
            $sIdent = $this->getPageIdent();
            $sRoom = $this->_oConfig->getRoomId($sIdent);
            $aRoom = $this->_oDb->getPublicVideoRoom($sRoom);
            if (empty($aRoom) || !(int)$aRoom[$CNF['FPJVC_STATUS']]){
                $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_CREATE_IM_VC, $this->_iProfileId);
                if ($mixedResult !== true)
                    $sContent = $mixedResult;
            } else
                if (!empty($aRoom) && (int)$aRoom[$CNF['FPJVC_STATUS']] && (int)$iProfileId !== $this->_iProfileId) {
                    $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_IM_VC, $this->_iProfileId);
                    if ($mixedResult !== true)
                        $sContent = $mixedResult;
                }
        }

        echo $sContent ? $sContent : $this->_oTemplate->getPublicJitsi($this->_iProfileId, $sIdent, $oProfile->getDisplayName(), 'bx-messenger-vc-call');
        exit;
    }

    private function getPageIdent(){
        $sPath = '';
        if (isset($_SERVER['HTTP_REFERER'])){
            $aPath = parse_url($_SERVER['HTTP_REFERER']);
            if (isset($aPath['path']))
                $sPath = $aPath['path'];

            if (isset($aPath['query']))
                $sPath .= '?' . $aPath['query'];

        }

        return $sPath;
    }

    public function actionUpdatePublicJitsiVideoConference(){
        $sRoom = bx_get('room');
        $sAction = bx_get('action');
        if (!$sRoom || !$this->_iProfileId || !$sAction)
            return false;

        if ($sAction == BX_JOT_PUBLIC_JITSI_JOIN)
            return $this->_oDb->createPublicVideoRoom($sRoom, $this->_iProfileId);

        return $this->_oDb->updatePublicVideoRoom($sRoom, $this->_iProfileId, $sAction);
    }

    public function serviceGetLiveUpdates($aMenuItemParent, $aMenuItemChild, $iCount = 0)
    {
        $iCountNew = $this->serviceGetUpdatedLotsNum();
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

    public function serviceGetLiveVcUpdates($iCount = 0)
    {
        $iCount = 0;
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        {
           $sPageUrl = $this->getPageIdent();
           /* ADD ABILITY TO call members when they on any page of the site and have incoming call */
           if (preg_match('/.*page\/(.*)\?id=(\d+).*/', $sPageUrl,$aParams)) {
               if (!empty($aParams) && isset($aParams[1]) && isset($aParams[2])){
                   $iPageOwner = $this->_oDb->findThePageOwner(array('i' => $aParams[1], 'id' => $aParams[2]));
                   //if ($this->_iProfileId == $iPageOwner)
               }
           }
           $aParticipants = $this->_oDb->getPublicRoomParticipants($this->_oConfig->getRoomId($sPageUrl));
           $iCount = count($aParticipants);
        }

        return array(
            'count' => $iCount,
            'method' => 'oMessengerPublicLib.addBubble(oData)',
            'data' => array(
                'code' => BxDolTemplate::getInstance()->parseHtmlByTemplateName('menu_item_addon', array(
                    'content' => '{count}'
                )),
                'selector' => '.bx-menu-item-public-vc-messenger a.bx-btn'
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
        foreach ($aLots as &$aLot) {
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
            return array('code' => 0, 'id' => $this->_oDb->addJot($iLotId, $sMessage, $this->_iUserId));

        return array('code' => 1, 'message' => _t('_bx_messenger_send_message_no_data'));
    }

    private function prepareMessageToDb($sMessage)
    {
        return $sMessage ? preg_replace(array('/\<p>/i', '/\<\/p>/i', '/\<pre.*>/i'), array(/*'', '',*/ '', '<br/>', '<pre>'), $sMessage) : '';
    }

    function actionGetGiphy(){
        if (!$this->isLogged())
            return '';

        $aContent = $this->_oTemplate->getGiphyItems(bx_get('action'), urlencode(bx_get('filter')), (float)bx_get('height'), (int)bx_get('start'));
        if (isset($aContent['content']) && $aContent['content'])
            return echoJson(array('code' => 0, 'html' => $aContent['content'], 'total' => isset($aContent['pagination']) ? $aContent['pagination']['total_count'] : (int)bx_get('start')));

        return echoJson(array('code' => 1, 'message' => MsgBox(_t('_bx_messenger_giphy_gifs_nothing_found'))));
    }

    function actionGetTalksList(){
        if (!$this->isLogged())
            return echoJson(array(
            'code' => 1,
            'reload' => 1
        ));

        $aParams = array('start' => (int)bx_get('count'));
        $aLotsList = $this->_oDb->getMyLots($this->_iProfileId, $aParams);
        if (!empty($aLotsList)) {
            $sContent = $this->_oTemplate->getLotsPreview($this->_iProfileId, $aLotsList);
            return echoJson(array(
                'code' => 0,
                'html' => $sContent
            ));
        }

        return echoJson(array('code' => 1));
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

    public function actionGetJitsiConferenceForm($iLotId = 0)
    {
        if (!$this->_iProfileId){
            echo MsgBox(_t('_bx_messenger_jitsi_err_can_join_conference'));
            exit;
        }

        header('Content-type: text/html; charset=utf-8');

        if (!$iLotId) {
            $sUrl = bx_get('url');
            $sTitle = bx_get('title');
            $aLot = $this -> _oDb -> getLotByUrlAndParticipantsList($sUrl);
            $iLotId = empty($aLot) ? $this->_oDb->createNewLot($this->_iProfileId, $sTitle, BX_IM_TYPE_PRIVATE, $sUrl) : $aLot[$this->_oConfig->CNF['FIELD_ID']];
        }

        $CNF = &$this->_oConfig->CNF;
        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        if (!$this->_oDb->isParticipant($iLotId, $this->_iProfileId) && !empty($aLotInfo) && $aLotInfo[$CNF['FIELD_TYPE']] !== BX_IM_TYPE_PRIVATE)
            $this->_oDb->addMemberToParticipantsList($iLotId, $this->_iProfileId);

        $aParams['audio_only'] = bx_get('startAudioOnly') ? 1 : 0;
        $mixedAuthor = $this->_oDb->getActiveJVCItem($iLotId, $CNF['FJVCT_AUTHOR_ID']);
        if (($mixedAuthor === false || ((int)$mixedAuthor == $this->_iProfileId)) && $this->_oConfig->isJitsiAllowed($aLotInfo[$CNF['FIELD_TYPE']])) {
            echo $this->_oTemplate->getJitsi($iLotId, $this->_iProfileId, $aParams);
            exit;
        }

        $sMessage = _t('_bx_messenger_jitsi_err_cant_type_use');
        if ($mixedAuthor !== false && ((int)$mixedAuthor != $this->_iProfileId) && ($mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_TALK_VC))) {
           if ($mixedResult !== true)
             $sMessage = $mixedResult;
           else
           {
             echo $this->_oTemplate->getJitsi($iLotId, $this->_iProfileId, $aParams);
             exit;
           }
        }

        echo MsgBox($sMessage, 2.5);
        exit;
    }

    public function onCreateVC($iLotId)
    {
        bx_alert($this->_oConfig->getObject('alert'), 'create_vc', $iLotId, $this->_iUserId);
    }

    public function onJoinVC($iLotId)
    {
        $aItem = $this->_oDb->getActiveJVCItem($iLotId);
        bx_alert($this->_oConfig->getObject('alert'), 'join_vc', $iLotId, $this->_iUserId, array('object_author_id' => $aItem[$this->_oConfig->CNF['FJVCT_AUTHOR_ID']]));
    }

    public function actionCreateJitsiVideoConference(){
        $CNF = $this->_oConfig->CNF;
        $iLotId = (int)bx_get('lot_id');
        if (!$iLotId)
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_found')));

        if (!$this->isLogged())
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_jitsi_err_can_join_conference')));

        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        if (empty($aLotInfo))
            return echoJson(array('code' => 1, 'message' => _t('_bx_messenger_not_found')));

        if (!$this->_oDb->isParticipant($iLotId, $this->_iProfileId) && !empty($aLotInfo) && $aLotInfo[$CNF['FIELD_TYPE']] !== BX_IM_TYPE_PRIVATE)
            $this->_oDb->addMemberToParticipantsList($iLotId, $this->_iProfileId);

        $mixedAuthor = $this->_oDb->getActiveJVCItem($iLotId, $CNF['FJVCT_AUTHOR_ID']);
        if ($mixedAuthor != $this->_iProfileId && ($mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_TALK_VC))) {
            if  ($mixedResult !== true)
                    return echoJson(array('code' => 1, 'message' => $mixedResult));
        }

        $aJVC = $this->_oDb->getJVC($iLotId);
        $iParticipants = count($this->_oDb->getParticipantsList($iLotId));
        $aResult = array('code' => 0, 'parts' => $iParticipants);
        if (!empty($aJVC) && (int)$aJVC[$CNF['FJVC_ACTIVE']]) {
            if ($this->_oDb->joinToActiveJVC($iLotId, $this->_iProfileId)) {
                $aResult['jot_id'] = $this->_oDb->getJotIdByJitsiItem((int)$aJVC[$CNF['FJVC_ACTIVE']]);
                $aResult[$CNF['FJVC_ROOM']] = $aJVC[$CNF['FJVC_ROOM']];
                $this->onJoinVC($iLotId);
                return echoJson($aResult);
            }
            else
               $this->_oDb->closeJVC($iLotId);
        }

        $this->onCreateVC($iLotId);
        return echoJson(
                        array_merge(
                            array('new' => 1),
                            $aResult,
                            $this->_oDb->createJVC($iLotId, $this->_iProfileId)
                        ));
    }

    public function actionStopJvc(){
        $iLotId = (int)bx_get('lot_id');
        if (!$iLotId)
            return echoJson(array('code' => 1, 'msg' => _t('_bx_messenger_not_found')));

        if (!$this->_oDb->isParticipant($iLotId, $this->_iProfileId))
            return echoJson(array('code' => 1));

        if ($this->_oDb->stopJVC($iLotId, $this->_iProfileId))
            return echoJson(array('code' => 0));

        return echoJson(array('code' => 1));
    }

    public function actionLeaveJvc(){
        if (!isLogged())
            return '';

        $iLotId = (int)bx_get('lot_id');
        $this->_oDb->leaveJVC($iLotId, $this->_iProfileId);
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

    function actionGetCallPopup(){
        $iLotId = (int)bx_get('lot');
        $aResult = array('code' => 1, 'msg' => _t('_bx_messenger_not_found'));
        if (!$iLotId || !isLogged())
            return echoJson($aResult);

        if ($this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_TALK_VC) !== true)
            $aResult = array('code' => 1);
        else
        {
            $mixedContent = $this->_oTemplate->getCallPopup($iLotId, $this->_iProfileId);
            $aResult = array('code' => +($mixedContent === false), 'popup' => $mixedContent);
        }

        return echoJson($aResult);
    }

    function actionGetLotSettings(){
        $iLotId = (int)bx_get('lot');
        $aResult = array('code' => 1, 'msg' => _t('_bx_messenger_not_found'));
        if (!$iLotId || !isLogged())
            return echoJson($aResult);

        $aResult = array('code' => 1, 'msg' => _t('_bx_messenger_lot_can_change_settings'));
        $bCheckAction = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $this->_iProfileId) === true;
        if (!($this->_oDb->isAuthor($iLotId, $this->_iProfileId) || $bCheckAction))
            return echoJson($aResult);

        $mixedContent = $this->_oTemplate->getLotSettingsForm($iLotId);
        $aResult = array(
            'code' => +($mixedContent === false),
            'popup' => array(
                'html' => $mixedContent,
                'options' => array(
                    'closeElement' => false,
			        'closeOnOuterClick' => true,
			        'removeOnClose' => true
                )
            )
        );
        return echoJson($aResult);
    }

    function actionSaveLotSettings(){
        $iLotId = (int)bx_get('lot');
        $aOptions = bx_get('options');
        $aResult = array('code' => 1, 'msg' => _t('_bx_messenger_not_found'));
        if (!$iLotId || !isLogged() || !($aLotInfo = $this->_oDb->getLotInfoById($iLotId)))
            return echoJson($aResult);

        $aResult = array('code' => 1, 'msg' => _t('_bx_messenger_lot_can_change_settings'));
        $bCheckAction = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $this->_iProfileId) === true;
        if (!($this->_oDb->isAuthor($iLotId, $this->_iProfileId) || $bCheckAction))
            return echoJson($aResult);

        if ($this->_oDb->saveLotSettings($iLotId, $aOptions))
            return echoJson(array('code' => 0/*, 'text_area' => $this->_oTemplate->getTextArea($this->_iProfileId, $iLotId)*/));

        return echoJson($aResult);
    }

    function serviceSearch($sText = '', $iStart = 0, $iLimit = 10, $sOrder = 'DESC', $iLotId = 0){
        $CNF = $this->_oConfig->CNF;

        if (($iLotId && !isLogged()) || (!isAdmin() && !$iLotId))
            return _t('_bx_messenger_no_permissions');

        $sResult = $this->_oDb->findInHistory($iLotId, $sText, $iStart, $iLimit, $sOrder);
        if (empty($sResult))
            return array();

        $aMessages = array();
        foreach($sResult as &$aMessage){
            $iType = $aMessage[$CNF['FIELD_TYPE']];
            $sTitle = $aMessage[$CNF['FIELD_TITLE']] ?
                _t($aMessage[$CNF['FIELD_TITLE']]) : _t('_bx_messenger_lots_private_lot');

            $aMessages[] = array_merge($aMessage, array(
                $CNF['FIELD_TITLE'] => $sTitle,
                'message_url' => $this->_oConfig->getRepostUrl($aMessage['message_id']),
                $CNF['FIELD_PARTICIPANTS'] => explode(',', $aMessage[$CNF['FIELD_PARTICIPANTS']])
            ));
        }

        return $aMessages;
    }
    function serviceGetMembershipLevels()
    {
       return BxDolAcl::getInstance()->getMemberships();
    }

    function serviceGetVideoConference($sRoomIdent, $iInitiator, $sTitle = '', $sId = '', $aInterfaceConfig = array()){
        if (!$iInitiator)

        if (!$sRoomIdent)
            $sRoomIdent = $this->_iProfileId;

        return $this->_oTemplate->getPublicJitsi($iInitiator, $sRoomIdent, $sTitle, $sId, $aInterfaceConfig);
    }

    function serviceGetPublicRoomInfo($sRoomId){
        return $this->_oDb->getPublicVideoRoom($sRoomId);
    }

    function serviceGenerateRoomName($sIdent){
        return $this->_oConfig->getRoomId($sIdent);
    }

    function actionGetEmojiPicker(){
		if (!$this->_iProfileId)
		    return;

        echo $this->_oTemplate->getEmojiCode();
        exit;
    }

    function actionUpdateUploadedFiles(){
        $iJotId = bx_get('jot_id');
        $aFiles = bx_get('files');
        if (!$this->isLogged() || !$iJotId || empty($aFiles))
            return echoJson(array('code' => 1, 'message' => MsgBox(_t('_bx_messenger_not_logged')), 'reload' => 1));

        $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_SEND_MESSAGE, $this->_iProfileId);
        if ($mixedResult !== true)
            return echoJson(array('code' => 1, 'message' => $mixedResult));


        $CNF = &$this->_oConfig->CNF;
        $aLotInfo = $this->_oDb->getLotByJotId($iJotId, false);
        if (empty($aLotInfo) || ($aLotInfo[$CNF['FIELD_TYPE']] === BX_IM_TYPE_PRIVATE &&
                !$this->_oDb->isParticipant($aLotInfo[$CNF['FIELD_MESSAGE_FK']], $this->_iProfileId)))
            return echoJson(array('code' => 1, 'message' => '_bx_messenger_not_participant'));

        $aUploadedFiles = $this->_oDb->getJotFiles($iJotId);
        $oStorage = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE']);
        foreach ($aFiles as &$aFile) {
            $sRealName = $aFile['realname'];
            if (!empty($aUploadedFiles) && array_filter($aUploadedFiles, function($aF) use ($sRealName, $CNF) {
                    return $aF[$CNF['FIELD_ST_NAME']] == $sRealName;
                }))
                continue;

            $iFileId = $oStorage->storeFileFromPath(BX_DIRECTORY_PATH_TMP . $aFile['name'], $aLotInfo[$CNF['FIELD_TYPE']] == BX_IM_TYPE_PRIVATE, $this->_iProfileId, (int)$iJotId);
            if ($iFileId) {
                $oStorage->afterUploadCleanup($iFileId, $this->_iProfileId);
                $this->_oDb->updateFiles($iFileId, array(
                    $CNF['FIELD_ST_JOT'] => $iJotId,
                    $CNF['FIELD_ST_NAME'] => $sRealName
                ));
                $this->_oDb->updateJot($iJotId, $CNF['FIELD_MESSAGE_AT_TYPE'], BX_ATT_TYPE_FILES);
            }
        }

        echoJson(array('code' => 0));
    }
}

/** @} */
