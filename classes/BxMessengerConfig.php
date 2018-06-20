<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Messenger Messenger
 * @ingroup     UnaModules
 *
 * @{
 */

bx_import('BxDolPrivacy');

class BxMessengerConfig extends BxBaseModTextConfig
{
	function __construct($aModule)
	{
		parent::__construct($aModule);

		$this->CNF = array (

			// module icon
			'ICON' => 'comments-o col-green3',

			// database tables
			'TABLE_ENTRIES' => $aModule['db_prefix'] . 'lots',
			'TABLE_MESSAGES' => $aModule['db_prefix'] . 'jots',
			'TABLE_TYPES' => $aModule['db_prefix'] . 'lots_types',
			'TABLE_USERS_INFO' => $aModule['db_prefix'] . 'users_info',
			'TABLE_ENTRIES_FULLTEXT' => 'search_title',
				
			// database fields
			   
			// main lot table fields 
			'FIELD_ID' => 'id',
			'FIELD_AUTHOR' => 'author',
			'FIELD_ADDED' => 'created',
			'FIELD_PARTICIPANTS' => 'participants',			
			'FIELD_TITLE' => 'title',
			'FIELD_URL' => 'url',
			'FIELD_TYPE' => 'type',
			'FIELD_CLASS' => 'class',

			// storage tale 
			'FIELD_ST_ID' => 'id',
			'FIELD_ST_AUTHOR' => 'profile_id',
			'FIELD_ST_NAME' => 'file_name',
			'FIELD_ST_ADDED' => 'created',
			'FIELD_ST_JOT' => 'jot_id',
			'FIELD_ST_TYPE' => 'mime_type',
			 

			// messages/jots table fields
			'FIELD_MESSAGE_ID' => 'id',
			'FIELD_MESSAGE' => 'message',
			'FIELD_MESSAGE_FK' => 'lot_id',
			'FIELD_MESSAGE_AUTHOR' => 'user_id',
			'FIELD_MESSAGE_ADDED' => 'created',
			'FIELD_MESSAGE_NEW_FOR' => 'new_for',
			'FIELD_MESSAGE_AT_TYPE' => 'attachment_type',
			'FIELD_MESSAGE_AT' => 'attachment',
			'FIELD_MESSAGE_LAST_EDIT' => 'last_edit',
			'FIELD_MESSAGE_EDIT_BY' => 'edit_by',
			'FIELD_MESSAGE_TRASH' => 'trash',

			// lots types table fields
			'FIELD_TYPE_ID' => 'id',
			'FIELD_TYPE_NAME' => 'name',
			'FIELD_TYPE_LINKED' => 'show_link', // means use link in title

			// users info fields
			'FIELD_INFO_LOT_ID' => 'lot_id',
			'FIELD_INFO_USER_ID' => 'user_id',
			'FIELD_INFO_STAR' => 'star',
			'FIELD_INFO_PARAMS' => 'params', // means use link in title

			// page URIs  			
			'URL_HOME' => BX_DOL_URL_ROOT . 'page/messenger',
			'URL_REPOST' => BX_DOL_URL_ROOT . 'm/messenger/archive/',

			// some params
			'STAR_BACKGROUND_COLOR' => '#f5a623',
			'BELL_ICON_ON' => 'bell',
			'STAR_ICON' => 'star',
			'BELL_ICON_OFF' => 'bell-off',
			'PARAM_FRIENDS_NUM_BY_DEFAULT' => 10,
			'PARAM_SEARCH_DEFAULT_USERS' => 5,
			'PARAM_MAX_HISTORY_MESSAGES' => 50,
			'PARAM_PUSH_NOTIFICATIONS_DEFAULT_SYMBOLS_NUM' => 190,
			'PARAM_PRIVATE' => TRUE,
			'PARAM_PUBLIC' => FALSE,
			'PARAM_ICONS_NUMBER' => 3,
			'PARAM_MODULE_TYPES' => array(
											'bx_groups' => BX_IM_TYPE_GROUPS,
											'bx_events' => BX_IM_TYPE_EVENTS
										),
			'URL_IDENT_PARAMS' => array('i','r','id','profile_id'),
			// objects
			'OBJECT_STORAGE' => 'bx_messenger_files',
			'OBJECT_IMAGES_TRANSCODER_GALLERY' => 'bx_messenger_photos_resized',
			'OBJECT_IMAGES_TRANSCODER_PREVIEW' => 'bx_messenger_preview',
			'OBJECT_VIDEOS_TRANSCODERS' => array(
													'poster' => 'bx_messenger_videos_poster',
													'mp4' => 'bx_messenger_videos_mp4',
													'webm' => 'bx_messenger_videos_webm',
												),
			'OBJECT_VIEWS' => 'bx_messenger_lots',
			'OBJECT_FORM_ENTRY' => 'bx_messenger_lots',
			'OBJECT_FORM_ENTRY_DISPLAY_VIEW' => 'bx_messenger_lots',
			'OBJECT_MENU_ACTIONS_VIEW_ENTRY' => 'bx_messenger_view', // actions menu on view entry page
			'OBJECT_MENU_ACTIONS_MY_ENTRIES' => 'bx_messenger_my', // actions menu on my entries page
			'OBJECT_MENU_SUBMENU' => '', // main module submenu
			'OBJECT_MENU_MANAGE_TOOLS' => 'bx_messenger_menu_manage_tools', //manage menu in content administration tools
			'OBJECT_GRID' => 'bx_messenger',
			'OBJECT_UPLOADERS' => array(),

			//options
			'MAX_SEND_SYMBOLS'	=> (int)getParam($aModule['db_prefix'] . 'max_symbols_number'),
			'MAX_PREV_JOTS_SYMBOLS' => (int)getParam($aModule['db_prefix'] . 'max_symbols_brief_jot'),
			'MAX_JOTS_BY_DEFAULT' => (int)getParam($aModule['db_prefix'] . 'max_jot_number_default'),
			'MAX_JOTS_LOAD_HISTORY' => (int)getParam($aModule['db_prefix'] . 'max_jot_number_in_history'),
			'IS_PUSH_ENABLED' => getParam($aModule['db_prefix'] . 'is_push_enabled') == 'on',			
			'PUSH_APP_ID' => getParam('sys_push_app_id') ? getParam('sys_push_app_id') : getParam($aModule['db_prefix'] . 'push_app_id'),
			'PUSH_REST_API' => getParam('sys_push_rest_api') ? getParam('sys_push_rest_api') : getParam($aModule['db_prefix'] . 'push_rest_api'),
			'PUSH_SAFARI_WEB_ID' => getParam('sys_push_safari_id') ? getParam('sys_push_safari_id') : getParam($aModule['db_prefix'] . 'push_safari_id'),
			'PUSH_SHORT_NAME' => getParam('sys_push_short_name') ? getParam('sys_push_short_name') : getParam($aModule['db_prefix'] . 'push_short_name'),
			'SERVER_URL' => getParam($aModule['db_prefix'] . 'server_url'),
			'CONVERT_SMILES' => getParam($aModule['db_prefix'] . 'typing_smiles') == 'on',
			'MAX_FILES_TO_UPLOAD' => (int)getParam($aModule['db_prefix'] . 'max_files_send'),
			'MAX_VIDEO_LENGTH'	=> (int)getParam($aModule['db_prefix'] . 'max_video_length_minutes'),
		);	   
	}
	
	/**
	* Returns type of the talk 
	*@param string $sModule module uri, if messenger block was added to a special page of any module
	*@return int type
	*/
	public function getTalkType($sModule = ''){
		return $sModule && isset($this->CNF['PARAM_MODULE_TYPES'][$sModule]) ? $this->CNF['PARAM_MODULE_TYPES'][$sModule] : BX_IM_TYPE_PUBLIC; 
	}
	
	/**
	* Checks if the posted link is reporst link
	*@param string $sUrl internal post url
	*@return array, string url and int post id
	*/
	public function isJotLink($sUrl){
		$aResult = array();
		$sJotPattern = '/^'. preg_replace(array('/\./', '/\//'), array('\.', '\/'), $this->CNF['URL_REPOST']) . '(\d+)/i';
		if (preg_match($sJotPattern, $sUrl, $aMatches) && intval($aMatches[1]))
			$aResult = array('url' => $aMatches[0], 'id' => $aMatches[1]);
		
		return $aResult;
	}
	
	/**
	* Converts text link to url (wraps text url to <a>)
	*@param string $sText url
	*@param string $sAttrs special attributes for the link
	*@param boolean $bHtmlSpecialChars convert link to special html chars
	*@return string url
	*/
	public function bx_linkify($sText, $sAttrs = '', $bHtmlSpecialChars = false){
		if ($bHtmlSpecialChars)
			$sText = htmlspecialchars($sText, ENT_NOQUOTES, 'UTF-8');

		$sRe = "@\b((https?://)|(www\.))(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
		preg_match_all($sRe, $sText, $aMatches, PREG_OFFSET_CAPTURE);

		$aMatches = $aMatches[0];
		if ($i = count($aMatches))
			$bAddNofollow = getParam('sys_add_nofollow') == 'on';

		while ($i--)
		{
			$sUrl = $aMatches[$i][0];
			if (!preg_match('@^https?://@', $sUrl))
				$sUrl = 'http://'.$sUrl;

			if (strncmp(BX_DOL_URL_ROOT, $sUrl, strlen(BX_DOL_URL_ROOT)) !== 0) {
				$sAttrs .= ' target="_blank" ';
				if ($bAddNofollow)
					$sAttrs .= ' rel="nofollow" ';
			}

			$sReplacement = '';
			if ($this -> isJotLink($sUrl))
				$sReplacement = "<a href=\"{$sUrl}\">{$aMatches[$i][0]}</a>";
			else
			{
				$oEmbed = BxDolEmbed::getObjectInstance();
				if($oEmbed)
					$sReplacement = $oEmbed->getLinkHTML($sUrl, $aMatches[$i][0]);
				else
					$sReplacement = "<a {$sAttrs} href=\"{$sUrl}\">{$aMatches[$i][0]}</a>";				
			}
			
			$sText = substr_replace($sText, $sReplacement, $aMatches[$i][1], strlen($aMatches[$i][0]));
		}
		
		$mail_pattern = "/([A-z0-9\._-]+\@[A-z0-9_-]+\.)([A-z0-9\_\-\.]{1,}[A-z])/";
		$sText = preg_replace($mail_pattern, '<a href="mailto:$1$2">$1$2</a>', $sText);

		return $sText;
	}
	
	/**
	* Remove repost links from the message
	*@param string $sMessage message
	*@param int iJotId jot Id
	*@return string message
	*/
	public function cleanRepostLinks($sMessage, $iJotId)
	{
		$sArchiveUrl = $this->CNF['URL_REPOST'] . $iJotId;
		return str_replace($sArchiveUrl, '', $sMessage);
	}
	
	/**
	* Builds from server environment valid Url handle for lot 
	*@return string url
	*/
	public function getPageIdent()
	{
		$sUrl = $_SERVER['REQUEST_URI'];
		$aUrl = array();
		
		if (!empty($_SERVER['QUERY_STRING']))
		{
			parse_str($_SERVER['QUERY_STRING'], $aUrl);			
			if (!empty($aUrl))
			{
				$aValidUrl = array();
				foreach($this->CNF['URL_IDENT_PARAMS'] as &$sParam)
					if (!empty($aUrl[$sParam]))
						$aValidUrl[$sParam] = $aUrl[$sParam];
		
				if (!empty($aValidUrl))
					$sUrl = http_build_query($aValidUrl);
			}
		}
		
		return $sUrl;
	}
}

/** @} */
