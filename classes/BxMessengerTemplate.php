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
 * Module  representation
 */
class BxMessengerTemplate extends BxBaseModGeneralTemplate
{
	function __construct(&$oConfig, &$oDb)
	{
		parent::__construct($oConfig, $oDb);
	}
	
	/**
	* Attach js and css files for messenger depends on page with messenger block
	*@param string $sMode 
	*/
	public function loadCssJs($sMode = 'all'){
        $CNF = $this->_oConfig->CNF;
	    $aCss = array(
						'semantic.min.css',
						'semantic-messenger.css',
						'main.css',
                        'video-conference.css',
                        'emoji.css',
                        'messenger-phone.css',
                        'talk-header.css',
                        'quill.bubble.css',
                        $CNF['EMOJI']['css']
					 );

		$aJs = array(
		                'primus.js',
                        'record-video.js',
		                'editor.js',
                        'storage.js',
						'connect.js',
						'status.js',
                        'messenger.js',
						'RecordRTC.min.js',
						'adapter.js',
						'semantic.min.js',
                        'soundjs.min.js',
                        'quill.min.js',
                        'jquery-ui/jquery.ui.widget.min.js',
                        'jquery-ui/jquery.ui.tooltip.min.js',
                        $CNF['EMOJI']['js']
					);

		if ($sMode == 'all'){
			array_push($aCss, 'admin.css', 'messenger.css');
			array_push($aJs, 'columns.js');
		}

        if ($this->_oConfig->CNF['USE_MENTIONS']) {
            array_push($aCss, 'quill.mention.css');
            array_push($aJs, 'quill.mention.min.js');
        }

        $this->initFilesUploader();

        $this->addCss($aCss);
		$this->addJs($aJs); 
	}

	/**
	* Main function to build post messages area with messages history
	*@param int $iProfileId logged member id
	*@param int $iLotId id of conversation. It can be empty if new talk
	*@param int $iJotId jot id, allows to load history from jot's position
	*@return string html code
	*/
	public function getHistory($iProfileId, $iLotId = BX_IM_EMPTY, $iJotId = BX_IM_EMPTY){
		$CNF = $this->_oConfig->CNF;
		$aParams = array(
			'content' => $this -> parseHtmlByName('history-block.html', array(
                'content' => MsgBox(_t('_bx_messenger_empty_history'))
            )),
            'new_msg_active' => 'none',
            'unread_count' => 0
		);

        if ($iLotId){
            $iUnreadLotsJots = $this->_oDb->getUnreadJotsMessagesCount($iProfileId, $iLotId);
            if ($iUnreadLotsJots && !$iJotId)
                $iJotId = $this -> _oDb -> getFirstUnreadJot($iProfileId, $iLotId);

            $sContent = $this->getHistoryArea($iProfileId, $iLotId, $iJotId, $iUnreadLotsJots && $iUnreadLotsJots < (int)($CNF['MAX_JOTS_BY_DEFAULT']/2));
            $aParams = array(
			    'content' => $sContent,
                'new_msg_active' => $iUnreadLotsJots ? 'block' : 'none',
                'unread_count' => $iUnreadLotsJots
            );
		}
        
        $aParams['lot_id'] = $iLotId;
		return $this -> parseHtmlByName('history.html', $aParams);
	}

    public function getHistoryArea($iProfileId, $iLotId = BX_IM_EMPTY, $iJotId = BX_IM_EMPTY, $bRead = false, $bEmptyMessage = false){
        $CNF = $this->_oConfig->CNF;

        $sContent = !$bEmptyMessage ? MsgBox(_t('_bx_messenger_empty_history')) : '';
        if ($iLotId){
            $aJots = $this -> getJotsOfLot($iProfileId,
                array(
                    'lot_id' => $iLotId,
                    'limit' => $CNF['MAX_JOTS_BY_DEFAULT'],
                    'start' => $iJotId,
                    'display' => true,
                    'select' => true,
                    'views' => true,
                    'read' => $bRead
                ));
            if (!empty($aJots) && $aJots['content'])
                $sContent = $aJots['content'];
        }

        return $this -> parseHtmlByName('history-block.html', array(
            'content' => $sContent
        ));
    }

	public function getTextArea($iProfileId, $iLotId = 0){
	    $CNF = $this->_oConfig->CNF;

        $mixedResult = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_SEND_MESSAGE, $iProfileId);
	    if (!$iProfileId || $mixedResult !== true)
	        return '';

        $bGiphy = $bIsGiphySet = $CNF['GIPHY']['api_key'] !== '';
	    $bRecorder = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_VIDEO_RECORDER, $iProfileId) === true;
        $bFiles = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_SEND_FIELS, $iProfileId) === true;

        $bMSG = $bSmiles = true;
	    if ($iLotId) {
          $mixedOptions = $this->_oDb->getLotSettings($iLotId);

          $bMSG = $mixedOptions === false || in_array(BX_MSG_SETTING_MSG, $mixedOptions);
          $bFiles = $bFiles && ($mixedOptions === false || in_array(BX_MSG_SETTING_FILES, $mixedOptions));
          $bRecorder = $bRecorder && ($mixedOptions === false || in_array(BX_MSG_SETTING_VIDEO_RECORD, $mixedOptions));
          $bGiphy = $bIsGiphySet && ($mixedOptions === false || in_array(BX_MSG_SETTING_GIPHY, $mixedOptions));
          $bSmiles = $mixedOptions === false || in_array(BX_MSG_SETTING_SMILES, $mixedOptions);
        }

        $bCheckAction = $this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $iProfileId) === true;
        $bIsAuthor = $bCheckAction || $this->_oDb->isAuthor($iLotId, $iProfileId);

        $sContent = '';
	    if ($bMSG || $bFiles || $bRecorder || $bGiphy || $bSmiles || $bIsAuthor) {
            $aVars = array(
                'bx_if:giphy' => array(
                    'condition' => $bGiphy || ($bIsGiphySet && $bIsAuthor),
                    'content' => array()
                ),
                'bx_if:recording' => array(
                    'condition' => $bRecorder || $bIsAuthor,
                    'content' => array()
                ),
                'bx_if:files' => array(
                    'condition' => $bFiles || $bIsAuthor,
                    'content' => array()
                ),
                'bx_if:smiles' => array(
                    'condition' => ($bMSG && $bSmiles) || $bIsAuthor,
                    'content' => array()
                ),
                'bx_if:text' => array(
                    'condition' => $bMSG || $bIsAuthor,
                    'content' => array()
                ),
                'giphy' => $bGiphy || ($bIsGiphySet && $bIsAuthor) ? $this->getGiphyPanel() : ''
            );

            $sContent = $this -> parseHtmlByName('text_area.html', $aVars);
        }

        return $sContent;
    }

	public function getEmojiCode(){
        $CNF = $this->_oConfig->CNF;

	    return $this->parseHtmlByName('emoji-picker.html', array(
            'title' => _t('_bx_messenger_emoji_text_title'),
            'function' => "{$CNF['JSMain']}.onEmojiInsert",
            'set' => $CNF['EMOJI_SET'],
            'native' => $CNF['EMOJI_SET'] === 'native' ? 'true' : 'false',
            'preview' => $CNF['EMOJI_PREVIEW']  ? 'true' : 'false',
            'localization' => addslashes(json_encode(
                array(
                    'search' => _t('_bx_messenger_emoji_search'),
                    'clear' => _t('_bx_messenger_emoji_clear'),
                    'notfound' => _t('_bx_messenger_emoji_not_found'),
                    'skintext' => _t('_bx_messenger_emoji_skintext'),
                    'categories' => array(
                        'search' => _t('_bx_messenger_emoji_categ_search'),
                        'recent' => _t('_bx_messenger_emoji_categ_recent'),
                        'people' => _t('_bx_messenger_emoji_categ_people'),
                        'nature' => _t('_bx_messenger_emoji_categ_nature'),
                        'foods' => _t('_bx_messenger_emoji_categ_foods'),
                        'activity' => _t('_bx_messenger_emoji_categ_activity'),
                        'places' => _t('_bx_messenger_emoji_categ_places'),
                        'objects' => _t('_bx_messenger_emoji_categ_objects'),
                        'symbols' => _t('_bx_messenger_emoji_categ_symbols'),
                        'flags' => _t('_bx_messenger_emoji_categ_flags'),
                        'custom' => _t('_bx_messenger_emoji_categ_custom'),
                    ),
                    'categorieslabel' => _t('_bx_messenger_emoji_categ_search'),
                    'skintones' => array(
                        1 => _t('_bx_messenger_emoji_skintones_1'),
                        2 => _t('_bx_messenger_emoji_skintones_2'),
                        3 => _t('_bx_messenger_emoji_skintones_3'),
                        4 => _t('_bx_messenger_emoji_skintones_4'),
                        5 => _t('_bx_messenger_emoji_skintones_5'),
                        6 => _t('_bx_messenger_emoji_skintones_6'),
                    ),
                )))));
    }

	public function initFilesUploader(){
        $this->addCss(array(
            'filepond-custom.css',
            'filepond.min.css',
            'filepond-plugin-image-preview.min.css',
            'filepond-plugin-media-preview.min.css'
        ));

        $this->addJs(array(
            'uploader.js',
            'filepond.min.js',
            'filepond-plugin-image-preview.min.js',
            'filepond-plugin-file-validate-size.min.js',
            'filepond-plugin-media-preview.min.js',
            'filepond-plugin-file-rename.min.js'
        ));

        $this->addJsTranslation(array(
            '_bx_messenger_upload_delete',
            '_bx_messenger_delete_confirm',
            '_bx_messenger_upload_invalid_file_type',
            '_bx_messenger_max_files_upload_error',
            '_bx_messenger_upload_is_complete',
            '_bx_messenger_upload_cancelled',
            '_bx_messenger_uploading_file',
            '_bx_messenger_invalid_server_response',
            '_bx_messenger_uploading_remove_button',
			'_bx_messenger_file_is_too_large_error',
            '_bx_messenger_file_is_too_large_error_details',
			'_bx_messenger_file_type_is_not_allowed',
        ));
    }

    private function getJotMenuCode(&$aJot, $iProfileId){
	    $CNF = &$this->_oConfig->CNF;

	    $iJotAuthor = !empty($aJot) ? (int)$aJot[$CNF['FIELD_MESSAGE_AUTHOR']] : (int)$iProfileId;
	    $iJotId = !empty($aJot) ? (int)$aJot[$CNF['FIELD_MESSAGE_ID']] : 0;

	    $bAllowToDelete = $this->_oDb->isAllowedToDeleteJot($iJotId, $iProfileId, $iJotAuthor);
	    $bVC = !empty($aJot) ? (int)$aJot[$CNF['FIELD_MESSAGE_VIDEOC']] : false;
        $aMenuItems = array(
            array(
                'click' => "{$CNF['JSMain']}.onAddReaction(this);",
                'title' => _t('_bx_messenger_reaction_jot'),
                'icon' => 'smile',
                'class' => 'smile'
            ),
            array(
                'visibility' => $bAllowToDelete && !$bVC,
                'click' => "{$CNF['JSMain']}.onEditJot(this);",
                'title' => _t('_bx_messenger_edit_jot'),
                'icon' => 'edit',
                'class' => ''
            ),
            array(
                'click' => "{$CNF['JSMain']}.onCopyJotLink(this);",
                'title' => _t('_bx_messenger_share_jot'),
                'icon' => 'link',
                'class' => ''
            ),
            array(
                'visibility' => $bAllowToDelete,
                'click' => "if (confirm('" . bx_js_string(_t('_bx_messenger_remove_jot_confirm')) . "')) 
                                                {$CNF['JSMain']}.onDeleteJot(this);",
                'title' => _t('_bx_messenger_remove_jot'),
                'icon' => 'backspace',
                'class' => ''
            ),
            array(
                'click' => "{$CNF['JSMain']}.onReplyJot(this);",
                'title' => _t('_bx_messenger_reply'),
                'icon' => 'reply',
                'class' => ''
            ),
            /*array(
                'click' => "{$CNF['JSMain']}.onUnread(this);",
                'title' => _t('_bx_messenger_mark_as_unread'),
                'icon' => 'eye-slash'
            ),*/
        );

        $aVars = array();
        foreach ($aMenuItems as &$aItem) {
                if (isset($aItem['visibility']) && $aItem['visibility'] !== TRUE)
                    continue;

                $aVars['bx_repeat:menu'][] = $aItem;
        }

		$sMenu = $this->parseHtmlByName('popup-menu-item.html', $aVars);
		return BxTemplStudioFunctions::getInstance()->transBox("jot-menu-{$iJotId}", $sMenu, true);
    }

    /**
     * Main function to build post message block for any page
     * @param int $iProfileId logged member id
     * @param int $iLotId id of conversation. It can be empty if new talk
     * @param int $iJotId id of the message in history
     * @param bool $bIsBlockVersion
     * @return array content and title of the block
     */
	public function getTalkBlock($iProfileId, $iLotId = BX_IM_EMPTY, $iJotId = BX_IM_EMPTY, $bIsBlockVersion = false){
        return $this -> parseHtmlByName('talk.html', array(
			'header' => $this->getTalkHeader($iLotId, $iProfileId, $bIsBlockVersion),
			'history' => $this->getHistory($iProfileId, $iLotId, $iJotId),
            'text_area' => $this->getTextArea($iProfileId, $iLotId)
		));
	}

    public function getTalkBlockByUserName($iViewer, $iProfileId){
        return $this -> parseHtmlByName('talk.html', array(
            'header' => $this->getTalkHeaderForUsername($iViewer, $iProfileId),
            'history' => $this-> getHistory($iViewer),
            'text_area' => $this->getTextArea($iViewer)
        ));
    }

    public function getCreateTalkForm($iProfileId, $iLotId = 0){
        return $this -> parseHtmlByName('talk.html', array(
            'header' => $this -> parseHtmlByName('header_wrapper.html', array('header' => $this->getEditTalkArea($iProfileId, $iLotId))),
            'history' => $this-> getHistory($iProfileId, $iLotId),
            'text_area' => $this->getTextArea($iProfileId, $iLotId)
        ));
    }

	public function getTalkHeaderForUsername($iViewer, $iProfileId, $bWrap = true){
	    $oViewer = $this -> getObjectUser($iViewer);
        $oProfile = $this -> getObjectUser($iProfileId);
        if (!$oProfile || !$oViewer)
            return '';

	    return $bWrap ? $this -> parseHtmlByName('talk_header.html', array(
            'buttons' => '',
            'back_title' => bx_js_string(_t('_bx_messenger_lots_menu_back_title')),
            'title' => $this->getThumbsWithUsernames($iProfileId)
        )): $this->getThumbsWithUsernames($iProfileId);
    }

    public function getThumbsWithUsernames($mixedProfiles){
	    if (empty($mixedProfiles))
	        return '';

	    $aProfiles = $aVars = array();
	    if (!is_array($mixedProfiles))
            $aProfiles[] = (int)$mixedProfiles;
	    else
            $aProfiles = $mixedProfiles;

	    foreach($aProfiles as &$iProfileId){
           $oProfile = $this -> getObjectUser($iProfileId);
           if (!$oProfile)
                continue;

           $sThumb = $oProfile->getThumb();
           $bThumb = stripos($sThumb, 'no-picture') === FALSE;
           $sDisplayName = $oProfile->getDisplayName();

           $aVars['bx_repeat:usernames'][] = array(
                'username' => $sDisplayName,
                'bx_if:avatars' => array(
                    'condition' => $bThumb,
                    'content' => array(
                        'thumb' => $sThumb,
                        'title' => $sDisplayName,
                    )
                ),
                'bx_if:letters' => array(
                    'condition' => !$bThumb,
                    'content' => array(
                        'color' => implode(', ', BxDolTemplate::getColorCode($iProfileId, 1.0)),
                        'title' => $sDisplayName,
                        'letter' => mb_substr($sDisplayName, 0, 1)
                    )
                )
           );
        }

        return $this -> parseHtmlByName('thumb_usernames.html', $aVars);
    }

    /**
     * @param integer $iLotId Talk's id
     * @param integer $iProfileId Viewer id
     * @param bool $bIsBlockVersion indicates if it is Block Messenger version
     * @return false|string
     */

	public function getTalkHeader($iLotId, $iProfileId, $bIsBlockVersion = false, $isArray = false){
        $CNF = &$this->_oConfig->CNF;
        $aLotInfo = array();
        if ($iLotId)
            $aLotInfo = $this -> _oDb -> getLotInfoById($iLotId);

        $sTitle = _t('_bx_messenger_page_block_title');
        if (!empty($aLotInfo))
        {
            if (!empty($aLotInfo[$CNF['FIELD_TITLE']]))
                $sTitle = _t($aLotInfo[$CNF['FIELD_TITLE']]);

            if (!$bIsBlockVersion && $this->_oDb->isLinkedTitle($aLotInfo[$CNF['FIELD_TYPE']]))
                $sTitle = _t('_bx_messenger_linked_title', '<a href ="'. $this->_oConfig->getPageLink($aLotInfo[$CNF['FIELD_URL']]) .'">' . $sTitle . '</a>');
            else if ($aLotInfo[$CNF['FIELD_TYPE']] == BX_IM_TYPE_PRIVATE)
                $sTitle = $this -> getParticipantsNames($iProfileId, $iLotId);
        }

        $aVars = array(
            'buttons' => $this->getTalkHeaderButtons($iLotId),
            'title' => $sTitle
        );

        return $isArray ? $aVars : $this -> parseHtmlByName('talk_header.html', $aVars);
    }

    public function getTalkHeaderButtons($iLotId){
        $CNF = &$this->_oConfig->CNF;
	    $oMenu = BxTemplMenu::getObjectInstance($CNF['OBJECT_MENU_ACTIONS_TALK_MENU']);
        $oMenu->setTemplateById(BX_DB_MENU_TEMPLATE_TABS);
        $oMenu->setContentId($iLotId);

        return $oMenu->getCode();
	}

    public function getLotMenuCode($iLotId, $iProfileId){
        $CNF = &$this->_oConfig->CNF;

        $bAllowed = $this->_oDb->isAuthor($iLotId, $iProfileId) || ($this->_oConfig->isAllowedAction(BX_MSG_ACTION_ADMINISTRATE_TALKS, $iProfileId) === true);
        $aMenuItems = array(
            array(
                'permissions' => $bAllowed,
                'click' => "{$CNF['JSMain']}.createLot({lot:{$iLotId}});",
                'title' => _t("_bx_messenger_lots_menu_add_part"),
                'icon' => 'plus-circle',
                'class' => ''
            ),
            array(
                'permissions' => $bAllowed,
                'click' => "if (confirm('" . bx_js_string(_t('_bx_messenger_delete_lot'), BX_ESCAPE_STR_APOS) . "')) 
                                                {$CNF['JSMain']}.onDeleteLot($iLotId);",
                'title' => _t('_bx_messenger_lots_menu_delete'),
                'icon' => 'backspace',
                'class' => ''
            ),
            array(
                'title' => _t("_bx_messenger_lots_menu_leave"),
                'click' => "if (confirm('" . bx_js_string(_t('_bx_messenger_leave_chat_confirm'), BX_ESCAPE_STR_APOS) . "')) oMessenger.onLeaveLot($iLotId);",
                'icon' => 'sign-out-alt',
                'class' => ''
            ),
            array(
                'title' => _t("_bx_messenger_lots_menu_media"),
                'click' => "$('.bx-messenger-conversation-block-wrapper .ui.sidebar').sidebar('toggle')",
                'icon' => 'photo-video',
                'class' => ''
            ),
            array(
                'permissions' => $bAllowed,
                'click' => "if (confirm('" . bx_js_string(_t('_bx_messenger_clear_lot'), BX_ESCAPE_STR_APOS) . "')) {$CNF['JSMain']}.onClearLot($iLotId);",
                'title' => _t('_bx_messenger_clear_lot_menu'),
                'icon' => 'trash',
                'class' => ''
            ),
            array(
                'permissions' => $bAllowed,
                'click' => "{$CNF['JSMain']}.onLotSettings();",
                'title' => _t('_bx_messenger_lot_menu_settings'),
                'icon' => 'cogs',
                'class' => ''
            )
        );

        $aVars = array();
        foreach ($aMenuItems as &$aItem) {
            if (isset($aItem['permissions']) && $aItem['permissions'] !== true)
                continue;

            $aVars['bx_repeat:menu'][] = $aItem;
        }

        return $this->parseHtmlByName('popup-menu-item.html', $aVars);
    }

	/**
	* Create top of the block with participants names and statuses
	*@param int $iProfileId logged member id
	*@param int $iLotId id of conversation. It can be empty if new talk
    *@return string HTML code
	*/
	public function getParticipantsNames($iProfileId, $iLotId){
		$aNickNames = array();

		$aParticipantsList = $this->_oDb->getParticipantsList($iLotId, true, $iProfileId);
		if (empty($aParticipantsList))
			return '';
		
		$iCount = count($aParticipantsList);
		$aParticipantsList = array_slice($aParticipantsList, 0, $this->_oConfig->CNF['PARAM_ICONS_NUMBER']);
	
		if (count($aParticipantsList) == 1)
		{
			$oProfile = $this -> getObjectUser($aParticipantsList[0]);
			if ($oProfile)
			{
				$aNickNames['bx_repeat:users'][] = array(
				    'profile_username' => $oProfile -> getUrl(),
					'username' =>  $oProfile -> getDisplayName(),
				);
			}
			$sCode = $this -> parseHtmlByName('status_usernames.html', $aNickNames);
		}
		else
		{
			foreach($aParticipantsList as $iParticipant)
			{			
				$oProfile = $this -> getObjectUser($iParticipant);
				if ($oProfile)
					$aNickNames[] = $oProfile->getDisplayName();
			}
			
			$sOthers = $iCount > (int)$this -> _oConfig -> CNF['PARAM_ICONS_NUMBER'] ? _t('_bx_messenger_lot_title_participants_number', $iCount - (int)$this -> _oConfig -> CNF['PARAM_ICONS_NUMBER']) : '';
			$sCode = $this -> parseHtmlByName('simple_usernames.html', array('usernames' => implode(', ', $aNickNames) . " {$sOthers}"));
		}
		
		return $sCode;
	}

	function getEditTalkArea($iProfileId, $iLotId = BX_IM_EMPTY, $aProfiles = array(), $bAllowToSave = true){
       $aParticipants = array();
       $aParticipantsList = $iLotId ? $this->_oDb->getParticipantsList($iLotId, true, $iProfileId) : $aProfiles;
       foreach ($aParticipantsList as $iParticipant)
            if ($oProfile = $this->getObjectUser($iParticipant)) {
                $sThumb = $oProfile->getThumb();
                $bThumb = stripos($sThumb, 'no-picture') === FALSE;
                $sDisplayName = $oProfile->getDisplayName();

                $aParticipants[] = array(
                    'thumb' => $oProfile->getThumb(),
                    'name' => $oProfile->getDisplayName(),
                    'id' => $oProfile->id(),
                    'bx_if:avatars' => array(
                        'condition' => $bThumb,
                        'content' => array(
                            'name' => $sDisplayName,
                            'thumb' => $sThumb,
                        )
                    ),
                    'bx_if:letters' => array(
                        'condition' => !$bThumb,
                        'content' => array(
                            'color' => implode(', ', BxDolTemplate::getColorCode($iParticipant, 1.0)),
                            'letter' => mb_substr($sDisplayName, 0, 1)
                        )
                    )
                );
       }

	   $aVars = array(
                    'bx_repeat:participants_list' => $aParticipants,
                    'bx_if:edit_mode' =>
                        array(
                            'condition' => $bAllowToSave,
                            'content' => array(
                                'lot' => $iLotId,
                            )
                        ),
       );

	   return $this->parseHtmlByName('talk_edit_participants_list.html', $aVars);
    }
	/**
	* Search friends function which shows fiends only if member have no any talks yet
	*@param string $sParam keywords
	*@return string html code
	*/
	function getFriendsList($sParam = ''){
		$iLimit = (int)$this->_oConfig->CNF['PARAM_FRIENDS_NUM_BY_DEFAULT'] ? (int)$this->_oConfig->CNF['PARAM_FRIENDS_NUM_BY_DEFAULT'] : 5;

		$sContent = MsgBox(_t('_Empty'));
		if (!$this->_oConfig->CNF['SHOW-FRIENDS'])
			return $sContent;

        $aFriends = array();
         if (!$sParam){
             bx_import('BxDolConnection');
            $oConnection = BxDolConnection::getObjectInstance('sys_profiles_friends');
            if (!$oConnection || !($aFriends = $oConnection -> getConnectionsAsArray ('content', bx_get_logged_profile_id(), 0, false, 0, $iLimit + 1, BX_CONNECTIONS_ORDER_ADDED_DESC)))
                 return $sContent;
        }
         else
         {
            $aUsers = BxDolService::call('system', 'profiles_search', array($sParam, $iLimit), 'TemplServiceProfiles');
            if (empty($aUsers))
                 return $sContent;

            foreach($aUsers as &$aValue)
                $aFriends[] = $aValue['value'];
        }

        $aItems['bx_repeat:friends'] = array();
        foreach($aFriends as &$iValue){
            $oProfile = $this -> getObjectUser($iValue);
             $sThumb = $oProfile->getThumb();
             $bThumb = stripos($sThumb, 'no-picture') === FALSE;
             $sDisplayName = $oProfile->getDisplayName();

             $aItems['bx_repeat:friends'][] = array(
                 'name' => $sDisplayName,
                 'id' => $oProfile -> id(),
                 'bx_if:avatars' => array(
                     'condition' => $bThumb,
                     'content' => array(
                         'thumb' => $sThumb,
                         'title' => $sDisplayName,
                     )
                 ),
                 'bx_if:letters' => array(
                     'condition' => !$bThumb,
                     'content' => array(
                         'color' => implode(', ', BxDolTemplate::getColorCode($iValue, 1.0)),
                         'title' => $sDisplayName,
                         'letter' => mb_substr($sDisplayName, 0, 1)
                     )
                 )
             );
        }

        return $this -> parseHtmlByName('friends_list.html', $aItems);
	}
	
	/**
	*  List of Lots (left side block content)
	*@param int $iProfileId logged member id
	*@param array $aLots list of lost to show
	*@param boolean $bShowTime display time(last message) in the right side of the lot
	*@return string html code
	*/
	function getLotsPreview($iProfileId, $aLots, $bShowTime = true){
		$CNF = &$this->_oConfig->CNF;
		$sContent = '';

		foreach($aLots as &$aLot)
		{
			$aParticipantsList = $this -> _oDb -> getParticipantsList($aLot[$CNF['FIELD_ID']], true, $iProfileId);
			
			$iParticipantsCount = count($aParticipantsList);
			$aParticipantsList = $iParticipantsCount ? array_slice($aParticipantsList, 0, $CNF['PARAM_ICONS_NUMBER']) : array($iProfileId);
			
			$aVars['bx_repeat:avatars'] = array();
			$aNickNames = array();
			foreach($aParticipantsList as $iParticipant){
				$oProfile = $this -> getObjectUser($iParticipant);
                if ($oProfile) {
                    $sThumb = $oProfile->getThumb();
                    $bThumb = stripos($sThumb, 'no-picture') === FALSE;
                    $sDisplayName = $oProfile->getDisplayName();
					$aVars['bx_repeat:avatars'][] = array(
                        'bx_if:avatars' => array(
                            'condition' => $bThumb,
                            'content' => array(
                                'title' => $sDisplayName,
                                'thumb' => $sThumb,
                            )
                        ),
                        'bx_if:letters' => array(
                            'condition' => !$bThumb,
                            'content' => array(
                                'color' => implode(', ', BxDolTemplate::getColorCode($iParticipant, 1.0)),
                                'letter' => mb_substr($sDisplayName, 0, 1)
                            )
                        ),
                        'status' => ''
 					);
				 
					$aNickNames[] = $sDisplayName;
			    }
			}
			
			if (!empty($aLot[$CNF['FIELD_TITLE']]))
				$sTitle = _t($aLot[$CNF['FIELD_TITLE']]);
			else
			{ 
				if ($iParticipantsCount > 3)
					$sTitle = implode(', ', array_slice($aNickNames, 0, $CNF['PARAM_ICONS_NUMBER'])) . '...';
				else
					$sTitle = implode(', ', $aNickNames);
			}	

			$sStatus = $sCount = '';
			if ($iParticipantsCount <= 1 && $oProfile && empty($aLot[$CNF['FIELD_TITLE']])){
                $sStatus = (method_exists($oProfile, 'isOnline') ? $oProfile -> isOnline() : false) ?
					$this -> getOnlineStatus($oProfile-> id(), 1) : 
					$this -> getOnlineStatus($oProfile-> id(), 0) ;
			}
			else
                $sCount = '<div class="bx-def-label bx-def-font-middle status">' . $iParticipantsCount .'</div>';
	
			
			$aVars[$CNF['FIELD_ID']] = $aLot[$CNF['FIELD_ID']];
			$aVars[$CNF['FIELD_TITLE']] = $sTitle;
			$aVars['number'] = $sCount;
			$aVars['status'] = $sStatus;

			$aLatestJots = $this -> _oDb -> getLatestJot($aLot[$CNF['FIELD_ID']]);
			
			$iTime = bx_time_js($aLot[$CNF['FIELD_ADDED']], BX_FORMAT_DATE);
			
			$aVars[$CNF['FIELD_MESSAGE']] = $aVars['sender_username'] = '';
			if (!empty($aLatestJots))
			{
				$sMessage = '';
				if (isset($aLatestJots[$CNF['FIELD_MESSAGE']]))
				{
                    $sMessage = preg_replace( '/<br\W*?\/>|\n/', " ", $aLatestJots[$CNF['FIELD_MESSAGE']]);
				    $sMessage = html2txt($sMessage);
					if ($aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_REPOST)
					{
						$sMessage = $this -> _oConfig -> cleanRepostLinks($sMessage, $aLatestJots[$this->_oConfig->CNF['FIELD_MESSAGE_AT']]);
						$sMessage = $sMessage ? $sMessage : _t('_bx_messenger_repost_message');
					}
					
					$sMessage = BxTemplFunctions::getInstance()->getStringWithLimitedLength($sMessage, $this->_oConfig-> CNF['MAX_PREV_JOTS_SYMBOLS']);
				}
				
				if (!$sMessage){
				    if ($aLatestJots[$CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES)
					    $sMessage = _t('_bx_messenger_attached_files_message', $this -> _oDb -> getJotFiles($aLatestJots[$CNF['FIELD_MESSAGE_ID']], true));

				    if ($aLatestJots[$CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_GIPHY)
                        $sMessage = _t('_bx_messenger_attached_giphy_message');

                    if ((int)$aLatestJots[$CNF['FIELD_MESSAGE_VIDEOC']])
                        $sMessage = _t('_bx_messenger_lots_menu_video_conf_start');
                }

				$aVars[$CNF['FIELD_MESSAGE']] = $sMessage;
				if ($oSender = $this -> getObjectUser($aLatestJots[$CNF['FIELD_MESSAGE_AUTHOR']]))
				{
					$aVars['sender_username'] = $oSender -> id() == $iProfileId ? _t('_bx_messenger_you_username_title') : $oSender -> getDisplayName();
					$aVars['sender_username'] .= ':';
				}
				
				$iTime = bx_time_js($aLatestJots[$CNF['FIELD_MESSAGE_ADDED']], BX_FORMAT_DATE);
			}

			$iUnreadJotsCount = $this->_oDb->getNewJots($iProfileId, $aLot[$CNF['FIELD_ID']], true);

            $aVars['class'] = $iUnreadJotsCount ? 'unread-lot' : '';
			$aVars['title_class'] = $iUnreadJotsCount ? 'bx-def-font-extrabold' : '';
			$aVars['message_class'] = $iUnreadJotsCount ? 'bx-def-font-semibold' : '';
			$aVars['bubble_class'] = $iUnreadJotsCount ? '' : 'hidden';
			$aVars['count'] = $iUnreadJotsCount;
			$aVars['bx_if:show_time'] = array(
												'condition' => $bShowTime,
												'content' => array(
														'time' => $iTime
													)
												);			
			
			$sContent .= $this -> parseHtmlByName('lots_briefs.html',  $aVars);
		}
		
		return $sContent;
	}
  
  	/**
	* Builds top talk area with Profiles names and Statuses
	*@param int $iProfileId logget member id
	*@param int $iStatus member status
	*@return string html code
	*/
	private function getOnlineStatus($iProfileId, $iStatus){
	    switch($iStatus){
			case 0:
					$sTitle = _t('_bx_messenger_offline');
					$sClass = 'offline';
				break;
			case 2:
					$sTitle = _t('_bx_messenger_away');
					$sClass = 'away';
				break;
			default:
					$sTitle = _t('_bx_messenger_online');
					$sClass = '';
		}


		return $this -> parseHtmlByName('online_status.html', array(
			'id' => (int)$iProfileId,
			'title' => $sTitle,
			'class' => $sClass
		));
	}

    /**
     * Returns html with users show viewed the message
     * @param $iJotId int message id
     * @param $iExcludeProfile int exclude defined profile id from the list
     * @return bool|string
     */
	public function getViewedJotProfiles($iJotId, $iExcludeProfile = 0){
        $CNF = $this->_oConfig->CNF;
        $aJotInfo = $this -> _oDb -> getJotById($iJotId);
        if (empty($aJotInfo))
            return '';

        $aResult = $aParticipants = $this -> _oDb -> getParticipantsList($aJotInfo[$CNF['FIELD_MESSAGE_FK']], true);
        if ($CNF['MAX_VIEWS_PARTS_NUMBER'] < count($aResult))
            return '';

        $aUnreadProfiles = $this->_oDb->getForWhomJotIsNew($aJotInfo[$CNF['FIELD_MESSAGE_FK']], $iJotId);
        if (!empty($aUnreadProfiles))
            $aResult = array_diff($aParticipants, $aUnreadProfiles);
        else
            return '';

        $aIcons = array();
        foreach($aResult as &$iProfileId) {
            if ($iExcludeProfile && $iExcludeProfile == $iProfileId)
                continue;

            if ($oProfile = BxDolProfile::getInstance($iProfileId))
                $aIcons[] = array(
                    'id' => $iProfileId,
                    'icon' => $oProfile->getIcon(),
                    'name' => $oProfile->getDisplayName(),
                );
        }

        return $this -> parseHtmlByName('viewed.html', array(
            'bx_repeat:viewed' => $aIcons
        ));
    }
	/**
	* Get jots list by specified criteria
	*@param int $iProfileId logged member id
	*@param array $aParams options
	*	- int $iLotId 
	*	- string $sUrl of the lot block
	*	- int $iStart jot's id from which to load the messages
	*	- string $sLoad type of the load (new jots or prev from history) 
	*	- int $iLimit number of jots
	*	- boolean $bDisplay make jots visible before loading
	*	- string html code
	*	- boolean load history from defined jot id and to select it
    *@return array HTML code
	*/
	public function getJotsOfLot($iProfileId, $aParams){
        $CNF = &$this -> _oConfig -> CNF;
		$iLotId = isset($aParams['lot_id']) ? (int)$aParams['lot_id'] : BX_IM_EMPTY;
		$sUrl = isset($aParams['url']) ? $aParams['url'] : BX_IM_EMPTY_URL;
		$iStart = isset($aParams['start']) ? (int)$aParams['start'] : BX_IM_EMPTY; 
		$sLoad = isset($aParams['load']) ? $aParams['load'] : 'new';
		$iLimit = isset($aParams['limit']) ? (int)$aParams['limit'] : BX_IM_EMPTY; 
		$bDisplay = isset($aParams['display']) ? (bool)$aParams['display'] : false;
		$bSelectJot = isset($aParams['select']) ? (bool)$aParams['select'] : false;
		$bMarkAsRead = isset($aParams['read']) && $aParams['read'] === true;
        $bShowViews = isset($aParams['views']);
        $bDynamic = isset($aParams['dynamic']);

        $aResult = array('content' => '');
		$aLotInfo = $this -> _oDb -> getLotByIdOrUrl($iLotId, $sUrl, $iProfileId);
		if (empty($aLotInfo))
			return $aResult;

		if ($bSelectJot && $iStart){
		    $aStartMiddleJot = $this -> _oDb -> getJotsByLotId($aLotInfo[$CNF['FIELD_MESSAGE_ID']], $iStart, 'prev', (int)$CNF['MAX_JOTS_BY_DEFAULT']/2);
			if (!empty($aStartMiddleJot))
			    $iStart = current($aStartMiddleJot)[$CNF['FIELD_MESSAGE_ID']];
		}
		
		$aJots = $this->_oDb->getJotsByLotId($aLotInfo[$CNF['FIELD_MESSAGE_ID']], $iStart, $sLoad, $iLimit, $bSelectJot && $iStart);
		if (empty($aJots))
			return $aResult;

        $iJotCount = count($aJots);
		$aVars['bx_repeat:jots'] = array();
        $iFirstUnreadJot = $this->_oDb->getFirstUnreadJot($iProfileId, $iLotId);
		foreach($aJots as $iKey => $aJot) {
            $oProfile = $this->getObjectUser($aJot[$CNF['FIELD_MESSAGE_AUTHOR']]);
            $iJot = $aJot[$CNF['FIELD_MESSAGE_ID']];

            if ($oProfile) {
                $sReply = $sAttachment = $sMessage = '';
                $bIsTrash = (int)$aJot[$CNF['FIELD_MESSAGE_TRASH']];
                $iIsVC = (int)$aJot[$CNF['FIELD_MESSAGE_VIDEOC']];
                $bIsLotAuthor = $this->_oDb->isAuthor($iLotId, $iProfileId);
                $isAllowedDelete = $this->_oDb->isAllowedToDeleteJot($aJot[$CNF['FIELD_MESSAGE_ID']], $iProfileId, $aJot[$CNF['FIELD_MESSAGE_AUTHOR']], $aJot[$CNF['FIELD_MESSAGE_FK']]);

                if ($bIsTrash || ($iIsVC && !$aJot[$CNF['FIELD_MESSAGE']]))
                    $sMessage = $this->getMessageIcons($aJot[$CNF['FIELD_MESSAGE_ID']], $bIsTrash ? 'delete' : 'vc', isAdmin() || $bIsLotAuthor);
                else {
                    $sMessage = $this->_oConfig->bx_linkify($aJot[$CNF['FIELD_MESSAGE']]);
                    if (!empty($aJot[$CNF['FIELD_MESSAGE_AT_TYPE']])){
						if ($aJot[$CNF['FIELD_MESSAGE_AT_TYPE']] !== BX_ATT_TYPE_REPLY)
							$sAttachment = $this->getAttachment($aJot);
						else 
							$sReply = $this->getAttachment($aJot);						
					}					
                }

                $sActionIcon = '';
                $sDisplayName = $oProfile->getDisplayName();
                if (!$bIsTrash) {
                    if ($aJot[$CNF['FIELD_MESSAGE_EDIT_BY']])
                        $sActionIcon = $this->parseHtmlByName('edit_icon.html',
                            array(
                                'edit' => _t('_bx_messenger_edit_by',
                                    bx_process_output($aJot[$CNF['FIELD_MESSAGE_LAST_EDIT']], BX_DATA_DATETIME_TS),
                                    $this->getObjectUser($aJot[$CNF['FIELD_MESSAGE_EDIT_BY']])->getDisplayName()),
                            )
                        );
                    else
                        if ($iIsVC && $aJot[$CNF['FIELD_MESSAGE']]) {
                            $aJVCItem = $this->_oDb->getJVCItem($iIsVC);
                            $sActionIcon = $this->parseHtmlByName('vc_icon.html',
                                array(
                                    'info' => _t('_bx_messenger_jitsi_vc_into_title', $sDisplayName->getDisplayName(), bx_process_output($aJVCItem[$CNF['FJVCT_START']], BX_DATA_DATETIME_TS))
                                )
                            );
                        }

                }

                $sThumb = $oProfile->getThumb();
                $bThumb = stripos($sThumb, 'no-picture') === FALSE;

                $sReactions = $this->getJotReactions($iJot);
                $aVars['bx_repeat:jots'][] = array(
                    'title' => $sDisplayName,
                    'time' => bx_time_js($aJot[$CNF['FIELD_MESSAGE_ADDED']], BX_FORMAT_TIME, true),
                    'views' => $bShowViews && ($iJotCount - 1 == $iKey) ? $this->getViewedJotProfiles($iJot, $iProfileId) : '',
                    'new' => (int)($iFirstUnreadJot && $iJot >= $iFirstUnreadJot),
                    'url' => $oProfile->getUrl(),
                    'immediately' => +$this->_oConfig->CNF['REMOVE_MESSAGE_IMMEDIATELY'],
                    'bx_if:avatars' => array(
                        'condition' => $bThumb,
                        'content' => array(
                            'title' => $sDisplayName,
                            'thumb' => $sThumb,
                        )
                    ),
                    'bx_if:letters' => array(
                        'condition' => !$bThumb,
                        'content' => array(
                            'color' => implode(', ', BxDolTemplate::getColorCode($aJot[$CNF['FIELD_MESSAGE_AUTHOR']], 1.0)),
                            'letter' => mb_substr($sDisplayName, 0, 1)
                        )
                    ),
                    'id' => $aJot[$CNF['FIELD_MESSAGE_ID']],
                    'message' => $sMessage,
                    'attachment' => $sAttachment,
					'reply' => $sReply,
                    'my' => (int)$iProfileId === (int)$aJot[$CNF['FIELD_MESSAGE_AUTHOR']] ? 1 : 0,
                    'bx_if:jot_menu' => array(
                        'condition' => $iProfileId && !$bIsTrash,
                        'content' => array(
                            'jot_menu' => $this->getJotMenuCode($aJot, $iProfileId)
                        )
                    ),
                    'bx_if:show_reactions_area' => array(
                        'condition' => !$bIsTrash,
                        'content' => array(
                            'bx_if:reactions' => array(
                                'condition' => true,
                                'content' => array(
                                    'reactions' => $sReactions,
                                    'bx_if:reactions_menu' => array(
                                        'condition' => $iProfileId,
                                        'content' => array(
                                            'display' => $sReactions ? 'block' : 'none',
                                        )
                                    ),
                                )
                            ),
                            'bx_if:edit' => array(
                                'condition' => $isAllowedDelete,
                                'content' => array()
                            ),
                        )
                    ),
                    'display' => !$bDisplay ? 'style="display:none;"' : '',
                    'bx_if:blink-jot' => array(
                        'condition' => $bSelectJot && $aParams['start'] == $iJot,
                        'content' => array()
                    ),
                    'display_message' => '',
                    'edit_icon' => $aJot[$CNF['FIELD_MESSAGE_EDIT_BY']] && !$bIsTrash ?
                        $this->parseHtmlByName('edit_icon.html',
                            array(
                                'edit' => _t('_bx_messenger_edit_by',
                                    bx_process_output($aJot[$CNF['FIELD_MESSAGE_LAST_EDIT']], BX_DATA_DATETIME_TS),
                                    $this->getObjectUser($aJot[$CNF['FIELD_MESSAGE_EDIT_BY']])->getDisplayName()),
                            )
                        ) : '',
                    'action_icon' => $sActionIcon
                );

                if ($bMarkAsRead)
                    $this->_oDb->readMessage($aJot[$CNF['FIELD_MESSAGE_ID']], $iProfileId);
            }
        }

        if ($bMarkAsRead)
            $this->_oDb->markNotificationAsRead($iProfileId, $iLotId);

		return array(
		                'content' => $this -> parseHtmlByName('jots.html',  $aVars),
                        'first_jot' => $sLoad == 'prev' ? $aJots[count($aJots) - 1] : $aJots[0]);
	}

	/**
	* Builds left column with content 
	*@param int $iProfileId logged member id
	*@return string html code
	*/
	public function getLotsList($iProfileId){
		$aMyLots = $this->_oDb->getMyLots($iProfileId);
		if (!empty($aMyLots))
			$sContent = $this->getLotsPreview($iProfileId, $aMyLots);
		else
			$sContent = $this->getFriendsList();
		
		$aVars = array(
			'items' => $sContent,
			'star_title' => bx_js_string(_t('_bx_messenger_lots_menu_star_title')),
			'search_for_title' => bx_js_string(_t('_bx_messenger_search_for_lost_title')),
			'bx_repeat:menu' => array(
										array('menu_title' => _t("_bx_messenger_lots_type_all"), 'type' => 0, 'count' => '')
									 ),
			'star_icon' => $this->_oConfig->CNF['STAR_ICON'],
			'star_color' => $this->_oConfig->CNF['STAR_BACKGROUND_COLOR'],
            'bx_if:create' => array(
                'condition' => $this->_oConfig->isAllowedAction(BX_MSG_ACTION_CREATE_TALKS, $iProfileId) === true,
                'content' => array(
                    'create_lot_title' => bx_js_string(_t('_bx_messenger_lots_menu_create_lot_title')),
                )
            )
		);

		return $this -> parseHtmlByName('lots_list.html', $aVars);
	}

    /**
     * Create js configuration for the messenger depends on administration settings
     * @param int $iProfileId logged member id
     * @param bool $bBlockVersion
     * @param int $iLotId
     * @param int $iJotId
     * @param int $iPersonToTalk
     * @param int $iType
     * @return string html code
     */
	public function loadConfig($iProfileId, $bBlockVersion = true, $iLotId = BX_IM_EMPTY, $iJotId = BX_IM_EMPTY, $iPersonToTalk = BX_IM_EMPTY, $iType = BX_IM_TYPE_PRIVATE){
		$CNF = &$this->_oConfig->CNF;
	    $aUrlInfo = parse_url(BX_DOL_URL_ROOT);

	    $oEmbed = BxDolEmbed::getObjectInstance();
        $sEmbedTemplate = '';
		if($oEmbed && $CNF['USE_EMBEDLY'])
           $sEmbedTemplate = $oEmbed->getLinkHTML('__url__');

        $this->addJsTranslation(array(
            '_bx_messenger_online',
            '_bx_messenger_offline',
            '_bx_messenger_away',
            '_bx_messenger_repost_message',
            '_bx_messenger_close_video_confirm',
            '_bx_messenger_video_recorder_is_not_available',
            '_bx_messenger_video_recorder_is_blocked',
            '_bx_messenger_max_video_file_exceeds',
            '_bx_messenger_video_record_is_not_supported',
            '_bx_messenger_search_no_results',
            '_bx_messenger_search_query_issue',
            '_bx_messenger_wait_for_uploading',
            '_bx_messenger_are_you_sure_close_jisti',
            '_bx_messenger_jisti_connection_error',
            '_bx_messenger_post_area_message',
            '_bx_messenger_jitsi_mobile_warning',
            '_bx_messenger_loading',
            '_bx_messenger_share_jot',
            '_bx_messenger_notification_request',
            '_bx_messenger_notification_request_yes',
            '_bx_messenger_notification_request_no',
        ));

        $sUsername = '';
        $oProfile = $this -> getObjectUser($iProfileId);
        if($oProfile)
            $sUsername = bx_js_string($oProfile -> getDisplayName());

        $sUrl = $this->_oConfig->getPageIdent();
        if ($iPersonToTalk && ($oModuleProfile = BxDolProfile::getInstance($iPersonToTalk))) {
            $sModule = $oModuleProfile->getModule();
            $bIsProfile = BxDolRequest::serviceExists($sModule, 'act_as_profile') && BxDolService::call($sModule, 'act_as_profile');
            if (BxDolRequest::serviceExists($sModule, 'is_group_profile') && BxDolService::call($sModule, 'is_group_profile') && !$bIsProfile) {
                $aOwnerInfo = BxDolService::call($sModule, 'get_info', array($oModuleProfile->getContentId(), false));
                if (!empty($aOwnerInfo) && is_array($aOwnerInfo) && BxDolService::call($sModule, 'check_allowed_view_for_profile', array($aOwnerInfo)) === CHECK_ACTION_RESULT_ALLOWED) {
                    $oModule = BxDolModule::getInstance($sModule);
                    if ($oModule->_oConfig) {
                        $oMCNF = $oModule->_oConfig->CNF;

                        $sUrl = "i={$oMCNF['URI_VIEW_ENTRY']}&id=" . $oModuleProfile->getContentId();
                        if ($sUrl && $aTalk = $this->_oDb->getLotByUrl($sUrl))
                            $iLotId = $aTalk[$CNF['FIELD_ID']];
                        else
                        {
                            $sTalkTitle = $oModuleProfile->getDisplayName();
                            $iType = $this->_oConfig->getTalkType($sModule);
                            $iLotId = $this->_oDb->createLot($iProfileId, $sUrl, $sTalkTitle, $iType, array($iProfileId));
                        }
                    }
                }
            }
        }

        if ($iLotId && ($aLotInfo = $this->_oDb->getLotInfoById($iLotId))){
            if ($aLotInfo[$CNF['FIELD_TYPE']] != BX_IM_TYPE_PRIVATE && isset($aLotInfo[$CNF['FIELD_URL']]))
                $sUrl =  $aLotInfo[$CNF['FIELD_URL']];

            $iType = $aLotInfo[$CNF['FIELD_TYPE']];
        };

        $bIsPushEnabled = (int)$iProfileId && $this->_oConfig->isOneSignalEnabled() && !getParam('sys_push_app_id');
        $aUnreadJotsInfo = $this->_oDb->getNewJots($iProfileId, $iLotId);

        $iUnreadJotsNumber = $iLastUnreadJot = 0;
        $iStartJot = (int)$iJotId;
        if (!empty($aUnreadJotsInfo)){
            $iStartJot = $iStartJot ? $iStartJot : (int)$aUnreadJotsInfo[$CNF['FIELD_NEW_JOT']];
            $iUnreadJotsNumber = (int)$aUnreadJotsInfo[$CNF['FIELD_NEW_UNREAD']];
            $iLastUnreadJot = (int)$aUnreadJotsInfo[$CNF['FIELD_NEW_JOT']];
        }

        $bAttach = true;
        if ($iStartJot)
            $bAttach = $this->_oDb->getJotsNumber($iLotId, $iStartJot) < (int)$CNF['MAX_JOTS_BY_DEFAULT']/2;
        
        $sJotJWT = $CNF['JOT-JWT'];
        $aVars = array(
			'profile_id' => (int)$iProfileId,
            'username' => $sUsername,
            'lot' => (int)$iLotId,
            'url' => $sUrl,
            'type' => $iType,
            'direction' => BxDolLanguages::getInstance()->getLangDirection(),
            'selected_profile' => (int)$iPersonToTalk,
            'jot_id' => $iStartJot,
            'by_url' => (int)($iJotId != BX_IM_EMPTY),
			'block_version' => +$bBlockVersion,
			'server_url' => $this->_oConfig-> CNF['SERVER_URL'],
			'message_length' => (int)$CNF['MAX_SEND_SYMBOLS'] ? (int)$CNF['MAX_SEND_SYMBOLS'] : 0,
			'jot_jwt' => $sJotJWT ? $this->_oConfig->generateJWTToken($iProfileId, array('profile' => $iProfileId)) : '',
			'ip' => gethostbyname($aUrlInfo['host']),
			'embed_template' => $sEmbedTemplate,
			'thumb_icon' => $this->parseHtmlByName('thumb_icon.html', array()),
			'thumb_letter' => $this->parseHtmlByName('thumb_letter.html', array()),
			'max_history' => (int)$CNF['MAX_JOTS_BY_DEFAULT'],
			'jitsi_server' => $this->_oConfig->getValidUrl($CNF['JITSI-SERVER'], 'url'),
			'last_unread_jot' => $iLastUnreadJot,
			'unread_jots' => $iUnreadJotsNumber,
			'allow_attach' => +$bAttach,
			'muted' => ($iLotId && $iProfileId ? (int)$this->_oDb->isMuted($iLotId, $iProfileId) : 0),
			'dates_intervals_template' => $this->parseHtmlByName('date-separator.html', array('date' => '__date__')),
			'reaction_template' => $this->parseHtmlByName('reaction.html', array(
			    'emoji_id' => '__emoji_id__',
			    'on_click' => 'oMessenger.onRemoveReaction(this);',
			    'parts' => '__parts__',
			    'title' => _t('_bx_messenger_reaction_title_author'),
                'number' => 1,
                'count' => 1,
                'params' => json_encode(array(
                    'id' => '__emoji_id__',
                    'size' => $CNF['REACTIONS_SIZE'],
                    'native' => $CNF['EMOJI_SET'] === 'native',
                    'set' => $CNF['EMOJI_SET']
                ))
            )),
			'jot_url' => $this->_oConfig->getRepostUrl(),
			'bx_if:onsignal' => array(
										'condition'	=> $bIsPushEnabled,
										'content' => array(
											'one_signal_api' => $CNF['PUSH_APP_ID'],
											'short_name' => $CNF['PUSH_SHORT_NAME'],
											'safari_key' => $CNF['PUSH_SAFARI_WEB_ID'],
											'jot_chat_page_url' => BxDolPermalinks::getInstance()->permalink($CNF['URL_HOME'])
										)
									)
		);

        // init files Uploader
        $oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
        if ($oStorage) {
            $sBaseUrl = $this->_oConfig->getBaseUri();
            $aVars['files_uploader'] = json_encode(array(
              'input_name' => $CNF['FILES_UPLOADER'],
              'restricted_extensions' => json_encode($oStorage->getRestrictedExt()),
              'uploader_url' => $sBaseUrl . 'upload_temp_file',
              'remove_temp_file_url' => $sBaseUrl . 'upload_temp_file',
              'file_size' => (int)$oStorage->getMaxUploadFileSize($iProfileId)/(1024*1024),// in bytes
              'number_of_files' => (int)$this->_oConfig->CNF['MAX_FILES_TO_UPLOAD'],
              'is_block_version' => +$bBlockVersion
            ));
        }


		if ($bIsPushEnabled) {
		    if(class_exists('BxDolPush', false) && method_exists('BxDolPush', 'getTags')){
                $aTags = BxDolPush::getTags($iProfileId);
		        $aPushTags = array(
                    'email' => $aTags['email'],
                    'email_hash' => $aTags['email_hash'],
                    'push_tags_encoded' => json_encode($aTags));
            } else
                $aPushTags = array('email' => '', 'email_hash' => '', 'push_tags_encoded' => json_encode(array('user' => $iProfileId)));

            unset($aPushTags['email']);
            unset($aPushTags['email_hash']);
            unset($aPushTags['email_hash']);
            $aVars['bx_if:onsignal']['content'] = array_merge($aVars['bx_if:onsignal']['content'], $aPushTags);
        }

		return $this -> parseHtmlByName('config.html', $aVars);
	}
	public function getJotReactions($iJotId){
        $CNF = &$this->_oConfig->CNF;
	    $aReactions = $this->_oDb->getJotReactions($iJotId);

	    $aJotReactions = array();
        $iViewer = bx_get_logged_profile_id();
        foreach($aReactions as &$aReaction)
            $aJotReactions[$aReaction[$CNF['FIELD_REACT_EMOJI_ID']]][$aReaction[$CNF['FIELD_REACT_PROFILE_ID']]] =
                $aReaction[$CNF['FIELD_REACT_PROFILE_ID']] == $iViewer
                    ? _t('_bx_messenger_reaction_title_author') : $this->getObjectUser($aReaction[$CNF['FIELD_REACT_PROFILE_ID']])->getDisplayName();

        $sReactions = '';
        foreach($aJotReactions as $sEmojiId => $aProfiles) {
            $iCount = count($aProfiles);
            $sReactions .= $this->parseHtmlByName('reaction.html', array(
                'title' => _t('_bx_messenger_reaction_title', implode(', ', $aProfiles), $sEmojiId),
                'emoji_id' => $sEmojiId,
                'number' => $iCount,
                'parts' => implode(',', array_keys($aProfiles)),
                'count' => $iCount,
                'on_click' => $iViewer ? 'oMessenger.onRemoveReaction(this);' : 'javascript:void(0);',
                'params' => json_encode(array(
                    'id' => $sEmojiId,
                    'size' => $CNF['REACTIONS_SIZE'],
                    'native' => $CNF['EMOJI_SET'] === 'native',
                    'set' => $CNF['EMOJI_SET']
                ))
            ));
        }

        return $sReactions;
    }

	public function getMessageIcons($iJotId, $sType = 'edit', $isAdmin = false)
	{ 
		$CNF = &$this->_oConfig->CNF;
		$sContent = '';		
		if (!($aJotInfo = $this -> _oDb -> getJotById($iJotId)))
			return $sContent;
		
		$sDate = bx_process_output($aJotInfo[$CNF['FIELD_MESSAGE_LAST_EDIT']], BX_DATA_DATETIME_TS);
		$sEditorName = $aJotInfo[$CNF['FIELD_MESSAGE_EDIT_BY']] ? $this -> getObjectUser($aJotInfo[$CNF['FIELD_MESSAGE_EDIT_BY']]) -> getDisplayName() : '';
		
		switch($sType)
		{
			case 'edit':
				$sContent = $aJotInfo[$CNF['FIELD_MESSAGE_EDIT_BY']] ?
								$this -> parseHtmlByName('edit_icon.html',
									array(
											'edit' => _t('_bx_messenger_edit_by', $sDate, $sEditorName)
										)
								) : '';
				break;
			case 'delete':
				$sContent = $this -> parseHtmlByName('deleted_jot.html',
						array(
								'bx_if:allow_to_delete' => array(
															'condition' => $isAdmin,
															'content'	=>
                                                                            array(
                                                                                    'message' => bx_js_string(_t('_bx_messenger_confirm_delete_completely')),
                                                                                    'id' => $iJotId
                                                                                ),
															),
								'info' => $sEditorName ? _t('_bx_messenger_deleted_by', $sDate, $sEditorName) : ''
							)
					);
				break;
            case 'vc':
                $iVC = $aJotInfo[$CNF['FIELD_MESSAGE_VIDEOC']];
                $sContent = _t('_bx_messenger_jitsi_err_vc_was_not_found');
                $sParticipants = '';

                if ($iVC && ($aJVCItem = $this->_oDb->getJVCItem($iVC)))
                {
                    $aParticipants = explode(',', $aJVCItem[$CNF['FJVCT_PART']]);
                    if (!$aJVCItem[$CNF['FJVCT_END']])
                       $sInfo = _t('_bx_messenger_jitsi_has_started', bx_time_js($aJVCItem[$CNF['FJVCT_START']]));
                    else
                    {
                            $iDiff = $aJVCItem[$CNF['FJVCT_END']] - $aJVCItem[$CNF['FJVCT_START']];
                            $iH = floor( $iDiff / 3600 );
                            $iM = floor( ( $iDiff / 60 ) % 60 );
                            $iS = $iDiff % 60;

                            $sDate = _t('_bx_messenger_jitsi_vc_duration_s', $iS);
                            if ($iH)
                                $sDate = _t('_bx_messenger_jitsi_vc_duration_h', $iH, $iM, $iS);
                            else
                                if ($iM)
                                    $sDate = _t('_bx_messenger_jitsi_vc_duration_m', $iM, $iS);

                            $aIcons = array();
                            foreach($aParticipants as &$iProfileId) {
                                if ($oProfile = BxDolProfile::getInstance($iProfileId))
                                $aIcons[] = array(
                                    'id' => $iProfileId,
                                    'icon' => $oProfile->getIcon(),
                                    'name' => $oProfile->getDisplayName(),
                                );
                            }

                            $sParticipants = !empty($aIcons) ? $this -> parseHtmlByName('viewed.html', array(
                                'bx_repeat:viewed' => $aIcons
                            )) : '';

                            $sInfo = _t('_bx_messenger_jitsi_conference', $sDate);
                     }

                        $aLotInfo = $this->_oDb->getLotByJotId($iJotId, false);
                        $aJVC = $this->_oDb->getJVC($aLotInfo[$CNF['FIELD_ID']]);
                        $sRoom = empty($aJVC) && !empty($aLotInfo) ? $this->_oConfig->getRoomId($aLotInfo[$CNF['FIELD_ID']], $aLotInfo[$CNF['FIELD_AUTHOR']]) : $aJVC[$CNF['FJVC_ROOM']];

                        $sContent = $this -> parseHtmlByName('vc_message.html',
                            array(
                                'info' => $sInfo,
                                'bx_if:join' => array(
                                    'condition' => !$aJVCItem[$CNF['FJVCT_END']] && ($isAdmin || $this->_oConfig->isAllowedAction(BX_MSG_ACTION_JOIN_TALK_VC) === true),
                                    'content' => array(
                                        'id' => $aLotInfo[$CNF['FIELD_ID']],
                                        'room' => $sRoom
                                    )
                                ),
                                'bx_if:part' => array(
                                    'condition' => $sParticipants,
                                    'content' => array(
                                       'participants' => $sParticipants
                                   )
                                )
                            ));
                }
                break;
		}
		
		return $sContent;
	}
	
	/**
	* Create profile html template for jot which is used when member posts a message
	*@param int $iProfileId logget member id
	*@return string html code
	*/
	public function getMembersJotTemplate($iProfileId){
		if (!$iProfileId)
		    return '';

		$CNF = &$this->_oConfig->CNF;
		$oProfile = $this -> getObjectUser($iProfileId);

        $isAllowedDelete = $CNF['ALLOW_TO_REMOVE_MESSAGE'] || isAdmin();
		if ($oProfile)
		{
			$aJot = array();

            $sDisplayName = $oProfile->getDisplayName();
            $sThumb = $oProfile->getThumb();
            $bThumb = stripos($sThumb, 'no-picture') === FALSE;

		    $aVars['bx_repeat:jots'][] = array
			(
				'title' => $oProfile->getDisplayName(),
                'views' => '',
				'time' => bx_time_js(time(), BX_FORMAT_TIME, true),
				'url' => $oProfile->getUrl(),
				'thumb' => $oProfile->getThumb(),
				'display' => 'style="display:flex;"',
				'display_message' => 'style="display:none;"',
				'id' => 0,
				'new' => '',
				'my' => 1,
				'message' => '',
				'attachment' => '',
				'reply' => $this -> parseHtmlByName('reply.html', array(
							    'id' => '{reply_parent_id}',
								'message' => '{reply_message}'
							)),
                'bx_if:avatars' => array(
                    'condition' => $bThumb,
                    'content' => array(
                        'title' => $sDisplayName,
                        'thumb' => $sThumb,
                    )
                ),
                'bx_if:letters' => array(
                    'condition' => !$bThumb,
                    'content' => array(
                        'color' => implode(', ', BxDolTemplate::getColorCode($iProfileId, 1.0)),
                        'letter' => mb_substr($sDisplayName, 0, 1)
                    )
                ),
                'bx_if:jot_menu' => array(
                    'condition' => $iProfileId,
                    'content'	=> array(
                        'jot_menu' => $this -> getJotMenuCode($aJot, $iProfileId),
                    )
                ),
                'bx_if:show_reactions_area' => array(
                    'condition' => true,
                    'content' => array(
                        'bx_if:reactions' => array(
                            'condition' => true,
                            'content' => array(
                                'reactions' => '',
                                'bx_if:reactions_menu' => array(
                                    'condition' => true,
                                    'content' => array(
                                        'display' => 'none',
                                    )
                                ),
                            )
                        ),
                        'bx_if:edit' => array(
                            'condition' => $isAllowedDelete,
                            'content'	=> array()
                        ),
                    )
                ),
				'bx_if:blink-jot' => array(
					'condition' => false,
					'content' => array()
				),
				'edit_icon' => '',
                'reactions' => '',
				'action_icon' => ''
			);
					
			return $this -> parseHtmlByName('jots.html',  $aVars);
		}
		
		return '';
	}

    function videoPlayer ($sUrlPoster, $sUrlMP4, $sUrlWebM = '', $sUrlMP4Hd = '', $aAttrs = false, $bDynamic = true)
    {
        $oPlayer = BxDolPlayer::getObjectInstance();
        if (!$oPlayer)
            return '';

        return $oPlayer->getCodeVideo (BX_PLAYER_STANDARD, array(
            'poster' => $sUrlPoster,
            'mp4' => array('sd' => $sUrlMP4, 'hd' => $sUrlMP4Hd),
            'webm' => array('sd' => $sUrlWebM ),
            'attrs' => $aAttrs,
            'styles' => 'width:90%; max-width:480px; height:auto;',
        ), $bDynamic);
    }

    function getAttachmentsVideoTranscoders($sStorage = ''){
	    $aTranscoders = parent::getAttachmentsVideoTranscoders($sStorage);
	    if (!$aTranscoders)
            return array();

        $aTranscoders['webm'] = BxDolTranscoderImage::getObjectInstance($this -> _oConfig -> CNF['OBJECT_VIDEOS_TRANSCODERS']['webm']);
        return $aTranscoders;
    }

    function getVideoFilesToPlay($aFile){
        $CNF = &$this -> _oConfig -> CNF;

        $sFileUrl = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
        $aTranscodersVideo = $this -> getAttachmentsVideoTranscoders();
        if (empty($aTranscodersVideo))
            return '';

        $sMp4File = $aTranscodersVideo['mp4']->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);
        $sMp4HDFile = $aTranscodersVideo['mp4_hd']->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);
        $sWebMFile = $aTranscodersVideo['webm']->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);
        $sPoster = $aTranscodersVideo['poster']->getFileUrl($aFile[$CNF['FIELD_ST_ID']]);

        if (!$sMp4File && !$sWebMFile){
            if ($aFile[$CNF['FIELD_ST_EXT']] == 'webm') {
                $sWebMFile = $sFileUrl;
                $sPoster = '';
            }
            if ($aFile[$CNF['FIELD_ST_EXT']] == 'mp4' || $aFile[$CNF['FIELD_ST_EXT']] == 'mov') {
                $sMp4File = $sMp4HDFile = $sFileUrl;
                $sPoster = '';
            }
        }

       return ($sMp4File || $sWebMFile) ? $this -> videoPlayer(
                $sPoster,
                $sMp4File,
                $sWebMFile,
                $sMp4HDFile,
                array('preload' => 'metadata'),
                true
            ) : $this->parseHtmlByName('tmp_video.html', array('img' => $sPoster));
    }

	function getReplyPreview($iJotId){
	    if (!$iJotId || !($aJot = $this->_oDb->getJotById($iJotId)))
	        return '';

        $CNF = &$this -> _oConfig -> CNF;
        if (!empty($aJot))
        {
            switch($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT_TYPE']])
            {               
                case BX_ATT_TYPE_GIPHY:
                    return '<img src="//media1.giphy.com/media/' . $aJot[$CNF['FIELD_MESSAGE_AT']] . '/giphy_s.gif" />';
                case BX_ATT_TYPE_REPLY:
                    return get_mb_substr(html2txt($aJot[$CNF['FIELD_MESSAGE']]), 0, $CNF['JOT-PREVIEW-TEXT-LENGTH']);
                case BX_ATT_TYPE_FILES_UPLOADING:
                case BX_ATT_TYPE_FILES:
                    $aUploadingFilesList = $aJot[$CNF['FIELD_MESSAGE_AT']] ? explode(',', $aJot[$CNF['FIELD_MESSAGE_AT']]) : array();
                    $aFiles = $this -> _oDb -> getJotFiles($aJot[$CNF['FIELD_MESSAGE_ID']]);
                    $aItems = array(
                        'bx_repeat:images' => array(),
                        'bx_repeat:files' => array(),
                        'bx_repeat:videos' => array(),
                        'bx_repeat:audios' => array(),
                        'bx_repeat:loading_placeholder' => array()
                    );

                    $aTranscodersVideo = $this -> getAttachmentsVideoTranscoders();
                    $oStorage = new BxMessengerStorage($CNF['OBJECT_STORAGE']);
                    $oTranscoderMp3 = BxDolTranscoderAudio::getObjectInstance($CNF['OBJECT_MP3_TRANSCODER']);
                    $aFile = current($aFiles);
                    $isVideo = $aTranscodersVideo && (0 == strncmp('video/', $aFile['mime_type'], 6)) && $aTranscodersVideo['poster']->isMimeTypeSupported($aFile['mime_type']);
                    if ($oStorage -> isImageFile($aFile[$CNF['FIELD_ST_TYPE']])) {
                        if ($oImagesTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']))
                            $sPhotoThumb = $oImagesTranscoder->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);

                        $sFileUrl = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
                        return '<img src="' . ($sPhotoThumb ? $sPhotoThumb : $sFileUrl) . '" />';
                    }

                    if ($isVideo)
                         return '<img src="' . $aTranscodersVideo['poster']->getFileUrl($aFile[$CNF['FIELD_ST_ID']]) . '" />';

                    if ($oTranscoderMp3 -> isMimeTypeSupported($aFile[$CNF['FIELD_ST_TYPE']]))
                        return $aFile[$CNF['FIELD_ST_NAME']];
					
					return $this -> parseHtmlByName('file.html', array(
																	'type' => $oStorage -> getFontIconNameByFileName($aFile[$CNF['FIELD_ST_NAME']]),
																	'name' => $aFile[$CNF['FIELD_ST_NAME']],
																	'file_type' => $aFile[$CNF['FIELD_ST_TYPE']],								
															   ));
				 default:
                    return html2txt($aJot[$CNF['FIELD_MESSAGE']]);											   
            }
        }
	}
    /**
		 * Returns attachment according jot's attachment type
		 * @param array $aJot jot info
		 * @param bool $bMenu show menu in attached items
		 * @param bool $bIsDynamicallyLoad true when message is dynamically loaded to the history
		 * @return string html code
	 */
	function getAttachment($aJot, $bMenu = true, $bIsDynamicallyLoad = false){
		$sHTML = '';
		$iViewer = bx_get_logged_profile_id();
		$CNF = &$this -> _oConfig -> CNF;
		
		$bIsLotAuthor = $this -> _oDb -> isAuthor($aJot[$CNF['FIELD_MESSAGE_FK']], $iViewer);
		if (!empty($aJot))
		{
			switch($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT_TYPE']])
			{
				case BX_ATT_TYPE_REPOST:
						$sHTML = $this -> getJotAsAttachment($aJot[$CNF['FIELD_MESSAGE_AT']]);
						break;
				case BX_ATT_TYPE_GIPHY:
                        $sHTML = $this -> parseHtmlByName('giphy.html', array(
                            'gif' => $aJot[$CNF['FIELD_MESSAGE_AT']],
                            'time' => time(),
                            'static' => $bIsDynamicallyLoad ? 'none' : 'flex',
                            'dynamic' => $bIsDynamicallyLoad ? 'block' : 'none',
                        ));
                        break;
				case BX_ATT_TYPE_REPLY:
						 $iJotId = (int)$aJot[$CNF['FIELD_MESSAGE_AT']];
						 if ($iJotId && ($aReplyJot = $this->_oDb->getJotById((int)$aJot[$CNF['FIELD_MESSAGE_AT']]))){							 
							  $sHTML = $this -> parseHtmlByName('reply.html', array(
									'id' => (int)$aJot[$CNF['FIELD_MESSAGE_AT']],
									'message' => $this->getReplyPreview($iJotId),
								));
						}
						break;
                case BX_ATT_TYPE_FILES_UPLOADING:
                case BX_ATT_TYPE_FILES:
                        $aUploadingFilesList = $aJot[$CNF['FIELD_MESSAGE_AT']] ? explode(',', $aJot[$CNF['FIELD_MESSAGE_AT']]) : array();
						$aFiles = $this -> _oDb -> getJotFiles($aJot[$CNF['FIELD_MESSAGE_ID']]);
						$aItems = array(
							'bx_repeat:images' => array(),
							'bx_repeat:files' => array(),
							'bx_repeat:videos' => array(),
							'bx_repeat:audios' => array(),
                            'bx_repeat:loading_placeholder' => array()
						);
						
						$aTranscodersVideo = $this -> getAttachmentsVideoTranscoders();
						$oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
						$oTranscoderMp3 = BxDolTranscoderAudio::getObjectInstance($this -> _oConfig -> CNF['OBJECT_MP3_TRANSCODER']);

						foreach($aFiles as &$aFile)
						{
    						    if (($iKey = array_search($aFile[$CNF['FIELD_ST_NAME']], $aUploadingFilesList)) !== FALSE)
    						        unset($aUploadingFilesList[$iKey]);

						        $isAllowedDelete = $this->_oDb->isAllowedToDeleteJot($aJot[$CNF['FIELD_MESSAGE_ID']], $iViewer, $aJot[$CNF['FIELD_MESSAGE_AUTHOR']], $bIsLotAuthor);
    				            $isVideo = $aTranscodersVideo && (0 == strncmp('video/', $aFile['mime_type'], 6)) && $aTranscodersVideo['poster']->isMimeTypeSupported($aFile['mime_type']);
								if ($oStorage -> isImageFile($aFile[$CNF['FIELD_ST_TYPE']]))
								{
								    $sPhotoThumb = '';
									if ($aFile[$CNF['FIELD_ST_TYPE']] != 'image/gif' && $oImagesTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']))
										$sPhotoThumb = $oImagesTranscoder->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);
									
									$sFileUrl = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
									$aItems['bx_repeat:images'][] = array(
										'url' => $sPhotoThumb ? $sPhotoThumb : $sFileUrl,
										'id' => $aFile[$CNF['FIELD_ST_ID']],
										'name' => $aFile[$CNF['FIELD_ST_NAME']],
										'delete_code' => $bMenu ? $this -> deleteFileCode($aFile[$CNF['FIELD_ST_ID']], $isAllowedDelete) : ''
									);
								}
								   elseif ($isVideo)
								{
									$aItems['bx_repeat:videos'][] = array(
										'id' => $aFile[$CNF['FIELD_ST_ID']],
										'video' => $this -> getVideoFilesToPlay($aFile),
										'delete_code' => $bMenu ? $this -> deleteFileCode($aFile[$CNF['FIELD_ST_ID']], $isAllowedDelete) : ''
									);
								}
                                elseif ($oTranscoderMp3 -> isMimeTypeSupported($aFile[$CNF['FIELD_ST_TYPE']]))
                                {
                                   $sFileUrl = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
                                   $sMp3File = $aFile[$CNF['FIELD_ST_EXT']] == 'mp3' ? $sFileUrl : $oTranscoderMp3->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);

                                   $aItems['bx_repeat:audios'][] = array(
                                        'id' => $aFile[$CNF['FIELD_ST_ID']],
                                        'title' => $aFile[$CNF['FIELD_ST_NAME']],
                                        'mp3' => $this -> audioPlayer($sMp3File, true),
                                        'bx_if:loading' => array(
                                            'condition' => !$sMp3File,
                                            'content' => array(
                                                'loading_img' => BxDolTemplate::getInstance()->getImageUrl('video-na.png')
                                            )
                                        ),
                                        'delete_code' => $bMenu ? $this -> deleteFileCode($aFile[$CNF['FIELD_ST_ID']], $isAllowedDelete) : ''
									);
								}
								else
									$aItems['bx_repeat:files'][] = array(
																			'file' => $this -> parseHtmlByName('a_file.html', 
																															  array(
																																		'file' => $this -> parseHtmlByName('file.html', array(
																																							'type' => $oStorage -> getFontIconNameByFileName($aFile[$CNF['FIELD_ST_NAME']]),
																																							'name' => $aFile[$CNF['FIELD_ST_NAME']],
																																							'file_type' => $aFile[$CNF['FIELD_ST_TYPE']],																		
																																		)),
																																		'id' => $aFile[$CNF['FIELD_MESSAGE_ID']],
																																		'url' => BX_DOL_URL_ROOT																																	
																																	)),
																			'delete_code' => $bMenu ? $this -> deleteFileCode($aFile[$CNF['FIELD_MESSAGE_ID']], $isAllowedDelete) : ''
																		);								
						}

                        foreach($aUploadingFilesList as $sFileName)
                            $aItems['bx_repeat:loading_placeholder'][] = array(
                                'url' => $this->getImageUrl('audio-na.png'),
                                'name' => $sFileName,
                            );
						
						$sHTML = $this -> parseHtmlByName('files.html', $aItems);
						break;
			}
		}

		return $sHTML;
	}

	public function getFileContent($aFile){
        $CNF = &$this -> _oConfig -> CNF;

        $oStorage = new BxMessengerStorage($this->_oConfig-> CNF['OBJECT_STORAGE']);
        if ($oStorage -> isImageFile($aFile[$CNF['FIELD_ST_TYPE']]))
        {
            $sPhotoThumb = '';
            if ($aFile[$CNF['FIELD_ST_TYPE']] != 'image/gif' && $oImagesTranscoder = BxDolTranscoderImage::getObjectInstance($CNF['OBJECT_IMAGES_TRANSCODER_PREVIEW']))
                $sPhotoThumb = $oImagesTranscoder->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);

            $sFileUrl = BxDolStorage::getObjectInstance($this->_oConfig-> CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
            return $this -> parseHtmlByName('img.html', array(
                                                                        'url' => $sPhotoThumb ? $sPhotoThumb : $sFileUrl,
                                                                        'name' => $aFile[$CNF['FIELD_ST_NAME']],
                                                                        'id' => $aFile[$CNF['FIELD_ST_ID']]
                                                                    ));
        }

        $aTranscodersVideo = $this -> getAttachmentsVideoTranscoders();
        $isVideo = $aTranscodersVideo && (0 == strncmp('video/', $aFile['mime_type'], 6)) && $aTranscodersVideo['poster']->isMimeTypeSupported($aFile['mime_type']);
        if ($isVideo)
              return $this -> parseHtmlByName('video.html', array(
                                                                            'id' => $aFile[$CNF['FIELD_ST_ID']],
                                                                            'video' => $this->getVideoFilesToPlay($aFile),
                                                                        ));

        $oTranscoderMp3 = BxDolTranscoderAudio::getObjectInstance($this -> _oConfig -> CNF['OBJECT_MP3_TRANSCODER']);
        if ($oTranscoderMp3 -> isMimeTypeSupported($aFile[$CNF['FIELD_ST_TYPE']]))
        {
                $sFileUrl = BxDolStorage::getObjectInstance($CNF['OBJECT_STORAGE'])->getFileUrlById((int)$aFile[$CNF['FIELD_ST_ID']]);
                $sMp3File = $aFile[$CNF['FIELD_ST_EXT']] == 'mp3' ? $sFileUrl : $oTranscoderMp3->getFileUrl((int)$aFile[$CNF['FIELD_ST_ID']]);

                return $this -> parseHtmlByName('audio.html', array(
                                                                    'id' => $aFile[$CNF['FIELD_ST_ID']],
                                                                    'title' => $aFile[$CNF['FIELD_ST_NAME']],
                                                                    'mp3' => $this -> audioPlayer($sMp3File, true)
                                                                ));
        }


         return $this -> parseHtmlByName('a_file.html', array(
																'file' => $this -> parseHtmlByName('file.html', array(
																			'type' => $oStorage -> getFontIconNameByFileName($aFile[$CNF['FIELD_ST_NAME']]),
																			'name' => $aFile[$CNF['FIELD_ST_NAME']],
																			'file_type' => $aFile[$CNF['FIELD_ST_TYPE']],																		
																		)),
																'id' => $aFile[$CNF['FIELD_MESSAGE_ID']],
																'url' => BX_DOL_URL_ROOT
															));
    }

	/**
	* Returns Jot content as attachment(repost) for a message
	*@param int $iJotId jot id
	*@return string html code
	*/
	function getJotAsAttachment($iJotId){
		$sHTML = '';
		
		$aJot = $this -> _oDb -> getJotById($iJotId);
		if (empty($aJot))
			return $sHTML;

		$iAttachedJotId = $this -> _oDb -> hasAttachment($iJotId);
		if ($iJotId != $iAttachedJotId)
		{
			$sOriginalMessage = $this->_oConfig->cleanRepostLinks($aJot[$this->_oConfig->CNF['FIELD_MESSAGE']], $iAttachedJotId);
			if (!$sOriginalMessage)
				$aJot = $this -> _oDb -> getJotById($iAttachedJotId);
		}
		
		if ($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_FILES || $aJot[$this->_oConfig->CNF['FIELD_MESSAGE_AT_TYPE']] == BX_ATT_TYPE_GIPHY)
			$sMessage = $aJot[$this->_oConfig->CNF['FIELD_MESSAGE']] . $this -> getAttachment($aJot, false);
		else
			$sMessage = $this -> _oConfig -> bx_linkify($aJot[$this->_oConfig->CNF['FIELD_MESSAGE']]);
		
		if (!empty($aJot))
		{
			$aLotsTypes = $this -> _oDb -> getLotsTypesPairs();
			$oProfile = $this -> getObjectUser($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_AUTHOR']]);
			$aLotInfo =  $this -> _oDb -> getLotByJotId($iJotId, false);
			$sHTML = $this -> parseHtmlByName('repost.html', array(
					'icon' => $oProfile -> getThumb(),
					'message' => $sMessage,
					'username' => $oProfile -> getDisplayName(),
					'message_type' => !empty($aLotInfo) && isset($aLotInfo[$this->_oConfig->CNF['FIELD_TYPE']])? _t('_bx_messenger_lots_message_type_' . $aLotsTypes[$aLotInfo[$this->_oConfig->CNF['FIELD_TYPE']]]) : '',
					'date' => bx_process_output($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_ADDED']], BX_DATA_DATETIME_TS),
				));
		}
		
		return $sHTML;
	}
	
	/**
	* Returns user profile even if it was removed from the site 
	*@param int $iProfileId profile id 
	*@return object instance of Profile
	*/
	public function getObjectUser($iProfileId)
	{
		bx_import('BxDolProfile');
		$oProfile = BxDolProfile::getInstance($iProfileId);
		if (!$oProfile)
		{
			bx_import('BxDolProfileUndefined');
			$oProfile = BxDolProfileUndefined::getInstance();
		}

		return $oProfile;
	}

    private function getFileMenuCode($iFileId, $bAllowedDelete){
        $CNF = &$this->_oConfig->CNF;

        $aMenuItems = array(
            array(
                'click' => "javascript:window.open('" . $this->_oConfig->getBaseUri() . "download_file/{$iFileId}" . "');",
                'title' => _t('_bx_messenger_file_download'),
                'icon' => 'download'
            ),
            array(
                'permissions' => true,
                'click' => "if (confirm('" . bx_js_string(_t('_bx_messenger_post_confirm_delete_file')) . "')) 
                                                {$CNF['JSMain']}.removeFile(this, {$iFileId})",
                'title' => _t('_bx_messenger_upload_delete'),
                'icon' => 'backspace'
            ),
        );

         $aVars = array('class' => 'file-menu', 'position' => 'left center');
         foreach ($aMenuItems as &$aItem) {
                if (isset($aItem['permissions']) && $aItem['permissions'] === true && !$bAllowedDelete)
                    continue;

                $aVars['bx_repeat:menu'][] = $aItem;
         }

        return $this->parseHtmlByName('popup-menu-item.html', $aVars);
    }

	/**
	* Returns right side file's menu in talk history. Allows to  remove or download the file
	*@param int $iFileId file id in storage table
	*@param boolean $bIsDeleteAllowed is the vendor of the file
	*@return string html
	*/
	public function deleteFileCode($iFileId, $bIsDeleteAllowed = false){
        if (!$bIsDeleteAllowed)
            return '';

	    return $this -> parseHtmlByName('file_menu.html', array(
                    'file_menu' => BxTemplStudioFunctions::getInstance()->transBox("jot-menu-file-{$iFileId}", $this -> getFileMenuCode($iFileId, $bIsDeleteAllowed), true)
				));
	}

	/**
	* Returns files uploading form
	*@param int $iJotId message id
	*@return string html form
	*/
	public function getEditJotArea($iJotId)
	{
		$aJot = $this -> _oDb -> getJotById($iJotId);
		return $this -> parseHtmlByName('edit_jot.html', array(
			'place_holder' => _t('_bx_messenger_post_area_message'),
			'content' => $aJot[$this->_oConfig->CNF['FIELD_MESSAGE']]
        ));
	}

	/**
	* Returns body of the jot
	*@param int $iJotId id of the jot
	*@return string html form
	*/	
	public function getJotsBody($iJotId)
	{
		$aJot = $this -> _oDb -> getJotById($iJotId);
		if (empty($aJot))
			return '';

		$sMessage = $this -> _oConfig -> bx_linkify($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE']]);
		$sAttachment = !empty($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT_TYPE']]) ? $this -> getAttachment($aJot) : '';
		$aVars = array(
			'message' => $sMessage,
			'attachment' => $sAttachment
		);
		
		return $this -> parseHtmlByName('hidden_jot.html',  $aVars);
	}

	/**
	* Returns Video Recording form
	*@param int $iProfile viewer profile id
	*@return string html form
	*/
	public function getVideoRecordingForm(){
		return $this -> parseHtmlByName('video_record_form.html', array('max_video_length' => (int)$this->_oConfig->CNF['MAX_VIDEO_LENGTH']  * 60 * 1000));
	}

    function audioPlayer($sUrlMP3, $bReturnBothIfEmpty = false, $aAttrs = false, $sStyles = '')
    {
        $aAttrsDefaults = array(
            'controls' => '',
            'loop' => '',
            'preload' => 'metadata',
            'download' => true
        );

        if ($bReturnBothIfEmpty && !$sUrlMP3)
            unset($aAttrsDefaults['controls']);

        $aAttrs = array_merge($aAttrsDefaults, is_array($aAttrs) ? $aAttrs : array());
        $sAttrs = bx_convert_array2attrs($aAttrs, '', $sStyles);

        $sLoading = '<img style="max-height:3rem;" src="' . $this->getImageUrl('audio-na.png') . '" />';

        $sAudio = "<audio {$sAttrs}>
                     " . ($sUrlMP3 ? '<source type="audio/mp3" src="' . $sUrlMP3 .'" />' : '') . "
                   </audio>";

        if ($bReturnBothIfEmpty && !$sUrlMP3)
            return $sLoading . $sAudio;

        return $sUrlMP3 ? $sAudio : $sLoading;
    }

    public function getGiphyPanel(){
	    return $this -> parseHtmlByName('giphy_panel.html', array());
    }

    public function getGiphyForm($sId){
        $CNF = &$this->_oConfig->CNF;
        if (!$sId || !$CNF['GIPHY']['api_key'])
            return MsgBox(_t('_bx_messenger_giphy_gifs_nothing_found'));

        return $this -> parseHtmlByName('giphy_form.html', array(
            'id' => $sId,
            'time' => time()
        ));
    }

    public function getGiphyItems($sAction, $sQuery, $fHeight = 0, $iStart = 0){
        $oResult = $this->_oConfig->getGiphyGifs($sAction, $sQuery, $iStart);
        $fGifHeight = $fHeight/200;
        $iTime = time();

        $aResult = array('pagination' => array(), 'content' => '');
        if ($oResult && ($aResult = json_decode($oResult, true))){
            if (!empty($aResult['data'])){
                $aVars['bx_repeat:gifs'] = array();
                foreach($aResult['data'] as &$aGif) {
                    $aImage = $aGif['images']['fixed_height'];
                    $aVars['bx_repeat:gifs'][] = array(
                        'id' => $aGif['id'],
                        'width' => $aImage['width'] * $fGifHeight,
                        'height' => $fHeight,
                        'gif' => $aGif['id'],
                        'time' => $iTime,
                        'title' => $aGif['title']
                    );
                }

                if (!empty($aVars['bx_repeat:gifs']))
                    $aResult = array('pagination' => $aResult['pagination'], 'content' => $this->parseHtmlByName('giphy_items.html', $aVars));
            }
        }

        return $aResult;
    }

    public function getJitsi($iLotId, $iProfileId, $aOptions){
	    $CNF = &$this->_oConfig->CNF;
        $JITSI = &$CNF['JITSI'];
        $sError = '';

        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        if (empty($aLotInfo))
            $sError = MsgBox(_t('_bx_messenger_not_found'));

        if (!$this->_oDb->isParticipant($iLotId, $iProfileId))
            $sError = MsgBox(_t('_bx_messenger_not_participant'));

        if ($sError)
            return BxBaseFunctions::getInstance()->msgBox($sError, 2.5);

        $sTitle = isset($aLotInfo[$CNF['FIELD_TITLE']]) && $aLotInfo[$CNF['FIELD_TITLE']]
            ? $aLotInfo[$CNF['FIELD_TITLE']]
            : $this -> getParticipantsNames($iProfileId, $iLotId);

        $sTitle = _t($sTitle);

        $oProfileInfo = BxDolProfile::getInstance($iProfileId);
        $oLanguage = BxDolStudioLanguagesUtils::getInstance();
        $sLanguage = $oLanguage->getCurrentLangName(false);

        $aJVC = $this->_oDb->getJVC($iLotId);
        $sRoom = empty($aJVC) ? $this->_oConfig->getRoomId($aLotInfo[$CNF['FIELD_ID']], $aLotInfo[$CNF['FIELD_AUTHOR']]) : $aJVC[$CNF['FJVC_ROOM']];

        $mixedJWT = $this->getJWTToken($sRoom, $iProfileId);
        $sCode = $this -> parseHtmlByName('jitsi_video_form.html', array(
            'id' => $iLotId,
            'domain' => $this->_oConfig->getValidUrl($CNF['JITSI-SERVER']),
            'lang' => $sLanguage,
            'site_title' =>  bx_js_string(getParam('site_title')),
            'lib_link' => $this->_oConfig->getValidUrl($CNF['JITSI-SERVER'], 'url') . '/' . $JITSI['LIB-LINK'],
            'info_enabled' => +$CNF['JITSI-HIDDEN-INFO'],
            'chat_enabled' => +$CNF['JITSI-CHAT'],
            'chat_sync' => +$CNF['JITSI-CHAT-SYNC'],
            'audio_only' => +isset($aOptions['audio_only']),
            'show_watermark' => +$CNF['JITSI-ENABLE-WATERMARK'],
            'watermark_url' => $CNF['JITSI-WATERMARK-URL'],
            'support_link' => $CNF['JITSI-SUPPORT-LINK'],
            'jitsi_meet_title' => bx_js_string(_t('_bx_messenger_jitsi_meet_app_title', getParam('site_title'))),
            'user_name' => $oProfileInfo->getDisplayName(),
            'me' => _t('_bx_messenger_jitsi_meet_me'),
            'avatar' => $oProfileInfo->getAvatar(),
            'name' => $sRoom,
            'title' => bx_js_string(strmaxtextlen($sTitle)),
            'jwt_token' => $mixedJWT !== false ? $mixedJWT : ''
        ));

        return $sCode;
	}

    public function getPublicJitsi($iProfileId, $sIdent, $sTitle = '', $sId = 'bx-messenger-jitsi', $aInterfaceConfig = array()){
        $CNF = &$this->_oConfig->CNF;
        $JITSI = &$CNF['JITSI'];

        $oProfile = BxDolProfile::getInstance($iProfileId);
        if (empty($oProfile))
            return false;

        $sTitle = $sTitle ? $sTitle : $oProfile->getDisplayName();

        $oLanguage = BxDolStudioLanguagesUtils::getInstance();
        $sLanguage = $oLanguage->getCurrentLangName(false);

        $sRoom = $this->_oConfig->getRoomId($sIdent ? $sIdent : $iProfileId);        
        $mixedJWT = $this->getJWTToken($sRoom, $iProfileId);
        return $this -> parseHtmlByName('jitsi_public_video_form.html', array(
            'domain' => $this->_oConfig->getValidUrl($CNF['JITSI-SERVER']),
            'lang' => $sLanguage,
            'site_title' => getParam('site_title'),
            'lib_link' => $this->_oConfig->getValidUrl($CNF['JITSI-SERVER'], 'url') . '/' . $JITSI['LIB-LINK'],
            'info_enabled' => +$CNF['JITSI-HIDDEN-INFO'],
            'chat_enabled' => +$CNF['JITSI-CHAT'],
            'chat_sync' => +$CNF['JITSI-CHAT-SYNC'],
            'audio_only' => 0,
            'show_watermark' => +$CNF['JITSI-ENABLE-WATERMARK'],
            'watermark_url' => $CNF['JITSI-WATERMARK-URL'],
            'support_link' => $CNF['JITSI-SUPPORT-LINK'],
            'jitsi_meet_title' => bx_js_string(_t('_bx_messenger_jitsi_meet_app_title', getParam('site_title'))),
            'user_name' => $oProfile->getDisplayName(),
            'me' => _t('_bx_messenger_jitsi_meet_me'),
            'avatar' => $oProfile->getAvatar(),
            'name' => $sRoom,
            'id' => $sId,
            'title' => bx_js_string(strmaxtextlen($sTitle)),
            'interface_config' => json_encode($aInterfaceConfig),
            'jwt_token' => $mixedJWT !== false ? $mixedJWT : ''
        ));
    }

    function getJWTToken($sRoom, $iProfileId){
        $oProfileInfo = BxDolProfile::getInstance( $iProfileId );
        if (empty($oProfileInfo) || !$sRoom)
            return false;

        $CNF = $this->_oConfig->CNF;
        if (empty($CNF['JWT']['app_id']) || empty($CNF['JWT']['secret']))
            return false;

        // Encode Header to Base64Url String
        $sHeader = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $sHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($sHeader));

        // Create token payload as a JSON string
        $sPayload = json_encode(['context' =>
            [
                'user' => [
                    'avatar' => $oProfileInfo->getThumb(),
                    'name' => $oProfileInfo->getDisplayName(),
                    'email' => $oProfileInfo->getAccountObject()->getEmail()
                ],
            ],
            "aud" => $this->_oConfig->CNF['JITSI-SERVER'],
            "iss" => $CNF['JWT']['app_id'],
            "sub" => $this->_oConfig->CNF['JITSI-SERVER'],
            "room" => $sRoom
        ]);

        $sPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($sPayload));

        // Create Signature Hash
        $sSignature = hash_hmac('sha256', $sHeader . "." . $sPayload, $CNF['JWT']['secret'], true);
        $sSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($sSignature));

        return "{$sHeader}.{$sPayload}.{$sSignature}";
    }

    public function getTalkFiles($iProfileId, $iLotId, $iStart = 0){
        $CNF = &$this->_oConfig->CNF;
        if (!$iLotId || !$iProfileId || !$this->_oDb->isParticipant($iLotId, $iProfileId))
            return MsgBox(_t('_bx_messenger_no_permissions'));

        $sContent = MsgBox(_t('_bx_messenger_txt_msg_no_results'));
        $aFiles = $this -> _oDb -> getLotFiles($iLotId, $iStart, $CNF['PARAM_DEFAULT_TALK_FILES_NUM']);
        if (!empty($aFiles)) {
            $aFilesItems = array();
            foreach ($aFiles as &$aValue) {
                $oOwner = $this -> getObjectUser($aValue[$CNF['FIELD_ST_AUTHOR']]);
                $aFilesItems[] = array(
                    'time' => bx_time_js($aValue[$CNF['FIELD_ST_ADDED']]),
                    'file' => $this->getFileContent($aValue),
                    'id' => $aValue[$CNF['FIELD_ST_ID']],
                    'username' => $oOwner -> getDisplayName(),
                    'author_thumb' =>  $oOwner -> getThumb(),
                );
            }

            if (!empty($aFilesItems))
                $sContent = $this->parseHtmlByName('files_feeds.html', array('bx_repeat:files' => $aFilesItems));
        }

        return $iStart && !$sContent ? '' : $sContent;
    }

    public function getCallPopup($iLotId, $iProfileId){
        $CNF = &$this->_oConfig->CNF;
	    $aLotInfo = $this->_oDb->getLotInfoById($iLotId);
        $aActiveVC = $this->_oDb->getActiveJVCItem($iLotId);
        $oProfile = $this -> getObjectUser($aActiveVC[$CNF['FJVCT_AUTHOR_ID']]);
        if (empty($aLotInfo) || !$this->_oDb->isParticipant($iLotId, $iProfileId) || empty($aActiveVC) || (int)$iProfileId == (int)$aActiveVC[$CNF['FJVCT_AUTHOR_ID']] || !$oProfile)
            return false;

        $sTitle = isset($aLotInfo[$CNF['FIELD_TITLE']]) && $aLotInfo[$CNF['FIELD_TITLE']]
                ? _t($aLotInfo[$CNF['FIELD_TITLE']])
                : $this -> getParticipantsNames($iProfileId, $iLotId);


        $aJVC = $this->_oDb->getJVC($iLotId);
        if (empty($aJVC))
            return MsgBox(_t('_bx_messenger_jitsi_err_vc_was_not_found'));

        $aVars = array(
            'title' => $sTitle,
            'thumb' => $oProfile -> getThumb(),
            'id' => $iLotId,
            'room' => $aJVC[$CNF['FJVC_ROOM']]
        );

	    $sContent = $this->parseHtmlByName('conference_call.html', $aVars);
        return BxTemplFunctions::getInstance()->transBox('bx-messenger-vc-call', $sContent);
    }

    public function getLotSettingsForm($iLotId){
        $CNF = &$this->_oConfig->CNF;
        $aLotInfo = $this->_oDb->getLotInfoById($iLotId);

        if (empty($aLotInfo) || empty($CNF['LOT_OPTIONS']))
            $sContent = MsgBox(_t('_Empty'));
        else
        {
            $aOptions = array();
            $aLotSettings = $this->_oDb->getLotSettings($iLotId);
            foreach ($CNF['LOT_OPTIONS'] as &$sValue) {
                $aOptions[] = array(
                    'type' => 'checkbox',
                    'name' => $sValue,
                    'label' => _t("_bx_messenger_lot_options_item_{$sValue}"),
                    'checked' => $aLotSettings === false || in_array($sValue, $aLotSettings)
                );
            }

            $aForm = array(
                'form_attrs' => array(
                    'name' => 'lot_options',
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ),
                'inputs' => array(
                    'options' => array(
                        'type' => 'input_set',
                        'dv' => '<br/>'
                    ),
                    'buttons' => array(
                        'type' => 'input_set',
                        'attrs_wrapper' => array('class' => 'bx-messenger-options-buttons'),
                        array(
                            'type' => 'button',
                            'name' => 'save',
                            'value' => _t("_bx_messenger_save_button"),
                            'attrs' => array('onclick' => "javascript:{$CNF['JSMain']}.onSaveLotSettings(this);")
                        ),
                        array(
                            'type' => 'button',
                            'name' => 'close',
                            'value' => _t("_bx_messenger_cancel_button"),
                            'attrs' => array('onclick' => "javascript:$(this).closest('.bx-popup-applied:visible').dolPopupHide();")
                        ),
                    )
                )
            );

            if (!empty($aOptions))
                $aForm['inputs']['options'] = array_merge($aForm['inputs']['options'], $aOptions);


            $oForm = new BxTemplFormView($aForm);
            $sContent = $oForm -> getCode();
        }

        return BxTemplFunctions::getInstance()->popupBox('lot-settings', _t('_bx_messenger_lot_options_title'), $sContent);
    }
}

/** @} */
