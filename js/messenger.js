/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup	UnaModules
 * @{
 */
 
/**
 * Main messenger js file.
 */
var oMessenger = (function($){
	var _oMessenger = null;
	
	function oMessenger(oOptions){
		
		//list of selectors
		this.sJotsBlock = '.bx-messenger-block.jots',
		this.sMessangerParentBox = '.bx-messenger-post-box',
		this.sMessangerBox = '#bx-messenger-message-box',
		this.sSendButton = '.bx-messenger-post-box-send-button > a',
		this.sTalkBlock = '.bx-messenger-conversation-block',
		this.sMainTalkBlock = '.bx-messenger-main-block',
		this.sTalkList = '.bx-messenger-conversations',
		this.sJot = '.bx-messenger-jots',
		this.sTalkListJotSelector = this.sTalkList + ' ' + this.sJot,
		this.sItemsList = '.bx-messanger-items-list',
		this.sSendArea = '.bx-messenger-text-box',
		this.sJotMessage = '.bx-messenger-jots-message',
		this.sLotInfo = '.bx-messenger-jots-snip-info',
		this.sLotsListBlock = '.bx-messanger-items-list',
		this.sLotSelector = '.bx-messenger-jots-snip',
		this.sLotsListSelector = this.sLotsListBlock + ' ' + this.sLotSelector,
		this.sUserTopInfo = '.bx-messenger-top-user-info',
		this.sUserSelectorBlock = '#bx-messenger-add-users',
		this.sUserSelector = this.sUserSelectorBlock + ' input[name="users[]"]',
		this.sUserSelectorInput = '#bx-messenger-add-users-input',
		this.sInputAreaDisabled = 'bx-messenger-post-box-dsabled',
		this.sActiveLotClass = 'active',
		this.sUnreadLotClass = 'unread-lot',
		this.sStatus = '.bx-messenger-status';
		this.sBubble = '.bubble',
		this.sJotIcons = '.bx-messenger-jots-actions-list',
		this.sTypingArea = '.bx-messenger-conversations-typing span',
		this.sConnectingArea = '.bx-messenger-info-area-connecting',
		this.sConnectionFailedArea = '.bx-messenger-info-area-connect-failed',
		this.sInfoArea = '#bx-messenger-info-area',
		this.sSendAreaMenuIcons = '#bx-messenger-send-area-menu',
		this.sAddFilesFormComments = '#bx-messenger-files-upload-comment',
		this.sAddFilesForm = '#bx-messenger-files-uploader',
		//globa class options
		this.oUsersTemplate	= null,
		this.sJotUrl = sUrlRoot + 'm/messenger/archive/',
		this.iAttachmentUpdate = false,
		this.iTimer = null,
		this.iMaxLength = (oOptions && oOptions.max) || 0,
		this.iStatus = document.hasFocus() ? 1 : 2, // 1- online, 2-away
		this.iActionsButtonWidth = '2.25';
		this.iScrollDownSpeed = 1500;
		this.iHideUnreadBadge = 1000;
		this.iRunSearchInterval = 500, // seconds
		this.iMinHeightToStartLoading = 0, // scroll height to start history loading 
		this.iMinTimeBeforeToStartLoadingPrev = 500, // 2 seconds before to start loading history
		this.iTypingUsersTitleHide = 1000, //hide typing users div when users stop typing
		this.iLoadTimout = 0,
		this.iFilterType = 0,
		this.iStarredTalks = false,
		this.bActiveConnect = true,		
		this.iPanding = false, // don't update jots while prevous update is not finished yet
		this.aUsers = [],
		this.soundFile = 'modules/boonex/messenger/data/notify.wav'; //beep file, occurs when message received
		
		// Emoj config
		if (oOptions && oOptions.emoji)
				this.emojiPicker = new EmojiPicker(oOptions.emoji);
		
		// Lot's(Chat's) settings 
		this.oSettings = {
							'type'	: 'public',
							'url'	: '',
							'title' : document.title || '',
							'lot'	: 0,
							'user_id': (oOptions && oOptions.user_id) || 0 
						};
		
		// Real-time WebSockets framework class
		this.oRTWSF = (oOptions && oOptions.oRTWSF) || window.oRTWSF || null;
		
		// main messenger window builder
		this.oJotWindowBuilder = null;
	}

	/**
	* Init current chat/talk/lot settings
	*/
	oMessenger.prototype.initJotSettings = function(oOptions){		
		var	_this = this;
			oMessageBox = $(this.sMessangerBox),
			oActionsSiblings = {};
			
			
			this.oSettings.url = oOptions.url || window.location.href,
			this.oSettings.type = oOptions.type || this.oSettings.type,
			this.oSettings.lot = oOptions.lot || 0,
			this.oSettings.name = oOptions.name || '';
			
			// init smiles			
			if (this.emojiPicker != undefined)				
				this.emojiPicker.discover();	  		
			
			if (!_this.oRTWSF.isInitialized()){
				this.blockSendMessages(true);				
				return;
			}
			
			// send message area init
			$(this.sSendArea).on('keydown', function(oEvent){
				var iKeyCode = oEvent.keyCode || oEvent.which;		
							
					if (iKeyCode == 13){ 
						 if (oEvent.shiftKey !== true){ 												
								$(_this.sSendButton).click();
								oEvent.preventDefault();						
							}
					}
				
				if (_this.oRTWSF != undefined)
						_this.oRTWSF.typing({
								lot	:_this.oSettings.lot, 
								name:_this.oSettings.name, 
								user_id:_this.oSettings.user_id});
			});
			
			$(this.sSendButton).on('click', function(){
				_this.sendMessage(oMessageBox.val());
				oMessageBox.val('');
			});
			
			
			// start to load history depens on scrolling position
			$(_this.sTalkBlock).scroll(function(){
				if ($(this).scrollTop() <= _this.iMinHeightToStartLoading){
					_this.iLoadTimout = setTimeout(function(){
						_this.updateJots('prev');
					}, _this.iMinTimeBeforeToStartLoadingPrev);
				}
				else
					clearTimeout(_this.iLoadTimout);							
			});	
						
			this.updateSendAreaButtons();
			this.initJotIcons(this.sTalkList);
			this.updateScrollPosition('bottom');			
			
			// hide buttons on outside click
			$(_this.sTalkBlock + ',' + _this.sSendArea).on('click', function(){				
				_this.triggerSendAreaButtons(true);	
			});
			
			// show buttons on "+" icon click
			$(_this.sSendAreaMenuIcons).click( function(){				
				_this.triggerSendAreaButtons(false);
			});
		
			/* Init SVG Icons*/
			feather.replace();
	}
	
	
	/**
	* Init send message area buttons
	*/
	oMessenger.prototype.updateSendAreaButtons = function(){
		var _this = this,			
			oSmile = $(_this.sSendAreaMenuIcons).parents('ul').find('a#smiles').parent(),
			bSmile = oSmile.data('hide');

		if (typeof bSmile !== 'undefined' && ((_this.isMobile() && !!bSmile) || (!_this.isMobile() && !!!bSmile)))
			return;
		
		oSmile.data('hide', _this.isMobile());	
	}
	
	oMessenger.prototype.triggerSendAreaButtons = function(bHide){
		var _this = this,
			oParent = $(_this.sSendAreaMenuIcons).parent(),
		oSiblings = oParent.prevAll().filter(
						function(){
							return !!!$(this).data('hide');
						});
			
		if (!oSiblings.length)
			return;
					
		if (bHide){
			oSiblings.hide();
			oParent.fadeIn();
		}
			
		oParent.parents('ul').parent().
			animate(
					{
						'width': !bHide ? oSiblings.length * _this.iActionsButtonWidth + 'rem' : _this.iActionsButtonWidth + 'rem'
					}, 500, 
					function()
					{
						if (!bHide)
						{
							oParent.hide();
							oSiblings.css('display', 'inline');							
						}
					});	
	}
	
	oMessenger.prototype.blockSendMessages = function(bBlock){
		if (bBlock != undefined)
			this.bActiveConnect = !bBlock;
		
		if (!this.bActiveConnect && !$(this.sMessangerParentBox).hasClass(this.sInputAreaDisabled))
			$(this.sMessangerParentBox).addClass(this.sInputAreaDisabled);
		
		if (this.bActiveConnect)
			$(this.sMessangerParentBox).removeClass(this.sInputAreaDisabled);
	}
	
	oMessenger.prototype.isMobile = function(){
		return $(window).width() <= 720;
	}
	
	oMessenger.prototype.initJotIcons = function(oParent){
		var _this = this;
		
		if (!_this.isMobile())
			$(_this.sJotIcons, oParent).hover(
				function(){
					$('> div', this).fadeIn();
				},
				function(){
					$('> div', this).hide();
				}
			);
		else
		{
			$(_this.sJotIcons, oParent).on('click', function(){
				$(_this.sJotIcons + '> div').hide();
				$('> div', this).fadeIn();
				return false;
			});			
		}
		
	
		$(_this.sTalkBlock).on('scroll click', function(e){
			$(_this.sJotIcons + '> div').hide();		
		});
			
		$(_this.sJotIcons + ' > div a', oParent).click(function(){
			$(this).parent().hide();
			return false;
		});
	}
	
	/**
	* Update status of the member
	*@param object oData changed profile's settings
	*/
	oMessenger.prototype.updateStatuses = function(oData){
		var sClass = 'offline';
	
		switch(oData.status){
			case 1:
				sClass = 'online';
				break;
			case 2:
				sClass = 'away';
				break;
			default:
				sClass = 'offline';
		}

		$('b[data-user-status="' + oData.user_id + '"]').removeClass('online offline away').addClass(sClass).attr('title', _t('_bx_messenger_' + sClass));		
	}
	
	/**
	* Load logged member's message template	
	*/
	oMessenger.prototype.loadMembersTemplate = function(){
		var _this = this;
		
		if (_this.oUsersTemplate == null)
			$.get('modules/?r=messenger/load_members_template', 
				function(oData){
					if (oData != undefined && oData.data.length){					
						_this.oUsersTemplate = $(oData.data);						
					}
			}, 'json');	
	}
	
	/**
	* Search for lot
	*@param int iType type of the lot
	*@param string sText keyword for filter
	*/
	oMessenger.prototype.searchByItems = function(iType, sText){
		var _this = this,
			iFilterType	= iType != undefined ? iType : this.iFilterType,
			searchFunction = function()
							{
								bx_loading($(_this.sItemsList), true);
									$.get('modules/?r=messenger/search', {param:sText || '', type:iFilterType, starred: +_this.iStarredTalks}, 
										function(oData)
										{
											if (parseInt(oData.code) == 1) 
												window.location.reload();
											else
											if (!parseInt(oData.code)){					
													$(_this.sItemsList).html(oData.html).fadeIn();
													_this.iFilterType = iFilterType;								
												}
										}, 'json');	
							};

		if (typeof iType !== 'undefined' || typeof sText !== 'undefined'){
			clearTimeout(_this.iTimer);	
			this.iTimer = setTimeout(searchFunction, _this.iRunSearchInterval);	
		}
		else
			searchFunction();
	}
	
	/**
	* Create lot
	*@param object oOptions lot options
	*/
	oMessenger.prototype.createLot = function(oOptions){
		var _this = this,
			oParams = oOptions || {};
		
		bx_loading($(_this.sMainTalkBlock), true);		
		$.post('modules/?r=messenger/create_lot', {profile:oParams.user || 0, lot:oParams.lot || 0}, function(oData){			
			bx_loading($(_this.sMainTalkBlock), false);				
				if (parseInt(oData.code) == 1) 
						window.location.reload();
				else		
				if (!parseInt(oData.code)){
					
					$(_this.sJotsBlock).parent().html(oData.html).bxTime();
					
					_this.updateScrollPosition('bottom');
					_this.initUsersSelector(oParams.lot !== undefined ? 'edit' : '');
					
					if (_this.oJotWindowBuilder != undefined) 
						_this.oJotWindowBuilder.changeColumn('right');
				}
				
				_this.blockSendMessages();				
		}, 'json');	
	}
	
	oMessenger.prototype.saveParticipantsList = function(iLotId){
		var _this = this;
		if (iLotId)
				$.post('modules/?r=messenger/save_lots_parts', {lot:iLotId, participants:_this.getPatricipantsList()}, function(oData){
						if (parseInt(oData.code) == 1) 
							window.location.reload();

						alert(oData.message);
						if (!parseInt(oData.code)){
							_this.searchByItems();
							_this.loadTalk(iLotId);
						}
						
				}, 'json');
	}
	
	oMessenger.prototype.leaveLot = function(iLotId){
		var _this = this;
		if (iLotId)
			$.post('modules/?r=messenger/leave', {lot:iLotId}, function(oData){
				if (parseInt(oData.code) == 1) 
					window.location.reload();
				
				alert(oData.message);
				if (!parseInt(oData.code))
						_this.searchByItems();						
					}, 'json');
	}
	
	oMessenger.prototype.muteLot = function(iLotId, oEl){
		var _this = this,
			iVal = parseInt($(oEl).data('value'));
		
		$(oEl).html(!iVal ? feather.toSvg('bell-off') : feather.toSvg('bell'));
		$(oEl).data('value', +!iVal);
		
		$.post('modules/?r=messenger/mute', {lot:iLotId}, function(oData){
				if (typeof oData.code !== 'undefined')
					$(oEl).attr('title', oData.title);
		}, 'json');
	}
	
	oMessenger.prototype.starLot = function(iLotId, oEl){
		var _this = this,
			sColor = !parseInt($(oEl).data('value')) ? $(oEl).data('color') : 'none',
			iVal = parseInt($(oEl).data('value'));
		
		$('svg', oEl).attr({'fill': sColor, 'color': sColor});
		$(oEl).data('value', +!iVal);
		$.post('modules/?r=messenger/star', {lot:iLotId}, function(oData){
					if (typeof oData.code !== 'undefined')
						$(oEl).attr('title', oData.title);
				}, 'json');
	}

	oMessenger.prototype.deleteLot = function(iLotId){
		var _this = this;
		if (iLotId)
				$.post('modules/?r=messenger/delete', {lot:iLotId}, function(oData){						
					if (parseInt(oData.code) == 1) 
							window.location.reload();
		
						if (!parseInt(oData.code)){
							_this.searchByItems();
							
							if ($(_this.sLotsListSelector).length > 0)
									$(_this.sLotsListSelector).first().click();
						}
						
						alert(oData.message);
						
				}, 'json');
	}

	oMessenger.prototype.deleteJot = function(oObject){
		var oJot = $(oObject).parents(this.sJot),
			iJotId = oJot.data('id') || 0;
		
		if (iJotId && confirm(_t('_bx_messenger_remove_jot_confirm')))
			$.post('modules/?r=messenger/delete_jot', {jot:iJotId}, function(oData){						
					if (!parseInt(oData.code)) 
							oJot.fadeOut('slow', function(){
								$(this).remove();
							})
				}, 'json');
	}
	
	oMessenger.prototype.copyJotLink = function(oObject){
		var _this = this,
			iJotId = $(oObject).parents(_this.sJot).data('id') || 0,
			$oInput = $('<input>');
			
			if (iJotId){
				$('body').append($oInput);
				$oInput.val(_this.sJotUrl + iJotId).select();
				document.execCommand("copy");
				$oInput.remove();
			}
	}

	/**
	* Convert plan text links/emails to urls, mailto
	*@param string sText text of the message
	*/
	$.fn.linkify = function(){		
		var sUrlPattern = /\b(?:https?):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]/gim,
		// www, http, https
			sPUrlPattern = /(^|[^\/])(www\.[\S]+(\b|$))/gim,
		// Email addresses
			sEmailPattern = /[\w.]+@[a-zA-Z_-]+?(?:\.[a-zA-Z]{2,6})+/gim;
			
		var sUrl = '',
			oJot = $(_oMessenger.sJotMessage, this).get(0),
			sText = $(oJot).text()
				.replace(sUrlPattern, function(str){
					sUrl = str;
					return '<a class="bx-link" href="' + str + '">' + str + '</a>';
				})
				.replace(sPUrlPattern, function(str, p1, p2){
					sUrl = 'http://' + p2;
					return p1 + '<a class="bx-link" href="' + p2 + '">' + p2 + '</a>'
				})
				.replace(sEmailPattern, '<a href="mailto:$&">$&</a>');
		
		$(oJot).html(sText);
		if (sUrl.length)
			$(oJot).attacheLinkContent(sUrl);			
				
		return this;
	}
	
	/**
	* Add attachment for the urls
	*@param string sUrl internal or external link
	*@return object this
	*/
	$.fn.attacheLinkContent = function(sUrl){
		var _this = this;
		_oMessenger.iAttachmentUpdate = true;
		$.post('modules/?r=messenger/parse_link', {link:sUrl, jot_id:$(_this).parents(_oMessenger.sJot).data('id')}, function(oData){
				if (!parseInt(oData['code']))
					$(_this).parent().append(oData['html']);					
				else
				{
					// embedly/iframly links
					$('a.bx-link').dolConverLinks();
				}
				
				_oMessenger.broadcastMessage();
				_oMessenger.updateScrollPosition('bottom');
				
				_oMessenger.iAttachmentUpdate = false;				
			},
			'json');
			
		return this;
	}
	
	/**
	* Add attachment to the message
	*@param string sUrl internal or external link
	*@return object this
	*/
	oMessenger.prototype.attacheFiles = function(iJotId){
		var _this = this;
		
		_this.iAttachmentUpdate = true;
		$.post('modules/?r=messenger/get_attachment', {jot_id:iJotId}, function(oData){
				if (!parseInt(oData['code'])){
					$(_this.sJotMessage, '[data-id="' + iJotId + '"]').after(
						$(oData['html']).find('img').load(
							function()
							{
								_this.updateScrollPosition('bottom');
							}
						).end());
						
					/* Init SVG Icons*/
					feather.replace();
					
					_this.initJotIcons('[data-id="' + iJotId + '"]');						
					_this.broadcastMessage();
				}
				
				_this.iAttachmentUpdate = false;				
			},
			'json');
			
		return this;
	}
	
	/**
	* Select lot in left side column when member clicks on it
	*@param object el selected lot
	*/
	oMessenger.prototype.selectLotEmit = function(el){		
		$(el).addClass(this.sActiveLotClass).siblings().removeClass(this.sActiveLotClass).end().
				find(this.sLotInfo).removeClass(this.sUnreadLotClass).end().
				find(this.sBubble).fadeOut(this.iHideUnreadBadge).end();
	}
		
	/**
	* Load history for selected lot
	*@param int iLotId lot id
	*@param object el selected lot
	*/
	oMessenger.prototype.loadTalk = function(iLotId, el){
		var _this = this;
		
		if (this.isActiveLot(iLotId) && !this.isMobile()) return ;
	
		this.selectLotEmit(el);
		
		bx_loading($(this.sMainTalkBlock), true);
		$.post('modules/?r=messenger/load_talk', {id:iLotId}, function(oData){
			bx_loading($(_this.sMainTalkBlock), false);
				if (parseInt(oData.code) == 1) 
						window.location.reload();
				else
				if (!parseInt(oData.code)){
					$(_this.sJotsBlock).parent().html(oData.html).fadeIn(function(){
						if (_this.oJotWindowBuilder != undefined) 
								_this.oJotWindowBuilder.changeColumn();							
					}).bxTime();
							
					
					/*  copy current update member status to the top of the chat */
					var iUser = $(el).find(_this.sStatus).data('user-status');
					if (parseInt(iUser)){
						var classList = $(el).find(_this.sStatus).attr('class').split(/\s+/);						
						if (typeof classList[1] !== 'undefined'){							
							$('b[data-user-status="' + iUser + '"]').
								removeClass('online offline away').
								addClass(classList[1]).
								attr('title', _t('_bx_messenger_' + classList[1]));
						}
					}
					
					if (_this.isMobile()) 
							_this.correctUserStatus();
					
					// embedly/iframly links
					$('a.bx-link').dolConverLinks();
					
					_this.updateScrollPosition('bottom');
					_this.blockSendMessages();					
				}
		}, 'json');	
	}	
	
	/**
	* Change view of the lot participants if viewer uses mobile devise
	*/
	oMessenger.prototype.correctUserStatus = function(){
		var _this = this;
		$('.bx-messenger-block.jots .bx-messenger-participants-usernames').each(function(){
			$('.bx-messenger-status', $(this)).prependTo($('.name', $(this))).end().find('.status').remove();
		});
	}
	
	oMessenger.prototype.loadJotsForLot = function(iLotId){
		var _this = this;		
		
		bx_loading($(this.sMainTalkBlock), true);		
		$.post('modules/?r=messenger/load_jots', {id:iLotId}, function(oData){
			bx_loading($(_this.sMainTalkBlock), false);
			if (parseInt(oData.code) == 1) 
					window.location.reload();
						
			if (!parseInt(oData.code)){			
					$(_this.sMainTalkBlock).html(oData.html).fadeIn().bxTime();
					_this.updateScrollPosition('bottom');					
				}
		}, 'json');	
	}
		
	oMessenger.prototype.sendPushNotification = function(oData){		
			$.post('modules/?r=messenger/send_push_notification', oData);
	}

	/**
	* Main send message function, occurs when member send message
	*/
	oMessenger.prototype.sendMessage = function(sMessage, aFiles, fCallBack){
		var _this = this, 
			oParams = this.oSettings,
			msgTime = new Date();
		
		if (typeof aFiles !== 'undefined' && aFiles.length)
			oParams.files = aFiles;
		else
			oParams.files = undefined;
		
		oParams.participants = _this.getPatricipantsList();		
		if (!oParams.lot && !oParams.participants.length)
			return;
		
		if (!(sMessage.length && $.trim(sMessage).length) && typeof oParams.files == 'undefined')
			return;
		
		oParams.tmp_id = msgTime.getTime();

		// remove MSG (if it exists) from clean history page
		if ($('.bx-msg-box-container', _this.sTalkList).length)
				$('.bx-msg-box-container', _this.sTalkList).remove();	

		oParams.message = $.trim(sMessage);		
		if (oParams.message.length > this.iMaxLength) 
			oParams.message = oParams.message.substr(0, this.iMaxLength);

		if (oParams.message || typeof oParams.files !== 'undefined'){
			var sTmpMessage = $('<div>').text(oParams.message).html(); // convert HTML to valid text with tags
			// append content of the message to the history page
			$(_this.sTalkList).append(
									_this.oUsersTemplate.clone().
									attr('data-tmp', oParams.tmp_id). 
									find('time').attr('datetime', msgTime.toISOString()).end(). 
									find(_this.sJotMessage).text(sTmpMessage).addClass('new').end().
									bxTime()
								);
			
			_this.initJotIcons('[data-tmp="' + oParams.tmp_id + '"]');
			$(_this.sSendArea).html('');		
			
			/* Init SVG Icons*/
			feather.replace();
		}
		
		// save message to database and broadcast to all participants
		$.post('modules/?r=messenger/send', oParams, function(oData){
				switch(parseInt(oData.code))
				{
					case 0:
						var iJotId = parseInt(oData.jot_id);
											
						if (iJotId)
						{
							if (typeof oData.lot_id !== 'undefined')
								_this.oSettings.lot = parseInt(oData.lot_id);
												
							if (typeof oData.tmp_id != 'undefined')
								$(_this.sTalkList).
									find('[data-tmp="' + oData.tmp_id + '"]').
									attr('data-id', oData.jot_id).linkify();
									
							if (typeof oParams.files !== 'undefined')
								_this.attacheFiles(iJotId);
							
							_this.upLotsPosition(_this.oSettings);
						}
						
						if (!_this.iAttachmentUpdate)
							_this.broadcastMessage();
						
						break;					
					case 1:
						window.location.reload();
						break;
					default:
						alert(oData.message);
				}			
					if (typeof fCallBack == 'function')
						fCallBack();
			}, 'json');
			
		_this.updateScrollPosition('bottom');		
		return true;	
	}		
	
	oMessenger.prototype.broadcastMessage = function(){
		if (this.oRTWSF != undefined)
				this.oRTWSF.message({
										lot: this.oSettings.lot, 
										name: this.oSettings.name,
										user_id: this.oSettings.user_id
									});
	}
	
	/**
	* Get all participants from users selector area
	*/
	oMessenger.prototype.getPatricipantsList = function(){ 
		var list = [];
		
		if ($(this.sUserSelector).length){
			$(this.sUserSelector).each(function(){
				list.push($(this).val());
			});
		} 
		else if ($(this.sUserTopInfo).length){
			var iUserId = parseInt($(this.sUserTopInfo).data('user-id'));
			if (iUserId)
					list.push(iUserId);
		}
		
		return list;
	}
	
	/**
	* Move lot's brief to the top of the left side when new message received into it
	*@param object oData lot's settings
	*/
	oMessenger.prototype.upLotsPosition = function(oData){		
		var _this = this,
			lot = parseInt(oData.lot), 
			oLot = $('div[data-lot=' + lot + ']'),
			oNewLot = undefined;
		
		$.get('modules/?r=messenger/update_lot_brief', {lot_id: lot}, 
						function(oData){
									if (!parseInt(oData.code)){					
										oNewLot = $(oData.html);
										if (lot){
												if (!oLot.is(':first-child')){
														var sFunc = function(){
															$(_this.sLotsListBlock).prepend($(oNewLot).bxTime().fadeIn('slow'));
														};
														
														if (oLot.length)
															oLot.fadeOut('slow', function(){
																oLot.remove();
																sFunc();
															});
														else
															sFunc();
												}
												else
													oLot.replaceWith($(oNewLot).fadeTo(150, 0.5).fadeTo(150, 1).bxTime());
												
												if (_this.isActiveLot(lot))
													_this.selectLotEmit($(oNewLot));
										}		
									}
									
								}, 'json');
	}
	
	/**
	* Show member's typing area when member is typing a message
	*@param object oData profile info
	*/
	oMessenger.prototype.showTyping = function(oData) {	
		var _this = this,
			sName = oData.name != undefined ? (oData.name).toLowerCase() : '';
	
		if (oData.lot != undefined && this.isActiveLot(oData.lot)){			
			if (!~this.aUsers.indexOf(sName)) 
							this.aUsers.push(sName);
			
			$(this.sTypingArea).text(this.aUsers.join(','));			
			$(this.sInfoArea).fadeIn();
		}
		
		clearTimeout(this.iTimer);	
		this.iTimer = setTimeout(function(){
			$(_this.sInfoArea).fadeOut().find(_this.sTypingArea).html('');
			_this.aUsers = [];
		},_this.iTypingUsersTitleHide);				
	};
	
	oMessenger.prototype.onReconnecting = function(oData) {	
		var _this = this;
			
		$(this.sInfoArea).fadeIn();
		$(this.sTypingArea).parent().hide();
		$(this.sConnectingArea).show();	
		$(' > span', _this.sConnectingArea).html('');
		
		this.blockSendMessages(true);		
		clearInterval(this.iTimer);	

		this.iTimer = setInterval(function(){
			var sHTML = $(' > span', _this.sConnectingArea).html();
			sHTML += '.';
			$(' > span', _this.sConnectingArea).html(sHTML);
		}, 1000);		
	};
	
	oMessenger.prototype.onReconnected = function(oData) {	
		$(this.sConnectingArea).hide();
		$(this.sInfoArea).fadeOut();
		$(this.sTypingArea).parent().show();
		
		clearInterval(this.iTimer);
		
		this.blockSendMessages(false);
	};
	
	oMessenger.prototype.onReconnectFailed = function(oData) {	
		$(this.sConnectingArea).hide();
		$(this.sConnectionFailedArea).fadeIn();
		
		clearInterval(this.iTimer);
	};	
	
	/**
	* Check if specified lot is currntly active
	*@param int iId profile id 
	*@return boolean
	*/
	oMessenger.prototype.isActiveLot = function(iId){
		return parseInt(this.oSettings.lot) == iId;	
	}

	/**
	* Search for lot by participants list
	*@param int iId profile id 
	*/	
	oMessenger.prototype.findLotByParticipantsList = function(){
		var _this = this;
		$.post('modules/?r=messenger/find_lot', {participants:this.getPatricipantsList()}, 
			function(oData){
				_this.oJotWindowBuilder.resizeWindow();
				_this.loadJotsForLot(parseInt(oData.lotId));				
			}, 
		'json');
	}
	
	/**
	* Correct scroll position in history area depends on loaded messages (old history or new just received)
	*@param string sPosition position name
	*@param string sEff name of the effect for load 
	*@param object oObject any history item near which to place the scroll 
	*/
	oMessenger.prototype.updateScrollPosition = function(sPosition, sEff, oObject){
		var iPosition = 0,
			sEffect = sEff,
			iHeight = $(this.sTalkBlock).prop('scrollHeight'),
			_this = this;
				
		switch(sPosition){
			case 'top':
					iPosition = 0;
					break;
			case 'bottom':
					iPosition = iHeight;
					break;
			case 'position':
					iPosition = oObject != undefined ? oObject.position().top : 0;
					break;
		}
		
		if (sEffect == 'slow')
			$(this.sTalkBlock).animate({
											scrollTop: iPosition,
										 }, _this.iScrollDownSpeed);
		else 
			$(this.sTalkBlock).scrollTop(iPosition);	
	}
	
	/**
	* Sound when message received
	*/
	oMessenger.prototype.beep = function(){
		var playSound = null;
		try{
			playSound = new Audio(this.soundFile); 			
			if (!document.hasFocus()) {
				playSound.play();
			}
			
		}catch(e){
			console.log('Sound is not supported in your browser');			
		}		
	}
	
	/**
	* Upodate history area, occurs when new messages are received(move scroll to the very bottom) or member loads the history(move scroll to the very top)
	*@param string sLoad option shows just received or old from history
	*/
	oMessenger.prototype.updateJots = function(sLoad){
		var _this = this;
			sShow = sLoad || 'new',
			oObjects = $(this.sTalkListJotSelector),
			iStart = sShow == 'new' ? oObjects.last().data('id') : oObjects.first().data('id');
		
			if (_this.iPanding)
				return;
			
			if (sLoad == 'prev')
			   bx_loading($(this.sTalkBlock), true);	   
		   
			_this.iPanding = true;
			
			$.post('modules/?r=messenger/update', {url: this.oSettings.url, type: this.oSettings.type, start: iStart, lot: this.oSettings.lot, load:sShow}, 
			function(oData){
				var oList = $(_this.sTalkList);
				
				if (!parseInt(oData.code)){
						if (iStart == undefined) 
							oList.html('');
								
						if (sShow == 'new'){
							$(oData.html).filter(_this.sJot).each(function(){								
								if ($('div[data-id="' + $(this).data('id') + '"]', oList).length !== 0)
									$(this).remove();									
							}).appendTo(oList);
							
							_this.beep();							
						}	
						else 
							oList.prepend(oData.html);							
						
						oList.find(_this.sJot + ':hidden').fadeIn(function(){
								$(this).css('display', 'table-row');
								_this.initJotIcons(this);
						}).bxTime();
						
						// embedly/iframly links
						$('a.bx-link').dolConverLinks();
						
						_this.updateScrollPosition(
							sShow == 'new' ? 'bottom' : 'position', 
							sShow == 'new' ? 'slow' : '',
							sShow == 'new' ? null : $(oObjects.first())
						);
						
						/* Init SVG Icons*/
						feather.replace();			
				}
				
				_this.iPanding = false;
				
				if (sLoad == 'prev'){
					bx_loading($(_this.sTalkBlock), false);					
				}				
					
			}, 'json');
	}
	
	/**
	* Init user selector are when create or edit participants list of the lot
	*@param boolean bMode if used for edit or to create new lot
	*/
	oMessenger.prototype.initUsersSelector = function(bMode){
			var _this = this;
			
			$(_this.sUserSelectorInput).
				autocomplete({
								source: 'modules/?r=messenger/get_auto_complete',
								minLength: 1,
								width: 250,
								autoFocus: true,
								select: function(e, ui) {
															$(this).val(ui.item.value);
															$(this).trigger('selectuser', ui.item);
															e.preventDefault();
														}
							}).on({
								keyup : function(e, ui) {
									  if(/(188|13)/.test(e.which)) 
											$(this).trigger('selectuser', ui); 
								},					
								selectuser: function(e, item){
								  $(this).hide();						  
									  if (item != undefined)
											$(this).before('<b class="bx-def-color-bg-hl bx-def-round-corners">' +
															'<img class="bx-def-thumb bx-def-thumb-size bx-def-margin-sec-right" src="' + item.icon + '" /><span>'+ item.value + '</span>' + 
															'<input type="hidden" name="users[]" value="'+ item.id +'" /></b>');
									  
									  if (bMode != 'edit')
											_this.findLotByParticipantsList();
									  
									  $(this).show().val('').focus();
								}
							});
			
			$(_this.sUserSelectorBlock).on('click', 'b', function(){
					$(this).remove();					
					if (bMode != 'edit')
						_this.findLotByParticipantsList();
					
					$(_this.sUserSelectorInput).focus();
			});
	};
	
	/**
	* Returns object with public methods 
	*/
	return {
		/**
		* Init main Lot settings and object to work with (settings, real-time frame work, page builder and etc...)
		*@param object oOptions options
		*/
		init:function(oOptions){			
			var _this = this;
			if (_oMessenger != null) return true; 
				
			_oMessenger = new oMessenger(oOptions);
			
			/* Init users Jot template  begin */
			_oMessenger.loadMembersTemplate();
			/* Init users Jot template  end */
									
			/* Init sockets settings begin*/	
			if (_oMessenger.oRTWSF != undefined && _oMessenger.oRTWSF.isInitialized()){

				$(window).on('beforeunload', function(){
					if (_oMessenger.oRTWSF != undefined)
							_oMessenger.oRTWSF.end({
												user_id:oOptions.user_id
											 });
				});
				
				_oMessenger.oRTWSF.onTyping = function(oData){
					_this.onTyping(oData);
				};		

				_oMessenger.oRTWSF.onMessage = function(oData){
					_this.onMessage(oData);
				};
				
				_oMessenger.oRTWSF.onStatusUpdate = function(oData){					
					_this.onStatusUpdate(oData);
				};				
				
				_oMessenger.oRTWSF.onServerResponse = function(oData){					
					_this.onServerResponse(oData);
				};				
		
				_oMessenger.oRTWSF.onReconnecting = function(oData){					
					_oMessenger.onReconnecting(oData);
				};

				_oMessenger.oRTWSF.onReconnected = function(oData){					
					_oMessenger.onReconnected(oData);
				};
				
				_oMessenger.oRTWSF.onReconnectFailed = function(oData){					
					_oMessenger.onReconnectFailed(oData);
				};
				
				_oMessenger.oRTWSF.getSettings = function(){
					return $.extend({status:_oMessenger.iStatus}, _oMessenger.oSettings);
				};				
				
			}else{
				console.log('Real-time frameworks was not initialized');
				return false;
			}			
			/* Init connector settings end */			
			return true;
		},

		/**
		* Init Lot settings only (occurs when member selects any lot from lots list)
		*@param object oOptions options
		*/
		initJotSettings: function(oOptions){
			var _this = this;
			_oMessenger.initJotSettings(oOptions);
			
			$(document).on('dragenter dragover drop', function (e){
				e.stopPropagation();
				e.preventDefault();
			});
			
			$(_oMessenger.sTalkList + ',' + _oMessenger.sMessangerParentBox).on('drop', function (e){
				var files = e.originalEvent.dataTransfer.files;
				e.preventDefault();
							
				if (files.length)
					_this.showPopForm(undefined, function(){
						AqbDropZone.handleFiles(files);
					});
			});			
		},
		
		/**
		* Init settings, occurs when member opens the main messenger page
		*@param int iProfileId if profile id oà the person whom to talk 
		*@param object oBuilder page builder class
		*/
		initMessengerPage:function(iProfileId, oBuilder){
			_oMessenger.oJotWindowBuilder = oBuilder || window.oJotWindowBuilder;
			
			if (typeof oMessengerMemberStatus !== 'undefined'){
				oMessengerMemberStatus.init(function(iStatus){
					_oMessenger.iStatus = iStatus;
					if (typeof _oMessenger.oRTWSF !== "undefined")
						_oMessenger.oRTWSF.updateStatus({
											user_id:_oMessenger.oSettings.user_id,
											status:iStatus,
										 });
					});
			}
		
			if (typeof _oMessenger.oJotWindowBuilder !== "undefined"){		
				$(window).on('load resize', function(e){
						if (e.type == 'load'){
								if(iProfileId || $(_oMessenger.sLotsListSelector).length == 0) 
									_oMessenger.createLot({user:iProfileId});
								else
								if (!_oMessenger.isMobile() && $(_oMessenger.sLotsListSelector).length > 0)
									$(_oMessenger.sLotsListSelector).first().click();
						}
						else 
							_oMessenger.updateSendAreaButtons();
					
					_oMessenger.oJotWindowBuilder.resizeWindow();
				});

				_oMessenger.oJotWindowBuilder.loadRightColumn = function(){
					if ($(_oMessenger.sLotsListSelector).length > 0)
						$(_oMessenger.sLotsListSelector).first().click();
					else
						_oMessenger.createLot();
				};
			}
			else
			{
				console.log('Page Builder was not initialized');
			}
			
			/* Init SVG Icons*/
			feather.replace();
		},
		
		// init public methods
		loadTalk:function(iLotId, oEl){
			_oMessenger.loadTalk(iLotId, oEl);
			return this;
		},
		searchByItems:function(sText){
			_oMessenger.searchByItems(_oMessenger.iFilterType, sText);
			return this;
		},
		createLot:function createLot(oObject){
			_oMessenger.createLot(oObject);
			return this;
		},	
		onSaveParticipantsList:function(iLotId){ 
			_oMessenger.saveParticipantsList(iLotId);
			return this;
		},
		onLeaveLot: function(iLotId) {
			_oMessenger.leaveLot(iLotId);
			return this;
		},	
		onMuteLot: function(iLotId, oEl){
			_oMessenger.muteLot(iLotId, oEl);
			return this;
		},
		onStarLot: function(iLotId, oEl){
			_oMessenger.starLot(iLotId, oEl);
			return this;
		},
		onDeleteLot: function(iLotId){ 
			_oMessenger.deleteLot(iLotId);
			return this;
		},
		showLotsByType: function(iType){
			_oMessenger.searchByItems(iType);
			return this;
		},
		onDeleteJot:function(oObject){
			_oMessenger.deleteJot(oObject);
		},
		onCopyJotLink:function(oObject){
			_oMessenger.copyJotLink(oObject);
		},
		/**
		* Methods below occur when messenger gets data from the server
		*/
		onTyping: function(oData){
			_oMessenger.showTyping(oData);
			return this;
		},		
		onMessage: function(oData){
			_oMessenger.upLotsPosition(oData);
			_oMessenger.updateJots();
			return this;
		},
		onStatusUpdate: function(oData){
			_oMessenger.updateStatuses(oData);			
			return this;
		},
		onServerResponse: function(oData){
			_oMessenger.sendPushNotification(oData);			
			return this;
		},
		
		/**
		* Sends uploaded files to the talk
		*@param object oDropZone dropzone plugin object
		*/
		onSendFiles: function(oDropZone){
			var aFiles = [],
				sMessage = $(_oMessenger.sAddFilesFormComments).text();
			
			oDropZone.getAcceptedFiles().map(function(oFile){
				aFiles.push(oFile.name);
			});
			
			if (aFiles.length)
			{				
				if (_oMessenger.sendMessage(sMessage, aFiles, 
					function(){
						oDropZone.removeAllFiles();
						$(_oMessenger.sAddFilesFormComments).html('');
					}))
				{
					$(_oMessenger.sAddFilesForm).dolPopupHide({});
				}
			}
			else
				oDropZone.hiddenFileInput.click();
			
		},
		
		/**
		* Creates form for files uploading in popup
		*@param string sUrl link, if not specify default one will be used
		*@param function fCallback callback function,  executes on window show
		*/		
		showPopForm: function (sUrl, fCallback) {
			var _this = this,
				sUrl = 'modules/?r=messenger/' + (sUrl || 'get_upload_files_form'),
				sText = $(_oMessenger.sMessangerBox).val();

			$(window).dolPopupAjax({
				url: sUrl,
				id: {force: true, value: _oMessenger.sAddFilesForm.substr(1)},
				onShow: function() {
					$(_oMessenger.sAddFilesForm + ' .bx-popup-element-close').click(function() {
						$(_oMessenger.sAddFilesForm + ' .bx-btn.close').click();
					});
					
					if (typeof fCallback == 'function'){
						fCallback();
					}
					
					if (sText.length)
					{
						$(_oMessenger.sAddFilesFormComments).text(sText);
					}
				},				
				closeElement: true,
				closeOnOuterClick: false
			});
		},
		
		/**
		* Executes on files uploading window close 
		*@param string sMessage confirmation message
		*@param int iFilesNumber files number
		*/				
		onCloseUploadingForm:function(sMessage, iFilesNumber){
			if (!iFilesNumber || (iFilesNumber && confirm(sMessage)))
			{
				$(_oMessenger.sAddFilesFormComments).html('');
				$(_oMessenger.sAddFilesForm).dolPopupHide();
				return true;
			}
						
			return false;
		},
		
		/**
		* Occurs on image click, allows to make image bigger in popup.
		*@param int iId image file id
		*/	
		zoomImage:function(iId){						
			$(window).dolPopupAjax({
				url: 'modules/?r=messenger/get_big_image/' + iId + '/' + $(window).width() + '/' + $(window).height(),
				id: {force: true, value: 'bx-messenger-big-img'},
				top:'0px',
				left:'0px',			
				onBeforeShow: function() {
					$('#bx-messenger-big-img, #bx-messenger-big-img .bx-popup-element-close, #bx-messenger-big-img img, #bx-popup-fog').click(function() {
						$('#bx-messenger-big-img').dolPopupHide().remove();
					});					
				},
				closeElement: true
			});
		},
		
		/**
		* Show only marked as important lot
		*@param object oEl 
		*/
		showStarred:function(oEl){
			var sColor = $(oEl).data('color');

			if (!_oMessenger.iStarredTalks && sColor)
				$('svg', oEl).attr({'fill':sColor, 'color':sColor});
			else
				$('svg', oEl).attr({'fill':'none', 'color':'none'});
			
			_oMessenger.iStarredTalks = !_oMessenger.iStarredTalks;
			this.searchByItems($('#items').val());			
		},
		
		removeFile:function(oEl, id){
			$.get('modules/?r=messenger/delete_file', {id: id}, function(oData){
				if (!parseInt(oData.code)){
					if (!oData.empty_jot)
						$(oEl).parents('.delete').parent().fadeOut('slow', function(){
							$(this).remove();
						});
					else
						$(oEl).parents(_oMessenger.sJot).fadeOut('slow', function(){
							$(this).remove();
						});
				} 
				else
					alert(oData.message);
			}, 'json');
		},
		
		downloadFile:function(iFileId){
			$.get('modules/?r=messenger/download_file/' + iFileId, {id: iFileId}, function(oData){
				if (parseInt(oData.code))
					alert(oData.message);
			});
		}
	}
})(jQuery);

/** @} */
