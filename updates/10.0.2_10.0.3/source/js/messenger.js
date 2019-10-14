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
;window.oMessenger = (function($){
	let _oMessenger = null;
	
	function oMessenger(oOptions){
		
		//list of selectors
		this.sJotsBlock = '.bx-messenger-block.jots';
		this.sMessangerParentBox = '.bx-messenger-post-box';
		this.sMessengerBox = '#bx-messenger-message-box';
		this.sSendButton = '.bx-messenger-post-box-send-button > a';
		this.sTalkBlock = '.bx-messenger-conversation-block';
		this.sMainTalkBlock = '.bx-messenger-main-block';
		this.sTalkList = '.bx-messenger-conversations';
		this.sJot = '.bx-messenger-jots';
		this.sLotsBlock = '.bx-messenger-block-lots';
		this.sTalkListJotSelector = this.sTalkList + ' ' + this.sJot;
		this.sSendArea = '.bx-messenger-text-box';
		this.sJotMessage = '.bx-messenger-jots-message';
		this.sLotInfo = '.bx-messenger-jots-snip-info';
		this.sLotsListBlock = '.bx-messenger-items-list';
		this.sLotSelector = '.bx-messenger-jots-snip';
		this.sLotsListSelector = this.sLotsListBlock + ' ' + this.sLotSelector;
		this.sUserTopInfo = '.bx-messenger-top-user-info';
		this.sUserSelectorBlock = '#bx-messenger-add-users';
		this.sUserSelector = this.sUserSelectorBlock + ' input[name="users[]"]';
		this.sUserSelectorInput = '#bx-messenger-add-users-input';
		this.sInputAreaDisabled = 'bx-messenger-post-box-disabled';
		this.sActiveLotClass = 'active';
		this.sUnreadLotClass = 'unread-lot';
		this.sStatus = '.bx-messenger-status';
		this.sBubble = '.bubble';
		this.sJotIcons = '.bx-messenger-jots-actions-list > i';
		this.sJotMenu = '.bx-messenger-jots-icons';
		this.sTypingArea = '.bx-messenger-conversations-typing span';
		this.sConnectingArea = '.bx-messenger-info-area-connecting';
		this.sConnectionFailedArea = '.bx-messenger-info-area-connect-failed';
		this.sInfoArea = '#bx-messenger-info-area';
		this.sSendAreaMenuIcons = '#bx-messenger-send-area-menu';
		this.sAddFilesFormComments = '#bx-messenger-files-upload-comment';
		this.sAddFilesForm = '#bx-messenger-files-uploader';
		this.sEditJotArea = '.bx-messenger-edit-jot';
		this.sEditJotAreaId = '#bx-messenger-edit-message-box';
		this.sAttachmentArea = '.bx-messenger-attachment-area';
		this.sAttachmentBlock = '.bx-messenger-attachment';
		this.sAttachmentFiles = '.bx-messenger-attachment-files';
		this.sEmojiEditorClass = '.emoji-wysiwyg-editor';
		this.sHiddenJot = '.bx-messenger-hidden-jot';
		this.sDeletedJot = '.bx-messenger-jots-message-deleted';
		this.sMediaATArea = ['.bx-messenger-attachment-file-videos', ".bx-messenger-attachment-file-audio"];
		this.sFilesUploadAreaOnForm = '.bx-messenger-upload-area';
		this.sScrollArea = '.bx-messenger-area-scroll';
		this.sSelectedJot = '.bx-messenger-blink-jot';
		this.sTmpVideoFile = '.bx-messenger-attachment-temp-video';
		this.sJotMessageViews = '.view';
		this.sGiphyItems = '.bx-messenger-giphy-items';
		this.sGiphyBlock = '.bx-messenger-giphy';

		//globa class options
		this.oUsersTemplate	= null;
		this.sJotUrl = sUrlRoot + 'm/messenger/archive/';
		this.sInfoFavIcon = 'modules/boonex/messenger/template/images/icons/favicon-red-32x32.png';
		this.sJotSpinner = '<img src="modules/boonex/messenger/template/images/icons/jot-loading.gif" />';
		this.sDefaultFavIcon = $('link[rel="shortcut icon"]').attr('href');
		this.iAttachmentUpdate = false;
		this.iTimer = null;
		this.sEmbedTemplate = (oOptions && oOptions.ebmed_template) || '<a href="__url__">__url__</a>';
		this.iMaxLength = (oOptions && oOptions.max) || 0;
		this.iStatus = document.hasFocus() ? 1 : 2; // 1- online, 2-away
		this.iActionsButtonWidth = '2.25';
		this.iScrollDownSpeed = 1500;
		this.iHideUnreadBadge = 1000;
		this.iRunSearchInterval = 500; // seconds
		this.iMinHeightToStartLoading = 0; // scroll height to start history loading
		this.iMinTimeBeforeToStartLoadingPrev = 500; // 2 seconds before to start loading history
		this.iUpdateProcessedMedia = 30000; //  30 seconds to check updated media files
		this.iTypingUsersTitleHide = 1000; //hide typing users div when users stop typing
		this.iLoadTimout = 0;
		this.iFilterType = 0;
		this.iStarredTalks = false;
		this.bActiveConnect = true;
		this.iPanding = false; // don't update jots while previous update is not finished
		this.aUsers = [];
		this.lastEditText = '';
		this.soundFile = 'modules/boonex/messenger/data/notify.wav'; //beep file, occurs when message received
		this.emojiObject = oOptions.emoji || null;
		this.aExcludeKeys = [9,16,17,18,19,20,27,35,36,37,38,39,40,91,93,224,116];
		this.oStorage = null;
		this.oHistory = window.history || {};
		const _this = this;
		
		$(this).on('message', () => this.beep());

		// Lot's(Chat's) settings 
		this.oSettings = {
							'type'	: 1,
							'url'	: '',
							'title' : document.title || '',
							'lot'	: 0,
							'user_id': (oOptions && oOptions.user_id) || 0 
						};
		
		// Real-time WebSockets framework class
		this.oRTWSF = (oOptions && oOptions.oRTWSF) || window.oRTWSF || null;
		
		// main messenger window builder
		this.oJotWindowBuilder = null;

		$(window).on('popstate', function(){
			const iLot = _this.oHistory.state && _this.oHistory.state.lot;

			if (iLot)
				_this.loadTalk(iLot);
		});
	}

	/**
	* Init current chat/talk/lot settings
	*/
	oMessenger.prototype.initJotSettings = function(oOptions){		
		const _this = this,
			  oMessageBox = $(this.sMessengerBox);
	
			this.oSettings.url = oOptions.url || window.location.href;
			this.oSettings.type = oOptions.type || this.oSettings.type;
			this.oSettings.lot = oOptions.lot || 0;
			this.oSettings.name = oOptions.name || '';
	
			// init Emoj
			if (this.emojiObject)
			{
				this.emojiObject.set_focus = !this.isBlockVersion();
                this.emojiObject.popup_position = {'bottom' : '3rem'};
				this.emojiPicker = new EmojiPicker(this.emojiObject).discover();
			}	
	
			if (!_this.oRTWSF.isInitialized())
			{
				this.blockSendMessages(true);
				return;
			}

			// send message area init
			$(this.sSendArea).on('keydown', function(oEvent)
			{
				const iKeyCode = oEvent.keyCode || oEvent.which,
					  sMessage = oMessageBox.val();


				if (iKeyCode === 38 && $(_this.sTalkListJotSelector).length && !sMessage.length){
					const aJots = $(_this.sTalkListJotSelector).get().reverse();

						for(let i=0; i < aJots.length; i++) {
                          const oJot = $(`${_this.sJotMenu} i.backspace`, aJots[i]);
						    if (oJot.length) {
                                _this.editJot(oJot);
                                break;
                            }
                        }

					return false;
				}

				if (iKeyCode === 13)
				{
					if (oEvent.shiftKey !== true)
					{
						$(_this.sSendButton).click();
						oEvent.preventDefault();
					}
				}

				if (_this.oRTWSF !== undefined && !~$.inArray(iKeyCode, _this.aExcludeKeys)) /* exclude unnecessary key-down key codes  (Shift, Break and etc...) */
				{
					_this.oRTWSF.typing({
						lot: _this.oSettings.lot,
						name: _this.oSettings.name,
						user_id: _this.oSettings.user_id
					});
				}
			}).on('keyup', (oEvent) => {
				const iKeyCode = oEvent.keyCode || oEvent.which;
				if (!~$.inArray(iKeyCode, _this.aExcludeKeys)) {
					const sMessage = oMessageBox.val();

					if (sMessage.length)
						_this.oStorage.saveLot(_this.oSettings.lot, sMessage);
					else
						_this.oStorage.deleteLot(_this.oSettings.lot);
				}
			});
			
			$(this.sSendButton).on('click', function(){
				_this.sendMessage(oMessageBox.val());
				oMessageBox.val('');
				_this.oStorage.deleteLot(_this.oSettings.lot);
			});
						
			// start to load history depends on scrolling position
			$(_this.sTalkBlock).scroll(function(){
				var isScrollAvail = $(this).prop('scrollHeight') > $(this).prop('clientHeight'),
					isPrev = $(this).scrollTop() <= _this.iMinHeightToStartLoading,
					isNew = ($(this).prop('scrollHeight') - $(this).scrollTop() - _this.iMinHeightToStartLoading == $(this).prop('clientHeight')) && $(_this.sScrollArea).is(':visible');

				if ((isPrev || isNew) && isScrollAvail){
					_this.iLoadTimout = setTimeout(function(){
						_this.updateJots({
											action: isPrev ? 'prev' : 'new',
											position: isNew ? 'position' : undefined
										 });
					}, _this.iMinTimeBeforeToStartLoadingPrev);
				}
				else
					clearTimeout(_this.iLoadTimout);
			});

			/* runs periodic to find not processed videos in chat history and replace them with processed videos */
			setInterval(
						function()
							{
								_this.updateProcessedMedia();
							}, _this.iUpdateProcessedMedia
						);							
			
			this.updateSendAreaButtons();
			this.initJotIcons(this.sTalkList);
			this.updateScrollPosition('bottom');
			
			//remove all edit jot areas on their lost focus
			$(document).on('mouseup', function(oEvent){
				_this.removeEditArea(oEvent);
			});
			
			// hide buttons on outside click
			$(_this.sTalkBlock + ',' + _this.sSendArea).on('click', function(oEvent)
			{
				if (_this.isMobile())
					_this.triggerSendAreaButtons(true);	
				
				_this.removeEditArea(oEvent);
			});
			
			// show buttons on "+" icon click
			$(_this.sSendAreaMenuIcons).on('click', () => _this.triggerSendAreaButtons(false));

			_this.triggerSendAreaButtons(_this.isMobile());
	};

	oMessenger.prototype.checkNotFinishedTalks = function(){
		const oLots = this.oStorage.getLots(),
			  oLotsKeys = (oLots && Object.keys(oLots)) || [];

		if (oLotsKeys.length)
			oLotsKeys.map((iLot) =>  $(`${this.sLotSelector}[data-lot="${iLot}"] .info`).html( oLots[iLot].length ? '<i class="sys-icon pen"></i>' : ''));
	};
	
	/**
	* Init send message area buttons
	*/
		
	oMessenger.prototype.updateSendAreaButtons = function(){
		var _this = this,			
			oSmile = $(_this.sSendAreaMenuIcons)
						.parents('ul')
						.find('a.smiles')
						.parent(),
			bSmile = oSmile.data('hide');

		if (typeof bSmile !== 'undefined' && ((_this.isMobile() && !!bSmile) || (!_this.isMobile() && !bSmile)))
			return;
		
		oSmile.data('hide', _this.isMobile());	
	}

	/**
	 * Show/hide send message area buttons
	 *  
	 *  @param boolean bHide if true - hide buttons
	*/	
	oMessenger.prototype.triggerSendAreaButtons = function(bHide)
	{
		const _this = this,
			oParent = $(_this.sSendAreaMenuIcons).parent(),
			isSafari = /^((?!chrome|android|crios|fxios).)*safari/i.test(navigator.userAgent),
			oSiblings = oParent
							.prevAll(isSafari ? 'li:not(.video)' : '')
							.filter(function()
									{
										return !$(this).data('hide');
									});
		if (!oSiblings.length)
			return;
					
		if (bHide)
		{
			oSiblings.hide();
			oParent.fadeIn();
		}
			
		oParent
			.closest('ul')
			.parent()
			.animate(
					{
						'width': !bHide ? oSiblings.length * _this.iActionsButtonWidth + 'rem' : _this.iActionsButtonWidth + 'rem'
					}, 500, 
					function()
					{
						if (!bHide)
						{
							oParent.hide();
							oSiblings.show();
						}
					});	
	};
	
	oMessenger.prototype.blockSendMessages = function(bBlock){
		if (bBlock !== undefined)
			this.bActiveConnect = !bBlock;
		
		if (!this.bActiveConnect && !$(this.sMessangerParentBox).hasClass(this.sInputAreaDisabled))
			$(this.sMessangerParentBox).addClass(this.sInputAreaDisabled);
		
		if (this.bActiveConnect)
			$(this.sMessangerParentBox).removeClass(this.sInputAreaDisabled);
	}
	
	oMessenger.prototype.isMobile = function(){
		return $(window).width() <= 720;
	}
	
	oMessenger.prototype.isBlockVersion = function(){
		return $(this.sLotsBlock).length === 0;
	}
	
	oMessenger.prototype.removeEditArea = function(oEvent){
		const _this = this;
		$(this.sEmojiEditorClass, this.sEditJotArea).each(function()
		{
			if (!oEvent || !($(oEvent.target).parents(_this.sEditJotArea).length || $(oEvent.target).prop('tagName') === 'i'))
				$(this).trigger('close');
		});
	}
	
	oMessenger.prototype.initJotIcons = function(oParent){
		const _this = this,
			   fFire = function() {
				   $(this)
					   .siblings(_this.sJotMenu)
					   .mouseleave(function() {
					   		$(this).hide(0, () => $(this).unbind())
					   	})
					   .fadeIn();
			   };
		
		if (!_this.isMobile())
			$(_this.sJotIcons, oParent).mouseenter(fFire);
		else
		{
			$(_this.sJotIcons, oParent).on('click', function(){
				$(_this.sJotMenu).hide();
				fFire.call(this);
				return false;
			});			
		}
		
	
		$(_this.sTalkBlock).on('scroll click', function(e){
			$(_this.sJotMenu).hide();
		});
			
		$(`${_this.sJotMenu} a`, oParent).click(function(){
			$(this).parent().hide();
			return false;
		});
	}
		
	/**
	* Update status of the member
	*@param object oData changed profile's settings
	*/
	oMessenger.prototype.updateStatuses = function(oData){
		let sClass = 'offline';

		switch(oData.status)
		{
			case 1:
				sClass = 'online';
				break;
			case 2:
				sClass = 'away';
				break;
			default:
				sClass = 'offline';
		}

		$('div[data-user-status="' + oData.user_id + '"]')
			.removeClass('online offline away')
			.addClass(sClass)
			.attr('title', _t('_bx_messenger_' + sClass));
	}
	
	/**
	* Set users statuses
	*@param object oObject from which to copy status to all other places where status exists (to make all statuses similar)
	*/
	oMessenger.prototype.setUsersStatuses = function(oObject){
		const iUser = oObject
						.find(this.sStatus)
						.data('user-status');

		if (parseInt(iUser))
		{
			const classList = oObject
								.find(this.sStatus)
								.attr('class')
								.split(/\s+/);

			if (typeof classList[2] !== 'undefined')
			{
				$('div[data-user-status="' + iUser + '"]').
					removeClass('online offline away').
					addClass(classList[2]).
					attr('title', _t('_bx_messenger_' + classList[2]));
			}
		}
	};
	
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
	*@param int/function mixedOption type of the lot or calback function
	*@param string sText keyword for filter
	*/
	oMessenger.prototype.searchByItems = function(mixedOption, sText){
		const _this = this,
			iFilterType	= typeof mixedOption == 'function' || mixedOption === undefined ? this.iFilterType : mixedOption,
			searchFunction = function()
							{
								bx_loading($(_this.sLotsListBlock), true);
									$.get('modules/?r=messenger/search', {param:sText || '', type:iFilterType, starred: +_this.iStarredTalks}, 
										function(oData)
										{
											if (parseInt(oData.code) === 1)
												window.location.reload();
											else
											{
												const fCallback = () =>  typeof mixedOption === 'function' && mixedOption();
												
												if (!parseInt(oData.code))
												{			
													$(_this.sLotsListBlock)
														.html(oData.html)
														.bxTime()
														.fadeIn(fCallback);
													
													_this.iFilterType = iFilterType;
												}
												else
													fCallback();
											}
										}, 'json');	
							};

		if ((typeof mixedOption !== 'function' && mixedOption) || typeof sText !== 'undefined'){
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
		const _this = this,
			oParams = oOptions || {};
		
		bx_loading($(_this.sMainTalkBlock), true);
		$.post('modules/?r=messenger/create_lot', {profile:oParams.user || 0, lot:oParams.lot || 0}, function(oData){			
			bx_loading($(_this.sMainTalkBlock), false);
				if (parseInt(oData.code) === 1)
					window.location.reload();
				else		
					if (!parseInt(oData.code))
					{
						
						$(_this.sJotsBlock)
							.parent()
							.html(oData.html)
							.bxTime();
						
						if (typeof oData.title !== 'undefined')
							$(document).prop('title', oData.title);
					
						_this.updateScrollPosition('bottom');
						_this.initUsersSelector(oParams.lot !== undefined ? 'edit' : '');
						
						if (_this.oJotWindowBuilder !== undefined)
							_this.oJotWindowBuilder.changeColumn('right');
					}
				
				_this.blockSendMessages();
		}, 'json');	
	}
	
	oMessenger.prototype.saveParticipantsList = function(iLotId){
		const _this = this;
		let _iLotId = iLotId;

		$.post('modules/?r=messenger/save_lots_parts', 
		{
			lot:_iLotId, 
			participants:_this.getPatricipantsList()
		},
		function(oData)
		{
						const iResult = parseInt(oData.code);
					
						if (iResult === 1)
							alert(oData.message);
						else
						{
							if (!_iLotId)
								_iLotId = parseInt(oData.lot);

							if (!_this.isBlockVersion())
							{
								_this.searchByItems(
									function()
									{
										_this.oSettings.lot = null;
										_this.loadTalk(_iLotId, undefined, true);
									}
								);
							}
							else
								_this.loadTalk(_iLotId, undefined, true);
						}
						
		}, 'json');
	};
	
	oMessenger.prototype.updateCommentsAreaWidth = function(fWidth){
			$(this.sAddFilesFormComments)
				.css('max-width', fWidth ? fWidth : $(this.sAddFilesFormComments).parent().width());
	};

	oMessenger.prototype.leaveLot = function(iLotId){
		const _this = this;
		if (!iLotId)
			return false;

		$.post('modules/?r=messenger/leave', {lot:iLotId}, function(oData){
			alert(oData.message);
			if (!parseInt(oData.code))
					_this.searchByItems(() => $(_this.sLotsListSelector).length ?  $(_this.sLotsListSelector).first().click() : _this.createLot());
		}, 'json');
	};
	
	oMessenger.prototype.muteLot = function(iLotId, oEl){
		const iVal = parseInt($(oEl).data('value'));

		if (iVal)
			$('i.sys-icon', oEl)
				.removeClass('bell-slash').addClass('bell');
		else
			$('i', oEl)
				.removeClass('bell').addClass('bell-slash');

		$(oEl).data('value', +!iVal);

		$.post('modules/?r=messenger/mute', {lot:iLotId}, function(oData){
				if (typeof oData.code !== 'undefined')
					$(oEl).attr('title', oData.title);
		}, 'json');
	}
	
	oMessenger.prototype.starLot = function(iLotId, oEl){
		const iVal = parseInt($(oEl).data('value'));

		if (!iVal)
			$('i', oEl)
				.addClass('fill');
		else
			$('i', oEl)
				.removeClass('fill');

		$(oEl).data('value', +!iVal);

		$.post('modules/?r=messenger/star', {lot:iLotId}, function(oData){
					if (typeof oData.code !== 'undefined')
						$(oEl).attr('title', oData.title);
				}, 'json');
	}
	
	oMessenger.prototype.viewJot = function(iJotId){
		var _this = this,
			oObject = $(_this.sJotMessage, $('div[data-id="' + iJotId + '"]', _this.sTalkList));
		
		if (!oObject)
			return ;
		
		if ($(_this.sHiddenJot, oObject).length)
		{
			if ($(_this.sHiddenJot, oObject).is(':hidden'))
				$(_this.sHiddenJot, oObject).fadeIn('slow');
			else
				$(_this.sHiddenJot, oObject).fadeOut();
			
			return false;
		}
		
		bx_loading($(_this.sDeletedJot, oObject), true);
		$.post('modules/?r=messenger/view_jot', {jot:iJotId}, function(oData)
		{
			if (!parseInt(oData.code) && oData.html.length)
				$(oObject)
					.append(
							$(oData.html)
								.fadeIn('slow')
							);
			
			bx_loading($(_this.sDeletedJot, oObject), false);
		}, 'json');
	}

	oMessenger.prototype.deleteLot = function(iLotId){
		const _this = this;
		if (iLotId)
				$.post('modules/?r=messenger/delete', {lot:iLotId}, function(oData){
					if (parseInt(oData.code) == 1) 
							window.location.reload();
		
						if (!parseInt(oData.code))
						{
							_this.searchByItems(
								function()
								{
									if ($(_this.sLotsListSelector).length > 0)
									{
										$(_this.sLotsListSelector).first().click();
										if (_this.oJotWindowBuilder != undefined) 
											_this.oJotWindowBuilder.changeColumn('right');
									}
									else
										_this.createLot();
									
								}
							);
						}
				}, 'json');
	};

	oMessenger.prototype.deleteJot = function(oObject, bCompletely){
		var _this = this,
			oJot = $(oObject).parents(this.sJot),
			iJotId = oJot.data('id') || 0,
			checkScroll	= function()
			{
				if ($(_this.sTalkBlock).prop('scrollHeight') <= $(_this.sTalkBlock).prop('clientHeight'))
						_this.updateJots({
											action: 'prev',
											position: 'bottom'
										});
			};

		if (iJotId)
			$.post('modules/?r=messenger/delete_jot', {jot:iJotId, completely: +bCompletely || 0}, 
			function(oData)
			{
				if (!parseInt(oData.code))
				{
					if (!bCompletely && oData.html.length)
					{
						$(_this.sJotMessage, oJot)
							.fadeOut('slow',
								function()
								{
									$(this)
										.siblings(_this.sAttachmentArea)
										.fadeOut(function()
										{
											$(this).remove();
										})
										.end()
										.html(oData.html)
										.fadeIn('slow');
										checkScroll();

								})
							.unbind();
					}
					 else 
						$(oJot)
							.fadeOut('slow',
								function()
								{
									checkScroll();
									$(this).remove();
								});

					$(_this.sJotIcons, oJot)
						.remove();

					const oInfo = {
									jot_id:iJotId,
									addon:'delete'
								};
					
					if (!_this.isBlockVersion())
						_this.upLotsPosition($.extend(oInfo, _this.oSettings));

					_this.broadcastMessage(oInfo);
				}
			}, 'json');
	};
	
	oMessenger.prototype.cancelEdit = function(oObject){
		if (this.lastEditText.length);
		{
			$(oObject)
				.closest(this.sJot)
				.find(this.sJotMessage)
				.text(this.lastEditText)
				.parent()
				.linkify(true, true); // don't update attachment for message and don't broadcast as new message 
		}


		$(this.sMessengerBox).focus();
	};
		
	oMessenger.prototype.saveJot = function(oObject){
		const _this = this,
			oJot = $(oObject).parents(this.sJot),
			iJotId = oJot.data('id') || 0,
			sMessage = $.trim($(_this.sEditJotAreaId).val());

		if (!sMessage.length)
			return false;

		if (sMessage.localeCompare(_this.lastEditText) === 0)
		{
			_this.cancelEdit(oObject);
			return false;
		}
		
		if (iJotId)
		{
			$.post('modules/?r=messenger/edit_jot', {jot:iJotId, message:sMessage}, function(oData)
			{
				if (!parseInt(oData.code))
				{
					$(_this.sJotMessage, oJot)
						.text(sMessage)
						.parent()
						.linkify(false, true) // update attachment for the message, but don't broadcast as new message 
						.end()
						.append(oData.html || '');

					const oInfo = {
									jot_id:iJotId,
									addon:'edit'
								};
								
					if (!_this.isBlockVersion())
						_this.upLotsPosition($.extend(oInfo, _this.oSettings));
					
					_this.broadcastMessage(oInfo);
				}
			}, 'json');
			
			return true;
		}
			
		return false;
	}
	
	
	oMessenger.prototype.editJot = function(oObject){
		var oJot = $(oObject).parents(this.sJot),
			iJotId = oJot.data('id') || 0,
			_this = this;
		
		if ($(this.sEditJotArea).length)
			$(this.sEditJotArea).fadeOut().remove();
		
		if (iJotId)
		{
			bx_loading($(_this.sJotMessage, oJot).parent(), true);
			$.post('modules/?r=messenger/edit_jot_form', {jot:iJotId}, function(oData)
			{						
				bx_loading($(_this.sJotMessage, oJot).parent(), false);	
				if (!parseInt(oData.code))
				{						
					const sTmpText = $.trim($(_this.sJotMessage, oJot).text()),
						  sOriginalText = sTmpText.length ? sTmpText : $.trim($(_this.sEditJotAreaId, oData.html).text());
						  _this.lastEditText = sOriginalText;
						
					$(_this.sJotMessage, oJot)
						.html(oData.html)
						.find(_this.sEditJotArea)
						.fadeIn('slow', function()
						{
								if (_this.emojiObject)
								{
									const oEmoji = Object.create(_this.emojiObject),
										isEqual	= (sText) => $.trim(sText).localeCompare(sOriginalText) === 0;

										oEmoji.emojiable_selector = _this.sEditJotAreaId;
										oEmoji.menu_wrapper = undefined;

										let position = oJot.is(':last-child') ? 'bottom' : 'top';
										oEmoji.popup_position = {'right':0, [position] : 0};
										oEmoji.set_focus = true;
										oEmoji.custom_events = 
										{
											'close': function()
											{
												if (isEqual($(this).text()))
													_this.cancelEdit(this);
											},
											'keydown': function(oEvent)
											{
												const iKeyCode = oEvent.keyCode || oEvent.which,
													sText = $(oEmoji.emojiable_selector).val().length ? $(oEmoji.emojiable_selector).val() : sOriginalText;

												if (iKeyCode === 13)
												{
													if (oEvent.shiftKey !== true)
													{
														if (!isEqual(sText) && !_this.saveJot(this))
															_this.cancelEdit(this);
														
														oEvent.preventDefault();
													}
												}

												if (iKeyCode === 27 && isEqual(sText))
													_this.cancelEdit(this);

											}
										};
									
										oEmoji.add = true;
										new EmojiPicker(oEmoji).discover();
								}
								
								if (oJot.is(':last-child'))
									_this.updateScrollPosition('bottom');
						});
				}
			}, 'json');
		}
	}
	
	oMessenger.prototype.copyJotLink = function(oObject){
		var _this = this,
			iJotId = $(oObject)
						.parents(_this.sJot)
						.data('id') || 0,
			$oInput = $('<input>');
			
			if (iJotId)
			{
				$('body').append($oInput);
				$oInput.val(_this.sJotUrl + iJotId).select();
				document.execCommand("copy");
				$oInput.remove();
			}
	}

	/**
	* Convert plan text links/emails to urls, mailto
	*@param string sText text of the message
	*@param bool bDontBroadcast don't broadcast event as new message
	*@param bool bDontAddAttachment don't add repost link to the jot 
	*/
	$.fn.linkify = function(bDontAddAttachment, bDontBroadcast){
		let sUrlPattern = /\b(?:https?):\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|]/gim,
		// www, http, https
			sPUrlPattern = /(^|[^\/])(www\.[\S]+(\b|$))/gim,
		// Email addresses
			sEmailPattern = /[\w.]+@[a-zA-Z_-]+?(?:\.[a-zA-Z]{2,6})+/gim,
			sJotLinkPattern = new RegExp(_oMessenger.sJotUrl + '\\d+', 'i');


		const oJot = $(_oMessenger.sJotMessage, this).first();

		if (!$(oJot).length)
		return ;

		let sUrl = '',
			sText = $(oJot)
						.html()
						.replace(sUrlPattern, function(str)
						{
							sUrl = str;
							return !sJotLinkPattern.test(str) ? _oMessenger.sEmbedTemplate.replace(/__url__/g, str) : `<a href="${str}">${str}</a>`;
						})
						.replace(sPUrlPattern, function(str, p1, p2)
						{
							sUrl = 'http://' + p2;
							return p1 + (!sJotLinkPattern.test(str) ? _oMessenger.sEmbedTemplate.replace(/__url__/g, p2) : `<a href="${p2}">${p2}</a>`);
						})
						.replace(sEmailPattern, '<a href="mailto:$&">$&</a>');

		$(oJot).html(sText);
		if ($(oJot).siblings(_oMessenger.sAttachmentArea).length)
			return this;
		
		if (sUrl.length)
		{
			if (!bDontBroadcast)
				_oMessenger.iAttachmentUpdate = true;
		
			$(oJot)
				.attacheLinkContent(sUrl, bDontAddAttachment,
				function()
				{
					if (!bDontBroadcast)
					{
						_oMessenger.broadcastMessage();
						_oMessenger.updateScrollPosition('bottom');
						_oMessenger.iAttachmentUpdate = false;
					}
				});
		}
		else
		{
			$(oJot)
				.siblings(_oMessenger.sAttachmentArea)
				.remove() /* remove attachment block if exists*/
		}

		return this;
	}
	
	/**
	* Add attachment for the urls
	*@param string sUrl internal or external link
	*@param bool bDontAddAttachment don't attached link to the message jot to parse on html page
	*@param function fCallback call when ajax request is completed 
	*@return object this
	*/
	$.fn.attacheLinkContent = function(sUrl, bDontAddAttachment, fCallback){
		var _this = this;

		$.post('modules/?r=messenger/parse_link',
			{
				link:sUrl,
				jot_id:$(_this)
						.parents(_oMessenger.sJot)
						.data('id'),
				dont_attach: !!bDontAddAttachment
			},
			function(oData)
			{
				if (!parseInt(oData.code))
					$(_this)
						.parent()
						.find(_oMessenger.sAttachmentBlock)
						.remove() /* remove attachment if exists */
						.end()
						.append(oData.html);
				
				if (typeof fCallback == 'function')
					fCallback();
			},
			'json');
			
		return this;
	}
	
	/**
	* Check for not processed videos and update them, if they are ready 
	*/
	oMessenger.prototype.updateProcessedMedia = function(){
		const _this = this,
			  fMedia = (sMediaType, sType) => document.createElement(sMediaType.toLowerCase()).canPlayType(sType);

		let aMedia = [];

		$(this.sMediaATArea.join(','), this.sTalkList).each(
			function()
			{
				let __this = $(this);
				$(`${_this.sTmpVideoFile}, audio`, this).each(function(){
					if (!$('source', this).prop('src') || fMedia($(this).prop('tagName'), $('source', this).prop('type')) === '')
						aMedia.push($(__this).data('media-id'));
				});
			});
		
		if (aMedia.length)
			$.post('modules/?r=messenger/get_processed_media',{ media: aMedia },
			function(oData)
			{
				for(var i=0; i < aMedia.length; i++)
				{
					if (typeof oData[aMedia[i]] !== 'undefined') {
						$(`[data-media-id="${aMedia[i]}"] ${_this.sTmpVideoFile}`, _this.sTalkList)
							.replaceWith(oData[aMedia[i]]);

						$('[data-media-id="' + aMedia[i] + '"] .audio .file', _this.sTalkList)
							.replaceWith(oData[aMedia[i]]);
					}
				}
			}, 'json');
	}
		
	/**
	* Add attachment to the message
	*@param string sUrl internal or external link
	*@return object this
	*/
	oMessenger.prototype.attacheFiles = function(iJotId){
		const _this = this;
		_this.iAttachmentUpdate = true;
		$.post('modules/?r=messenger/get_attachment', {jot_id:iJotId}, function(oData)
		{
			if (!parseInt(oData['code']))
			{
				$(_this.sJotMessage, '[data-id="' + iJotId + '"]')
					.after(
							$(oData['html'])
								.waitForImages(
									function()
									{
										_this.updateScrollPosition('bottom');
									}
								));

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
	*@param object oBlock selected lot
	*/
	oMessenger.prototype.selectLotEmit = function(oBlock){
		oBlock.addClass(this.sActiveLotClass)
			.siblings()
			.removeClass(this.sActiveLotClass)
			.end()
			.find(this.sLotInfo)
			.removeClass(this.sUnreadLotClass)
			.end()
			.find(this.sBubble)
			.fadeOut(this.iHideUnreadBadge)
			.end();	
	}
	
	/**
	* Change page's favicon 
	*@param boolean bEnable 
	*/
	oMessenger.prototype.updatePageIcon = function(bEnable, iLot)
	{
		const oNewLots = $(`.${this.sUnreadLotClass}`),
			  iCurrentLot = $(this.sLotsListSelector).first().data('lot');

		let iUnreadLotsCount = oNewLots.length;

		if (iUnreadLotsCount === 1 && iLot !== undefined && iLot === iCurrentLot && (!this.isMobile() || (this.isMobile() && this.oJotWindowBuilder.isHistoryColActive())))
			iUnreadLotsCount = 0;

		this.notifyNewUnreadChats(iUnreadLotsCount);

		if (bEnable === true || iUnreadLotsCount)
			$('link[rel="shortcut icon"]').attr('href', this.sInfoFavIcon);
		else 
		if (bEnable === false || !iUnreadLotsCount)
			$('link[rel="shortcut icon"]').attr('href', this.sDefaultFavIcon);
	};
	
	$.fn.waitForImages = function(fCallback){
		var aImg = $('img', $(this)),
			iTotalImg = aImg.length,
			waitImgLoad = function()
			{
				iTotalImg--;
				if (!iTotalImg && typeof fCallback == 'function')
				{
					fCallback();
				}
			};

			if (!iTotalImg)
				fCallback();
			else
				aImg
					.load(waitImgLoad)
					.error(waitImgLoad);
		
		return this;
	};

    oMessenger.prototype.markJotsAsRead = function(iLotId, fCallback){
        $.post('modules/?r=messenger/mark_jots_as_read', {lot:iLotId}, function(oData){
            if (!parseInt(oData.code) && typeof fCallback === 'function')
                fCallback();
        }, 'json');
    };

    oMessenger.prototype.broadcastView = function(iJotId){
        return this.broadcastMessage({
            'jot_id': iJotId ? iJotId : $(this.sTalkListJotSelector).last().data('id'),
            'addon': 'check_viewed'
        });
    };

	/**
	* Load history for selected lot
	*@param int iLotId lot id
	*@param int iJotId jot id
	*@param bool bDontChangeCol don't change columns on mobile version 
	*@param bool bMarkAllAsRead allows to mark all the unread messages as read
	*@param function oCallback is called when history is loaded
	*@param object el selected lot
	*/
	oMessenger.prototype.loadTalk = function(iLotId, iJotId, bDontChangeCol, bMarkAllAsRead, fCallback){
		const _this = this,
			oLotBlock = $(this.sLotSelector + '[data-lot="' + iLotId + '"]');
				
		if (!iLotId) 
			return;

        _this.selectLotEmit(oLotBlock);

        if (_this.isActiveLot(iLotId) && _this.isMobile() && !bDontChangeCol)
		{
			_this.oJotWindowBuilder.changeColumn();
            _this.markJotsAsRead(iLotId, () => this.broadcastView());
            _this.updateScrollPosition('bottom');
            return;
		}

		bx_loading($(this.sMainTalkBlock), true);
		$.post('modules/?r=messenger/load_talk', {lot_id:iLotId, jot_id:iJotId, mark_as_read:+bMarkAllAsRead}, function(oData)
		{
			bx_loading($(_this.sMainTalkBlock), false);
				if (parseInt(oData.code) === 1)
					window.location.reload();
				else
				if (!parseInt(oData.code))
				{
					$(_this.sJotsBlock)
						.parent()
						.html(oData.html)
						.fadeIn(
							function()
							{
								if (_this.oJotWindowBuilder !== undefined)
								{
									if (!bDontChangeCol)
										_this.oJotWindowBuilder.changeColumn();
									else
										_this.oJotWindowBuilder.updateColumnSize();
								}
								
								_this.updatePageIcon(undefined, iLotId);
							}
						)
						.bxTime()
						.waitForImages(
							function()
							{								
								if (typeof fCallback == 'function')
									fCallback();
								else
									if ($(_this.sSelectedJot, _this.sTalkList).length === 1)
										_this.updateScrollPosition('position', 'slow', $(_this.sSelectedJot, _this.sTalkList),
										function(){
											$(_this.sScrollArea).fadeIn('slow');
										});																
									else
										_this.updateScrollPosition('bottom');
							});
						
					if (typeof oData.title !== 'undefined')
						$(document).prop('title', oData.title);

					_this.setUsersStatuses(oLotBlock);

					_this.blockSendMessages();

					/* If profile didn't finish the message add it to post message area --- Begin */
					let sStorageMessage = _this.oStorage.getLot(iLotId);
					if (typeof sStorageMessage === 'string' && sStorageMessage.length){
						let oObject = $('<div />').text(sStorageMessage);
						$(_this.sMessengerBox)
							.html(oObject.text().replace(/\n/ig, '<br>'))
							.change()
							.focus();
					}

					_this.checkNotFinishedTalks();

					if (parseInt($(_this.sTalkListJotSelector).last().data('new')))
                        _this.broadcastView();

					/* ----  End ---- */
				}
		}, 'json');
	};
	
	oMessenger.prototype.loadJotsForLot = function(iLotId, fCallback){
		const _this = this;
		
		bx_loading($(this.sMainTalkBlock), true);
		$.post('modules/?r=messenger/load_jots', {id:iLotId}, function(oData){
			bx_loading($(_this.sMainTalkBlock), false);
			if (parseInt(oData.code) == 1) 
					window.location.reload();
						
			if (!parseInt(oData.code))
			{
					$(_this.sMainTalkBlock)
						.html(oData.html)
						.fadeIn()
						.bxTime();
					
					_this.updateScrollPosition('bottom');
					
					if (typeof fCallback == 'function')
						fCallback();
			}
		}, 'json');	
	}
		
	oMessenger.prototype.sendPushNotification = function(oData){
			$.post('modules/?r=messenger/send_push_notification', oData);
	}

	/**
	 * Main send message function, occurs when member send message
	 * @param string sMessage text of the message
	 * @param object mixedObjects, it may be array of files or object with elements
	 * @param function fCallBack
	 */
	oMessenger.prototype.sendMessage = function(sMessage, mixedObjects, fCallBack){
		const _this = this,
			oParams = this.oSettings,
			msgTime = new Date();
		
		if (typeof mixedObjects === 'object')
		{
			if (Array.isArray(mixedObjects) && mixedObjects.length)
				oParams.files = mixedObjects;
			else
				oParams.ids = mixedObjects;
		}
		else {
			oParams.files = undefined;
			oParams.ids = undefined;
		}

		oParams.participants = _this.getPatricipantsList();
		oParams.message = $.trim(sMessage);
		if (!oParams.message.length && typeof oParams.files === 'undefined' && typeof oParams.ids === 'undefined')
			return;

		oParams.tmp_id = msgTime.getTime();

		// remove MSG (if it exists) from clean history page
		if ($('.bx-msg-box-container', _this.sTalkList).length)
				$('.bx-msg-box-container', _this.sTalkList).remove();	
		
		if (oParams.message.length > this.iMaxLength) 
			oParams.message = oParams.message.substr(0, this.iMaxLength);

		if (oParams.message || typeof oParams.files !== 'undefined' || typeof oParams.ids !== 'undefined')
		{
			// append content of the message to the history page
			$(_this.sTalkList)
				.append(
							_this.oUsersTemplate
								.clone()
									.attr('data-tmp', oParams.tmp_id)
									.find('time')
									.html(_this.sJotSpinner)
								.end()
									.find(_this.sJotMessage)
									.text(oParams.message)
									.fadeIn('slow')
								.end()
						);
								
			
			_this.initJotIcons('[data-tmp="' + oParams.tmp_id + '"]');
			$(_this.sSendArea).html('');
		}

		if ($(_this.sScrollArea).length)
			_this.loadTalk(oParams.lot);
		else
			_this.updateScrollPosition('bottom');

		// save message to database and broadcast to all participants
		$.post('modules/?r=messenger/send', oParams, function(oData){
				switch(parseInt(oData.code))
				{
					case 0:
						const iJotId = parseInt(oData.jot_id);
						const sTime = oData.time || msgTime.toISOString();
						if (iJotId)
						{
							if (typeof oData.lot_id !== 'undefined')
								_this.oSettings.lot = parseInt(oData.lot_id);

							if (typeof oData.tmp_id != 'undefined')
								$('[data-tmp="' + oData.tmp_id + '"]', _this.sTalkList)
									.attr('data-id', oData.jot_id)
									.find('time')
									.html('')
									.attr('datetime', sTime)
									.closest(_this.sJot)
									.bxTime(undefined, true)
									.linkify();
									
							if (typeof oParams.files !== 'undefined' || typeof oParams.ids !== 'undefined')
								_this.attacheFiles(iJotId);
							
							if (!_this.isBlockVersion())
								_this.upLotsPosition(_this.oSettings);
                                _this.broadcastView(iJotId);
						}

						if (!_this.iAttachmentUpdate)
							_this.broadcastMessage();
						
						break;					
					case 1:
						window.location.reload();
						break;
					default:						
						alert(oData.message);
						$(_this.sTalkList).find('[data-tmp="' + oParams.tmp_id + '"]').remove();
				}			
					if (typeof fCallBack == 'function')
						fCallBack();
			}, 'json');
		
		return true;
	};
	
	oMessenger.prototype.broadcastMessage = function(oInfo){
		const oMessage = Object.assign(oInfo || {}, {
											lot: this.oSettings.lot, 
											name: this.oSettings.name,
											user_id: this.oSettings.user_id
										});

		if (this.oRTWSF !== undefined)
				this.oRTWSF.message(oMessage);
	};
	
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
	* Move lot's brief to the top of the left side when new message received or just update brief message
	*@param object oObject lot's settings
	*/
	oMessenger.prototype.upLotsPosition = function(oObject, bSilentMode = false){
		const _this = this,
			lot = parseInt(oObject.lot), 
			oLot = $('div[data-lot=' + lot + ']'),
			oJot = $('div[data-id=' + oObject.jot_id + ']', _this.sTalkList);

		let	oNewLot = undefined;
	
		if (!(typeof oObject.addon === 'undefined' || (oObject.addon.length && oJot.is(':last-child') && oObject.addon !== 'check_viewed')))
			return;
			
		if (lot)
			$.get('modules/?r=messenger/update_lot_brief', {lot_id: lot}, 
				function(oData)
				{
					if (!parseInt(oData.code))
					{					
						const sHtml = oData.html.replace(new RegExp(_this.sJotUrl + '\\d+', 'i'), _t('_bx_messenger_repost_message'));
						oNewLot = $(sHtml).css('display', 'flex');
												
						if (!oLot.is(':first-child'))
						{
							const sFunc = function()
										{
											$(_this.sLotsListBlock)
												.prepend($(oNewLot)
												.bxTime()
												.fadeIn('slow'));
										};
										
							if (oLot.length)
								oLot.fadeOut('slow', function()
													{
														oLot.remove();
														sFunc();
													});
							else
								sFunc();
						}
						else
						{
							oLot
								.replaceWith($(oNewLot)
								.fadeTo(150, 0.5)
								.fadeTo(150, 1)
								.bxTime());
						}

						_this.setUsersStatuses(oLot);
						
						if (typeof oObject.addon === 'undefined') /* only for new messages */
						{
							if (!bSilentMode)
								$(_this).trigger(jQuery.Event('message'));
						
							if (_this.isActiveLot(lot) && !_this.isMobile() && !$(_this.sScrollArea).length)
								_this.selectLotEmit($(oNewLot));
						}
					}
				}, 'json');
	};

	oMessenger.prototype.notifyNewUnreadChats = function(iNewNumberOfUnreadChats) {
		if (typeof window.glBxMessengerOnNotificationChange !== 'undefined' && window.glBxMessengerOnNotificationChange instanceof Array) {
			for (let i = 0; i < window.glBxMessengerOnNotificationChange.length; i++)
				if (typeof window.glBxMessengerOnNotificationChange[i] === "function")
					window.glBxMessengerOnNotificationChange[i](iNewNumberOfUnreadChats);
		}
	};
	
	/**
	* Show member's typing area when member is typing a message
	*@param object oData profile info
	*/
	oMessenger.prototype.showTyping = function(oData) {	
		var _this = this,
			sName = oData.name != undefined ? (oData.name).toLowerCase() : '';
	
		if (oData.lot != undefined && this.isActiveLot(oData.lot))
		{			
			if (!~this.aUsers.indexOf(sName)) 
							this.aUsers.push(sName);
			
			$(this.sTypingArea).text(this.aUsers.join(','));
			$(this.sInfoArea).fadeIn();
		}
		
		clearTimeout(this.iTimer);	
		this.iTimer = setTimeout(function(){
			$(_this.sInfoArea)
			.fadeOut()
			.find(_this.sTypingArea)
			.html('');
			
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
		this.updateJots({
			action: 'msg'
		})
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
	*@param function fCallback callback function 
	*/	
	oMessenger.prototype.findLotByParticipantsList = function(fCallback){
		var _this = this;
		$.post('modules/?r=messenger/find_lot', {participants:this.getPatricipantsList()}, 
			function(oData){
				if (oData.lotId) {
					_this.oJotWindowBuilder.resizeWindow();
					_this.loadJotsForLot(parseInt(oData.lotId), fCallback);
				}
			}, 
		'json');
	}
	
	/**
	* Correct scroll position in history area depends on loaded messages (old history or new just received)
	*@param string sPosition position name
	*@param string sEff name of the effect for load 
	*@param object oObject any history item near which to place the scroll 
	*@param function fCallback executes when scrolling complete
	*/
	oMessenger.prototype.updateScrollPosition = function(sPosition, sEff, oObject, fCallback){
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
		
		$(this.sTalkBlock).animate({
											scrollTop: iPosition,
										 }, sEffect == 'slow' ? _this.iScrollDownSpeed : 0,
										 typeof fCallback === 'function' ? fCallback : function(){});
	}
	
	/**
	* Sound when message received
	*/
	oMessenger.prototype.beep = function(){
		let playSound = null;

		try{
			playSound = new Audio(this.soundFile);
			if (!document.hasFocus()) 
			{
				playSound.play();
				this.updatePageIcon(true);
			}
			
		}catch(e){
			console.log('Sound is not supported in your browser');
		}		
	}
	
	/**
	* Attach dblclick on message to call edit function
	*@return object
	*/	
	$.fn.onEditJot = function(){
		 $(_oMessenger.sJotMessage, this)
			.each(function()
			{				
				if (!$(_oMessenger.sDeletedJot, this).length)
					$(this)
						.on('dblclick', function(e)
						{
							if ($(e.target).hasClass(_oMessenger.sSendArea.substr(1)))
							{
								e.stopPropagation();
								return false;
							}
		
							_oMessenger.editJot(this);
						});
			});
		
		return this;
	}
	
	/**
	* Update history area, occurs when new messages are received(move scroll to the very bottom) or member loads the history(move scroll to the very top)
	*@param object oAction info about an action
	*/
	oMessenger.prototype.updateJots = function(oAction, bSilentMode = false){
		const _this = this,
			sAction = oAction.addon || (oAction.action !== 'msg' ? oAction.action : 'new'),
			sPosition = oAction.position || (sAction === 'new' ? 'bottom' : 'position'),
			oObjects = $(this.sTalkListJotSelector);

			let	iJotId = 0;
			
			if (oAction.action === 'msg' && $(_this.sScrollArea).length)
				return;
						
			if ((sAction === 'new' || sAction === 'prev') && _this.iPanding)
				return;
					
			switch(sAction)
			{
				case 'prev':
						iJotId = oObjects
									.first()
									.data('id');
						
						_this.iPanding = true;
						bx_loading($(this.sTalkBlock), true);
						break;
				case 'check_viewed':
				case 'delete':
				case 'edit':
						iJotId = oAction.jot_id || 0;
						break;
				default:
						iJotId = oObjects
									.last()
									.data('id');
									
						_this.iPanding = true;
						break;
			}
					   	
			$.post('modules/?r=messenger/update',
			{
				url: this.oSettings.url,
				type: this.oSettings.type,
				jot: iJotId,
				lot: this.oSettings.lot,
				load:sAction,
                read:(_this.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive()) || !_this.isMobile()
			},
			function(oData)
			{
				const oList = $(_this.sTalkList);
				if (!parseInt(oData.code))
				{
						if (iJotId === undefined)
							oList.html('');	
										
						switch(sAction)
						{
                            case 'new':
									if (!oData.html.length)
									{
										if ($(_this.sScrollArea).is(':visible'))
											$(_this.sScrollArea).fadeOut('slow', function(){
												$(this).remove();
											});
										
										return ;
									}
									
									$(oData.html)
									.filter(_this.sJot)
									.each(function()
										{
											if ($('div[data-id="' + $(this).data('id') + '"]', oList).length !== 0)
												$(this).remove();

                                            if ($(_this.sJotMessageViews, this).length)
                                                $(_this.sJotMessageViews, this).fadeIn();

										})
									.appendTo(oList)
									.waitForImages(
										function()
										{
											_this.updateScrollPosition(sPosition ? sPosition : 'bottom', 'slow', oObjects.last());
										});

									if ((_this.isBlockVersion() || (_this.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive())) && !bSilentMode)  /* play sound for jots only on mobile devices when chat area is active */
										$(_this).trigger(jQuery.Event('message'));


									if ((_this.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive()) || !_this.isMobile())
                                        _this.broadcastView();

									break;
							case 'prev':
									oList
										.prepend(
													$(oData.html)
														.waitForImages(
														function()
														{
															_this.updateScrollPosition(sPosition, 'fast', $(oObjects.first()));
														})
												);														
								break;
							case 'edit':
									$('div[data-id="' + iJotId + '"] ' + _this.sJotMessage, oList)
										.html(oData.html)
										.parent()
										.linkify(true, true); // don't update attachment for message and don't broadcast as new message 								
								break;
							case 'delete':
									const onRemove = function()
											{
												$(this).remove();
												_this.updateScrollPosition('bottom');
											};
									if (oData.html.length)
									{
											$('div[data-id="' + iJotId + '"] ' + _this.sJotMessage, oList)
											.html(oData.html)
											.parent()
											.linkify(true, true)
											.find(_this.sAttachmentArea)
											.fadeOut('slow', onRemove);
									} 
										/*  if nothing returns, then remove html code completely */
									 else
									{
										$('div[data-id="' + iJotId + '"]', oList)
											.fadeOut('slow', onRemove);
									}

							case 'check_viewed':
							    const aUsers = $(oData.html).filter('img');

								if (aUsers.length) {
    							    aUsers.each(function(){
										let iProfileId = $(this).data('viewer-id');
										$(`${_this.sJot} ${_this.sJotMessageViews} img[data-viewer-id="${iProfileId}"]`).remove();
									});

									return $(`div[data-id="${iJotId}"] ${_this.sJotMessageViews}`, oList)
										.html(oData.html)
										.fadeIn();
								}
						}
						
						oList
							.find(_this.sJot + ':hidden')
							.fadeIn(
								function()
								{
									$(this).css('display', 'flex');
									_this.initJotIcons(this);
								})
							.bxTime();

				}
							
				if (sAction === 'prev')
					bx_loading($(_this.sTalkBlock), false);
				
				_this.iPanding = false;
					
			}, 'json');
	};
		
	/**
	* Init user selector are when create or edit participants list of the lot
	*@param boolean bMode if used for edit or to create new lot
	*/
	oMessenger.prototype.initUsersSelector = function(bMode){
			const _this = this,
				onSelectFunc = function(fCallback)
				{
					if (bMode !== 'edit') {
						_this.findLotByParticipantsList(fCallback);

						setTimeout(() => {
							$(_this.sUserSelectorInput)
								.val('')
								.focus();
						}, 0);
					}
					else
						if (_this.oJotWindowBuilder !== undefined)
							_this.oJotWindowBuilder.updateColumnSize();
				};

				$(_this.sUserSelectorBlock + ' .ui.search')
						.search({
									clearable: true,
									apiSettings:
									{
										url: 'modules/?r=messenger/get_auto_complete&term={query}&except={except}',
										urlData:
												{
													except: function(){
																let aUsers = [];
																$('[name="users[]"]', _this.sUserSelectorBlock)
																	.each(function(){
																		aUsers.push($(this).val());
																	});
																
																return aUsers.join(',');	
															  }
												}
									},
									templates:
									{
										message:function(message, type){
											return type && message ? `<div class="message empty"><div class="description">${message}</div></div>` : '';
										}
									},
									error:
									{
										noResults  : _t('_bx_messenger_search_no_results'),
										serverError : _t('_bx_messenger_search_query_issue'),
									},
									cache : false,
									fields: {
									  results : 'items',
									  title   : 'value',
									  image	  : 'icon'
									},
									onResults: function(){
										$(this)
											.find('.results')
											.css({'background-color': $('.bx-def-color-bg-page').css('background-color')});
									},
									onSelect: function(result, response){
										$(this)
											.find('input')
											.val('')
											.end()
											.before(`<b class="bx-def-color-bg-hl bx-def-round-corners">
														<img class="bx-def-thumb bx-def-thumb-size bx-def-margin-sec-right" src="${result.icon}" /><span>${result.value}</span>
														<input type="hidden" name="users[]" value="${result.id}" /></b>`);

										onSelectFunc();
										return true;
									},
									minCharacters : 1
								})
								.find('input')
								.focus();
			
			$(_this.sUserSelectorBlock).on('click', 'b', function(){
					$(this).remove();
					onSelectFunc();
					$(_this.sUserSelectorInput).focus();
			});
	};
	
	/**
	* Returns object with public methods 
	*/
	return {
		/**
		* Init main Lot's settings and object to work with (settings, real-time frame work, page builder and etc...)
		*@param object oOptions options
		*/
		init:function(oOptions){			
			const _this = this;
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
			const _this = this;

			_oMessenger.initJotSettings(oOptions);

			$(document).on('dragenter dragover drop', function (e){
				e.stopPropagation();
				e.preventDefault();
			});
			
			$(window).on('focus', () =>_oMessenger.updatePageIcon());
			
			$(_oMessenger.sTalkList + ',' + _oMessenger.sMessangerParentBox)
				.on('drop paste', function (e)
				{
					const files = (e.type === 'drop' && e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files) || [];
					if (e.type === 'paste')
					{
						const aItems = (e.clipboardData || e.originalEvent.clipboardData).items;
						
						for (let i in aItems)
						{
							const oItem = aItems[i];
							if (oItem.kind === 'file')
								files.push(oItem.getAsFile());
						}
					}
					
					
					if (files.length)
						_this.showPopForm(undefined, function()
						{
							AqbDropZone.handleFiles(files);
						});
				});
		},
		
		/**
		* Init settings, occurs when member opens the main messenger page
		*@param int iLotId, if defined select this lot
		*@param int iJotId, if defined select this jot
		*@param int iProfileId if profile id of the person whom to talk
		*@param object oBuilder page builder class
		*/
		initMessengerPage:function(iLotId, iJotId, iProfileId, oBuilder){
			_oMessenger.oJotWindowBuilder = oBuilder || window.oJotWindowBuilder;

			if (typeof oMessengerMemberStatus !== 'undefined')
			{
				oMessengerMemberStatus.init(function(iStatus)
				{
					_oMessenger.iStatus = iStatus;
					if (typeof _oMessenger.oRTWSF !== "undefined")
						_oMessenger.oRTWSF.updateStatus({
											user_id:_oMessenger.oSettings.user_id,
											status:iStatus,
										 });
				});
			}
		
			if (_oMessenger.oJotWindowBuilder !== undefined){
				$(window).on('load resize', function(e){
						if (e.type === 'load')
						{
								if (iLotId && iJotId)
									_oMessenger.loadTalk(iLotId, iJotId, false, false);
								else
									if(iProfileId || $(_oMessenger.sLotsListSelector).length === 0)
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

			if (typeof oMessengerStorage !== 'undefined' && !_oMessenger.oStorage) {
				_oMessenger.oStorage = new oMessengerStorage();
				_oMessenger.checkNotFinishedTalks();
			}

			_oMessenger.updatePageIcon();
		},
		
		// init public methods
		loadTalk:function(iLotId, bMakeAllAsRead){
			_oMessenger.loadTalk(iLotId, undefined, false, !!bMakeAllAsRead);
			if (typeof _oMessenger.oHistory.pushState === 'function') {
				let oHistory ={'lot': iLotId};
				_oMessenger.oHistory.pushState(oHistory, null);
			}
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
		onViewDeletedJot: function(iJotId) {
			_oMessenger.viewJot(iJotId);
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
		onDeleteJot:function(oObject, bCompletely){
			_oMessenger.deleteJot(oObject, bCompletely);
		},
		onEditJot:function(oObject){
			_oMessenger.editJot(oObject);
		},		
		onSaveJot:function(oObject){
			_oMessenger.saveJot(oObject);
		},
		onCancelEdit:function(oObject){
			_oMessenger.cancelEdit(oObject);
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
			const bSilent = _oMessenger.oSettings.user_id === oData.user_id;

			try
			{
				if (!_oMessenger.isBlockVersion() && (!_oMessenger.isMobile() || (_oMessenger.isMobile() && oData.lot !== _oMessenger.oSettings.lot)))
					_oMessenger.upLotsPosition(oData, bSilent);
			}
			catch(e) {
				console.log('Lot list message update error', e);
			}

			try
			{
				if (oData.lot === _oMessenger.oSettings.lot)
					_oMessenger.updateJots(oData, bSilent);
			}
			catch(e) {
				console.log('Talk history update error', e);
			}

			return this;
		},
		onStatusUpdate: function(oData){
			_oMessenger.updateStatuses(oData);
			return this;
		},
		onServerResponse: function(oData){
			if (oData.addon === undefined || !oData.addon.length)
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
		* Loads form for files uploading in popup
		*@param string sUrl link, if not specify default one will be used
		*@param function fCallback callback function,  executes on window show
		*/		
		showPopForm: function (sMethod, fCallback) {
			const sUrl = 'modules/?r=messenger/' + (sMethod || 'get_upload_files_form'),
				  sText = $(_oMessenger.sMessengerBox).val();

			$(window).dolPopupAjax({
				url: sUrl,
				id: { force: true, value: _oMessenger.sAddFilesForm.substr(1) },
				onShow: function() {

					if (typeof fCallback == 'function')
						fCallback();

					if (sText.length)
						$(_oMessenger.sAddFilesFormComments).text(sText);

					setTimeout(() => _oMessenger.updateCommentsAreaWidth(), 100);
				},
				closeElement: true,
				closeOnOuterClick: false,
				removeOnClose:true,
				onHide:function()
				{
					_oMessenger.updateScrollPosition('bottom');
				}
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
		* Send selected gif file from giphy with message
		*@param string sId giphy image file
		*/
		sendGiphy:function(sId) {
			bx_loading($(_oMessenger.sFilesUploadAreaOnForm), true);
			const sMessage = $(_oMessenger.sAddFilesFormComments).text();
			_oMessenger.sendMessage(sMessage, { 'giphy': sId }, () => {
				$(_oMessenger.sFilesUploadAreaOnForm)
					.closest('.bx-popup-active')
					.dolPopupHide({});

				bx_loading($(_oMessenger.sFilesUploadAreaOnForm), false);
				$('.bx-messenger-conversation-block-wrapper .ui.sidebar.giphy')
					.sidebar('toggle');
			});
		},

		/**
		 * Select giphy item to send, allows to add message to gif image.
		 *@param string sId of the image
		 */
		onSelectGiphy:function(sId){
			$(window).dolPopupAjax({
				url: 'modules/?r=messenger/get_giphy_upload_form/' + sId,
				id: { force: true, value: _oMessenger.sAddFilesForm.substr(1) },
				closeElement: true,
				removeOnClose: true,
				onShow: function() {
					$('.bx-popup-applied:visible img')
						.load(function(){
							if ($(this).get(0).width)
								$(_oMessenger.sAddFilesFormComments, $('.bx-popup-applied:visible'))
									.css('max-width', $(this).get(0).width);
						 });
				},
			});
		},
		
		/**
		* Show only marked as important lot
		*@param object oEl 
		*/
		showStarred:function(oEl){
			if (!_oMessenger.iStarredTalks)
			{
				$(oEl)
					.addClass('active')
					.find('i.star')
					.addClass('fill')
			}
			else
				$(oEl)
					.removeClass('active')
					.find('i.star')
					.removeClass('fill');

			_oMessenger.iStarredTalks = !_oMessenger.iStarredTalks;
			this.searchByItems($('#items').val());
		},

		removeFile:function(oEl, id){
			$.get('modules/?r=messenger/delete_file', {id: id}, function(oData){
				if (!parseInt(oData.code))
				{
					if (!oData.empty_jot)
						$(oEl)
							.parents('.delete')
							.parent()
							.fadeOut('slow',
							function()
							{
								$(this).remove();
							});
					else
						$(oEl)
							.parents(_oMessenger.sJot)
							.fadeOut('slow', 
							function()
							{
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
		},
		sendVideoRecord:function(oFile, oCallback){
			var fileName = (new Date().getTime()),
				formData = new FormData();

			if (oFile.type !== undefined) {
                let sExt = (~oFile.type.indexOf(';') ? oFile.type.substring(0, oFile.type.lastIndexOf(';')) : oFile.type).replace('video/', '.');
				fileName += sExt;
            };

			formData.append('name', fileName);
			formData.append('file', oFile);

			bx_loading($(_oMessenger.sFilesUploadAreaOnForm), true);
			$.ajax({
				url:'modules/?r=messenger/upload_video_file',
				data:formData,
				type:'POST',
				dataType:'json',
				contentType: false,
				processData: false,
			})
			.done(
					function(oData)
					{
						bx_loading($(_oMessenger.sFilesUploadAreaOnForm), false);
						if (!parseInt(oData.code))
						{
							var sMessage = $(_oMessenger.sAddFilesFormComments).text();
							_oMessenger.sendMessage(sMessage, [fileName], function()
								{
									if (typeof oCallback == 'function')
										oCallback();
								});
						}
						else
							alert(oData.message);
					});
			
			
		},
		updateCommentsAreaWidth: function(fWidth){
			_oMessenger.updateCommentsAreaWidth(fWidth);
		},
		initGiphy: function(e){
			const $oContainer = $(_oMessenger.sGiphyItems),
				  fGiphy = (sType, sValue) => {
					const fWidth = $oContainer.width();

					$oContainer
						.append('<div class="giphy-loading" />');

					bx_loading($oContainer.find('.giphy-loading'), true);

					$.get('modules/?r=messenger/get_giphy', { width: fWidth, action: sType, filter: sValue },
					function(oData)
					{
						const bHasMasonry = $oContainer.data('masonry') ? true : false;
						// destroy masonry if already exists
						if ($oContainer.length && bHasMasonry)
							$oContainer.masonry('destroy');

						$oContainer
								.html(
									oData.code
									? oData.message
									: oData.html
								)
								.imagesLoaded(() => {
									$oContainer.masonry()
								});

						$oContainer.masonry({
								itemSelector: 'img',
								isAnimated: true,
								gutter: 5,
								fitWidth: true,
							});
					},
					'json');
            };

            if ($('.ui.giphy').css('visibility') === 'visible')
            {
                let iTimer = 0;
                $('input', _oMessenger.sGiphyBlock).keypress(function(e) {
                    const sChar = String.fromCharCode(!e.charCode ? e.which : e.charCode);

                    if (/[a-zA-Z0-9-_ ]/.test(sChar)) {
                        clearTimeout(iTimer);
                        iTimer = setTimeout(() => {
                        	fGiphy('search', $(this).val());
                        }, 1500);
                        return true;
                    }

                    e.preventDefault();
                    return false;
                });

                if ($oContainer && !$oContainer.find('img').length)
               		fGiphy();
            }
        }
	}
})(jQuery);

/** @} */
