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
		this.sSendAreaMenuPlus = '#bx-messenger-send-area-plus';
		this.sAddFilesFormComments = '#bx-messenger-files-upload-comment';
		this.sAddFilesForm = '#bx-messenger-files-uploader';
		this.sJitsiVideo = '#bx-messenger-jitsi-video';
		this.sJitsiMain = '#bx-messenger-jitsi';
		this.sEditJotArea = '.bx-messenger-edit-jot';
		this.sEditJotAreaId = '#bx-messenger-edit-message-box';
		this.sAttachmentArea = '.bx-messenger-attachment-area';
		this.sAttachmentBlock = '.bx-messenger-attachment';
		this.sAttachmentFiles = '.bx-messenger-attachment-files';
		this.sSendAreaActions = '.bx-messenger-post-box-send-actions';
		this.sSendAreaActionsButtons = '.bx-messenger-post-box-send-actions-items';
		this.sReactionsArea = '.bx-messenger-jot-reactions';
		this.sHiddenJot = '.bx-messenger-hidden-jot';
		this.sDeletedJot = '.bx-messenger-jots-message-deleted';
		this.sMediaATArea = ['.bx-messenger-attachment-file-videos', ".bx-messenger-attachment-file-audio"];
		this.sFilesUploadAreaOnForm = '.bx-messenger-upload-area';
		this.sScrollArea = '.bx-messenger-area-scroll';
		this.sSelectedJot = '.bx-messenger-blink-jot';
		this.sTmpVideoFile = '.bx-messenger-attachment-temp-video';
		this.sJotMessageViews = '.view';
		this.sReactionItem = '.bx-messenger-reaction';
		this.sReactionMenu = '.bx-messenger-reactions-menu';
		this.sBottomGroupsArea = '.bx-messenger-attachment-group';
		this.sGiphyItems = '.bx-messenger-giphy-items';
		this.sGiphySendArea = '#bx-messenger-send-area-giphy';
		this.sGiphMain = '.giphy';
		this.sGiphyBlock = '.bx-messenger-giphy';
		this.sEmojiId = '#emoji-picker';
		this.sTalkAreaWrapper = '.bx-messenger-table-wrapper';
		this.sSendAttachmentArea = '.bx-messenger-send-area-attachments';
		this.sActivePopup = '.bx-popup-applied:visible';
		this.sJitsiButton = '#jitsi-button';
		this.sJitsiJoinButton = '.bx-messenger-jots-message-vc-join-button';

		//global class options
		this.oUsersTemplate	= null;
		this.sReactionTemplate = oOptions && oOptions.reaction_template;
		this.oActiveEditQuill = null;
		this.sJotUrl = (oOptions && oOptions.jot_url) || sUrlRoot + 'm/messenger/archive/';
		this.sInfoFavIcon = 'modules/boonex/messenger/template/images/icons/favicon-red-32x32.png';
		this.sJotSpinner = '<img src="modules/boonex/messenger/template/images/icons/jot-loading.gif" />';
		this.sDefaultFavIcon = $('link[rel="shortcut icon"]').attr('href');
		this.iAttachmentUpdate = false;
		this.iTimer = null;
		this.sEmbedTemplate = (oOptions && oOptions.embed_template) || '<a href="__url__">__url__</a>';
		this.iMaxLength = (oOptions && oOptions.max) || 0;
		this.iStatus = document.hasFocus() ? 1 : 2; // 1- online, 2-away
		this.iActionsButtonWidth = '2.25';
		this.iScrollDownSpeed = 1500;
		this.aJitisActiveUsers = {};
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
		// quill toolbar settings
		this.aToolbarSettings = [
			['bold', 'italic', 'underline', 'strike', 'link'],
			['blockquote', 'code-block'],
			[{ 'color': [] }, { 'background': [] }]
		];
		this.lastEditText = '';
		this.incomingMessage = 'modules/boonex/messenger/data/notify.mp3'; //beep file, occurs when message received
		this.reaction = 'modules/boonex/messenger/data/reaction.mp3'; //beep file, occurs when message received
		this.call = 'modules/boonex/messenger/data/call.mp3'; //incoming call file for video conferences
		this.emojiObject = oOptions.emoji || null;
		this.aPlatforms = ['MacIntel', 'MacPPC', 'Mac68K', 'Macintosh', 'iPhone', 'iPod', 'iPad', 'iPhone Simulator', 'iPod Simulator', 'iPad Simulator', 'Pike v7.6 release 92', 'Pike v7.8 release 517'];
		this.oStorage = null;
		this.oHistory = window.history || {};
		this.oFilesUploader = null;
		this.oActiveAudioInstance = null;
		this.oActiveEmojiObject = Object.create(null);

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

		// text editor
		this.quill = null;

		$(window).on('popstate', function(){
			const iLot = _this.oHistory.state && _this.oHistory.state.lot;

			if (iLot)
				_this.loadTalk(iLot);
		});
	}
	oMessenger.prototype.playSound = function(sFile, bRepeat = false){
		if (!_oMessenger[sFile])
			return ;

	try {
			if (bRepeat)
				this.oActiveAudioInstance = createjs.Sound.play(sFile, { interrupt: createjs.Sound.INTERRUPT_ANY, loop: -1 });
			else
				createjs.Sound.play(sFile, {});
		}
		catch(e){
			console.log('Sound is not supported in your browser');
		}
	};
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

		if (oMessageBox.length && Quill) {
			const QuillClipboard = Quill.import('modules/clipboard');
			class Clipboard extends QuillClipboard {
				onPaste (event) {
					super.onPaste(event);
					if (event.clipboardData.getData('text/plain').length > 0)
						$(_this.sSendButton).fadeIn();
				}
			}

			Quill.register('modules/clipboard', Clipboard, true);

			this.quill = new Quill(this.sMessengerBox, {
					placeholder: oOptions.placeholder,
					theme: 'bubble',
					bounds: this.sMessengerBox,
					modules: {
						toolbar: _this.isMobile() ? false : _this.aToolbarSettings,
						clipboard: {
							matchers: [
								['IMG', () => {
									return { ops: [] }
								}]
							]
						},
						keyboard: {
							bindings: {
								enter: {
									key: 13,
									shiftKey: false,
									handler: () => {
										$(_this.sSendButton).click();
									}
								},
								up: {
									key: 38,
									shiftKey: false,
									handler: () => {
										if ($(_this.sTalkListJotSelector).length && _this.quill.getLength() <= 1){
											const aJots = $(`${_this.sTalkListJotSelector}[data-my=1]`).get().reverse();

											for(let i=0; i < aJots.length; i++) {
												const oJot = $(`${_this.sJotMenu} i.backspace`, aJots[i]);
												if (oJot.length) {
													_this.editJot(oJot);
													break;
												}
											}
											return false;
										}
										return true;
									}
								}
							}
						}
					}
				});

			_this.quill.on('text-change', function(delta, oldDelta, source) {
					const { ops } = _this.quill.getContents(),
						fMaxHeight = parseInt($(_this.sMessengerBox).css('max-height'));

					if (source === 'user') {
						if (_this.quill.getLength() > 1)
							_this.oStorage.saveLot(_this.oSettings.lot, JSON.stringify(ops));
						else
							_this.oStorage.deleteLot(_this.oSettings.lot);

					};

					_this.oRTWSF.typing({
						lot: _this.oSettings.lot,
						name: _this.oSettings.name,
						user_id: _this.oSettings.user_id
					});

					if (_this.quill.root.clientHeight >= fMaxHeight)
						$(_this.sMessengerBox).css('overflow-y', 'auto');
					else
						$(_this.sMessengerBox).css('overflow-y', 'visible');

					_this.updateSendButton();
				});
			}

			if (!_this.oRTWSF.isInitialized())
			{
				this.blockSendMessages(true);
				return;
			}

			$(this.sSendButton).on('click', function(){
				const { innerHTML } = _this.quill.root;

				if (_this.sendMessage(_this.quill.getLength() === 1 ? '' : innerHTML)){
					_this.quill.setContents([]);
					_this.quill.focus();
					_this.oFilesUploader.clean();
					_this.oStorage.deleteLot(_this.oSettings.lot);
					$(_this.sSendButton).hide();
				}
			});

			// start to load history depends on scrolling position
			$(_this.sTalkBlock).scroll(function(){
				const isScrollAvail = $(this).prop('scrollHeight') > $(this).prop('clientHeight'),
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

			$('span.info-menu > i').popup({
				on: 'click',
				hoverable: true,
				boundary: $('.bx-messenger-block.jots')
			});

			this.updateScrollPosition('bottom');
			
			//remove all edit jot areas on their lost focus
			$(document).on('mouseup', function(oEvent){
				_this.removeEditArea(oEvent);
			})
			.on('click', (oEvent) => _this.onOuterClick(oEvent));

			_this.checkNotFinishedTalks();

		$(_this.sSendAreaActionsButtons)
			.find('a.smiles')
			.on('click', function(){
				const oEmoji = $(_this.sEmojiId),
					bHidden = !$(_this.sEmojiId).height() || !oEmoji.is(":visible"),
					iHeight = oEmoji.css('height', 'min-content').height(),
					iParentHeight = oEmoji.closest(_this.sTalkAreaWrapper).height();

				if (bHidden)
					oEmoji.css({ visibility: 'visible', display: 'block', top: iParentHeight - iHeight - $(_this.sMessangerParentBox).height(), left: '0.5rem', right:""});
				else
					oEmoji.fadeOut();

				_this.oActiveEmojiObject = {'type': 'textarea'};
			});

		// enable video recorder if it is not IOS/Mac devices
		if(!this.aPlatforms.includes(navigator.platform))
			$(_this.sSendAreaActionsButtons)
				.find('li.video').show();

		// show Video Conference button
		if (!_this.isMobile())
			$(_this.sJitsiButton).show();
		else
			if ('undefined' !== typeof(window.ReactNativeWebView)) {
				if ('undefined' === typeof(window.glBxNexusApp) || parseInt(window.glBxNexusApp.ver.replace('.','')) < 140)
					console.log('This app doesn\'t support video conferences');
				else
					$(_this.sJitsiButton).show();
			};

		// init system sounds
		createjs.Sound.registerSound(this.incomingMessage, 'incomingMessage');
		createjs.Sound.registerSound(this.reaction, 'reaction');
		createjs.Sound.registerSound(this.call, 'call');
	};

	oMessenger.prototype.updateSendArea = function(bFilesEmpty){
		if (bFilesEmpty)
			$(this.sBottomGroupsArea).hide();
		else
			$(this.sBottomGroupsArea).show();

		if (this.quill.getLength() <= 1 && bFilesEmpty)
			$(this.sSendButton).fadeOut();
		else
			$(this.sSendButton).fadeIn();
	};

	oMessenger.prototype.updateSendButton = function(){
		const { length } = this.getSendAreaAttachmentsIds(false);
		const iFiles = this.oFilesUploader && this.oFilesUploader.getFiles().length;

		if (this.quill.getLength() <= 1 && !length && !iFiles)
			$(this.sSendButton).fadeOut();
		else
			$(this.sSendButton).fadeIn();
	};

	oMessenger.prototype.onOuterClick = function(oEvent){
		if (!($(oEvent.target).is('[class*=smile]') || $(oEvent.target).closest(this.sEmojiId).length || $(oEvent.target).siblings('[class*=smile]').length))
			$(`${this.sEmojiId}`).hide();

		if ($(oEvent.target).closest(this.sMessengerBox).length)
			return;

		if (!$(oEvent.target).closest(this.sGiphySendArea).length && !$(oEvent.target).closest(this.sGiphMain).length)
			$(this.sGiphMain).fadeOut();
	};

	oMessenger.prototype.checkNotFinishedTalks = function(){
		const 	_this = this,
				oLots = this.oStorage.getLots(),
			  	oLotsKeys = (oLots && Object.keys(oLots)) || [];

		$(`${this.sLotSelector} .info`).each(function(){
			$(this).html('');
		});

		if (oLotsKeys.length)
			oLotsKeys.map((iLot) => $(`${this.sLotSelector}[data-lot="${iLot}"] .info`).html( oLots[iLot].length ? '<i class="sys-icon pen"></i>' : ''));

		/* If member didn't finish the message add it to post message area --- Begin */
		let sStorageMessage = this.oStorage.getLot(this.oSettings.lot);

		if (typeof sStorageMessage === 'string' && sStorageMessage.length){
			let mixedValue = JSON.parse(sStorageMessage);
			if (Array.isArray(mixedValue)) {
				_this.quill.setContents(mixedValue);
				$(_this.sSendButton).fadeIn();
			}
			else
				_this.quill.setText(mixedValue);
		}

	};
	
	/**
	* Init send message area buttons
	*/
		
	oMessenger.prototype.updateSendAreaButtons = function(){
		const _this = this,
			oSmile = $(_this.sSendAreaActionsButtons)
						.find('a.smiles')
						.parent();

		if (_this.isMobile())
			oSmile.hide();
		else
			oSmile.show(100);
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
		const _this = this,
			  bEditArea = $(oEvent.target).parents(_this.sEditJotArea).length;

		$(this.sEditJotArea).each(function()
		{
			if (!bEditArea)
				_this.cancelEdit($(this));
		});
	};
	
	oMessenger.prototype.initJotIcons = function(oParent){
		const _this = this;

		$(_this.sJotIcons, oParent)
			.each(function(){
				$(this).popup({
					popup: $(_this.sJotMenu),
					on: !_this.isMobile() ? 'hover' : 'click',
					boundary: _this.sTalkBlock,
					hoverable: true
				});

				if (_this.isMobile())
					$(this)
						.siblings(_this.sJotMenu)
						.find('li')
						.on('click', () => $(this).popup('hide'));

			});
	};
		
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
		$.post('modules/?r=messenger/create_lot', { profile:oParams.user || 0, lot:oParams.lot || 0 }, function(oData){
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
			participants:_this.getParticipantsList()
		},
		function(oData)
		{
						const iResult = parseInt(oData.code);
					
						if (iResult === 1)
							bx_alert(oData.message);
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
			bx_alert(oData.message);
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
		$.post('modules/?r=messenger/view_jot', { jot: iJotId }, function(oData)
		{
			if (!parseInt(oData.code) && oData.html.length)
				$(oObject)
					.append(
							$(oData.html)
								.fadeIn('slow')
							);
			
			bx_loading($(_this.sDeletedJot, oObject), false);
		}, 'json');
	};

	oMessenger.prototype.calculatePositionTop = function(oJot, oObject, bLocal) {
		const iHeight = oObject.height(),
			bIsFileMenu = $(oObject).hasClass('file-menu'),
			iParentHeight = oJot.closest(this.sTalkAreaWrapper).height(),
			iScrollTop = oJot.closest(this.sTalkBlock).scrollTop(),
			iTop = oJot.position().top - iScrollTop;

			if (bIsFileMenu)
				return -iHeight/2;

		let iTopPos = 0;
		if ((iParentHeight > iHeight) && ((iParentHeight - iTop) < iHeight/2))
			iTopPos = iTop - iHeight;
		else if (iTop > iHeight/2)
			iTopPos = iTop - iHeight/2;

		return iTopPos - ( bLocal ? iTop : 0 ); // use this local in case if it is some internal popup not the global like reaction popup
	};

	oMessenger.prototype.onAddReaction = function(oObject, bNear){
		const _this = this,
			oJot = $(oObject).closest(this.sJot),
			iJotId = oJot.data('id') || 0,
			oEmoji = $(_this.sEmojiId);

			oEmoji.css({
				top: _this.calculatePositionTop(oJot, $(_this.sEmojiId).css({height: 'min-content'})),
				display: 'block',
				visibility: 'visible',
				left: bNear && !_this.isMobile() ? $(oObject).position().left : '',
			}).show(100);

		_this.oActiveEmojiObject = {'type': 'reaction', 'param': iJotId};
	};

	oMessenger.prototype.deleteLot = function(iLotId){
		const _this = this;
		if (iLotId)
				$.post('modules/?r=messenger/delete', {lot:iLotId}, function(oData){
					if (parseInt(oData.conRemoveReactionode) === 1)
							window.location.reload();
		
						if (!parseInt(oData.code))
						{
							_this.searchByItems(
								function()
								{
									if ($(_this.sLotsListSelector).length > 0)
									{
										$(_this.sLotsListSelector).first().click();
										if (_this.oJotWindowBuilder !== undefined)
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
				.html(this.lastEditText)
				.parent()
				.linkify(true, true); // don't update attachment for message and don't broadcast as new message*/

			if (this.oActiveEditQuill) {
				this.oActiveEditQuill.disable();
				this.oActiveEditQuill = null;
			}
		}

		this.quill.focus();
	};
		
	oMessenger.prototype.saveJot = function(oObject){
		const _this = this,
			oJot = $(oObject).parents(this.sJot),
			iJotId = oJot.data('id') || 0,
			sMessage = _this.oActiveEditQuill && _this.oActiveEditQuill.root.innerHTML;

		if (!_this.oActiveEditQuill.getText().trim().length)
			return false;

		if (sMessage.localeCompare(_this.lastEditText) === 0)
		{
			_this.cancelEdit(oObject);
			return false;
		}
		
		if (iJotId)
		{
			$.post('modules/?r=messenger/edit_jot', { jot: iJotId, message: sMessage  }, function(oData)
			{
				if (!parseInt(oData.code))
				{
					$(_this.sJotMessage, oJot)
						.html(sMessage)
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
	};
	
	
	oMessenger.prototype.editJot = function(oObject){
		const oJot = $(oObject).closest(this.sJot),
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
					const sTmpText = $(_this.sJotMessage, oJot).html();
					_this.lastEditText = sTmpText.length ? sTmpText : $(_this.sEditJotAreaId, oData.html).html();
					const updateScrollFunction = () => {
						const fMaxHeight = parseInt($(_this.sEditJotAreaId).css('max-height'));
						if (_this.oActiveEditQuill.root.clientHeight >= fMaxHeight)
							$(_this.sEditJotAreaId).css('overflow-y', 'auto');
						else
							$(_this.sEditJotAreaId).css('overflow-y', 'visible');
					};

					$(_this.sJotMessage, oJot)
						.html(oData.html)
						.find(_this.sEditJotArea)
						.fadeIn('slow', function()
						{
							const __this = this;
							const Keyboard = Quill.import('modules/keyboard');
							_this.oActiveEditQuill = new Quill(_this.sEditJotAreaId, {
								theme: 'bubble',
								bounds: _this.sEditJotAreaId,
								modules:{
								toolbar: _this.aToolbarSettings,
									keyboard: {
										bindings : {
											enter: {
												key: 13,
												shiftKey: false,
												handler: () => {
													_this.saveJot($(_this.sEditJotAreaId));
												}
											}
										}
									}
								}
							});

							_this.oActiveEditQuill.on('text-change', function(delta, oldDelta, source) {
								updateScrollFunction();
							});

							updateScrollFunction();
							_this.oActiveEditQuill.keyboard.addBinding({
								key: Keyboard.keys.ESCAPE
							}, () => _this.cancelEdit(__this));

							_this.oActiveEditQuill.focus();

							if (oJot.is(':last-child'))
								_this.updateScrollPosition('bottom');
						});
				}
			}, 'json');
		}
	};
	
	oMessenger.prototype.copyJotLink = function(oObject){
		const _this = this,
			  iJotId = $(oObject)
				.parents(_this.sJot)
				.data('id') || 0,
				oTextArea = document.createElement('textArea');

		oTextArea.readOnly = false;
		oTextArea.contentEditable = true;
		oTextArea.value = _this.sJotUrl + iJotId;
		document.body.appendChild(oTextArea);

		try {
			if (document.body.createTextRange) {// IE
				const oTextRange = document.body.createTextRange();
				oTextRange.moveToElementText(oTextArea);
				oTextRange.select();
				oTextRange.execCommand("Copy");
			} else if (window.getSelection && document.createRange) {// non-IE
				const oRange = document.createRange();
				oRange.selectNodeContents(oTextArea);
				const oSel = window.getSelection();
				oSel.removeAllRanges();
				oSel.addRange(oRange); // Does not work for Firefox if a textarea or input
				oTextArea.select(); // Firefox will only select a form element with select()

				if (oTextArea.setSelectionRange && navigator.userAgent.match(/ipad|ipod|iphone/i))
					oTextArea.setSelectionRange(0, 999999); // iOS only selects "form" elements with SelectionRange

				if (document.queryCommandSupported("copy"))
					document.execCommand('copy');
			}
		}catch (e) {
			console.log(e.toString());
		}

		document.body.removeChild(oTextArea);
	};

	$.fn.setRandomBGColor = function() {

		$('img', this).each(function(){
			let hex = Math.floor(Math.random() * 0xFFFFFF);
			$(this).css('background-color', "#" + ("000000" + hex.toString(16)).substr(-6));
		});

		return this;
	};

	/**
	* Convert plan text links/emails to urls, mailto
	*@param string sText text of the message
	*@param bool bDontBroadcast don't broadcast event as new message
	*@param bool bDontAddAttachment don't add repost link to the jot 
	*/
	$.fn.linkify = function(bDontAddAttachment, bDontBroadcast){

		let sUrlPattern = /((https?):\/\/[^"<\s]+)(?![^<>]*>|[^"]*?<\/a)/gim,
		// www, http, https
			sPUrlPattern = /(^|[^\/"'])(www\.[\S]+(\b|$))/gim,
		// Email addresses
			sEmailPattern = /([\w.]+@[a-zA-Z_-]+?(?:\.[a-zA-Z]{2,6}))(?![^<>]*>|[^"]*?<\/a)/gim,
			sJotLinkPattern = new RegExp(_oMessenger.sJotUrl.replace('?', '\\?') + '\\d+', 'i');

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
				.replace(sEmailPattern, str => `<a href="mailto:${str}">${str}</a>`);

		if (!sUrl){
			const aMatch = sText.match(/<a.*href="(https?:\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|])".*a>/);
			if (Array.isArray(aMatch) && aMatch.length >= 2)
				sUrl = aMatch[1];
		}

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
		const _this = this;
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
						.siblings(_oMessenger.sAttachmentBlock)
						.remove() /* remove attachment if exists */
						.end()
						.after(oData.html);
				
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
				$(_this.sReactionsArea, '[data-id="' + iJotId + '"]')
					.before(
							$(oData['html'])
								.waitForImages(
									function()
									{
										_this.updateScrollPosition('bottom');
									}
								));

				_this.initJotIcons('[data-id="' + iJotId + '"]');
				_this.broadcastMessage();
				_this.updateScrollPosition('bottom');
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
			.find('> div')
			.removeClass('bx-def-font-semibold')
			.find('div')
			.removeClass('bx-def-font-extrabold')
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

		if (iUnreadLotsCount === 1 && iLot === iCurrentLot && (!this.isMobile() || (this.isMobile() && this.oJotWindowBuilder.isHistoryColActive())))
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
			oParams = Object.assign({}, this.oSettings, _this.getSendAreaAttachmentsIds()),
			msgTime = new Date();

		if (_this.oFilesUploader)
		{
			if (!_this.oFilesUploader.isReady()) {
				bx_alert(_t('_bx_messenger_wait_for_uploading'));
				return false;
			}
			else
				oParams.files = _this.oFilesUploader.getFiles();
		}

		if (typeof mixedObjects !== 'undefined' && Array.isArray(mixedObjects.files))
				oParams.files = mixedObjects.files;

		oParams.participants = _this.getParticipantsList();
		oParams.message = $.trim(sMessage);
		if (!oParams.message.length && !oParams.files.length && typeof oParams.giphy === 'undefined')
			return;

		oParams.tmp_id = msgTime.getTime();

		// remove MSG (if it exists) from clean history page
		if ($('.bx-msg-box-container', _this.sTalkList).length)
				$('.bx-msg-box-container', _this.sTalkList).remove();	
		
		if (oParams.message.length > this.iMaxLength) 
			oParams.message = oParams.message.substr(0, this.iMaxLength);

		if (oParams.message || oParams.files.length || (typeof oParams.giphy !== 'undefined' && oParams.giphy.length))
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
									.html(oParams.message)
									.fadeIn('slow')
								.end()
						);
								
			
			_this.initJotIcons('[data-tmp="' + oParams.tmp_id + '"]');
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
									
							if (oParams.files.length || typeof oParams.giphy !== 'undefined')
								_this.attacheFiles(iJotId);
							
							if (!_this.isBlockVersion())
								_this.upLotsPosition(_this.oSettings);
						}

						if (!_this.iAttachmentUpdate)
							_this.broadcastMessage();
						
						break;					
					case 1:
						window.location.reload();
						break;
					default:						
						bx_alert(oData.message);
						$(_this.sTalkList).find('[data-tmp="' + oParams.tmp_id + '"]').remove();
				}			
					if (typeof fCallBack == 'function')
						fCallBack();
			}, 'json');
		
		return true;
	};

	oMessenger.prototype.broadcastMessage = function(oInfo){
		const oMessage = Object.assign({
											lot: this.oSettings.lot, 
											name: this.oSettings.name,
											user_id: this.oSettings.user_id
										}, oInfo || {});

		if (this.oRTWSF !== undefined)
				this.oRTWSF.message(oMessage);
	};
	
	/**
	* Get all participants from users selector area
	*/
	oMessenger.prototype.getParticipantsList = function(){
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
	};
	oMessenger.prototype.stopActiveSound = function() {
		if (this.oActiveAudioInstance) {
			this.oActiveAudioInstance.stop();
			this.oActiveAudioInstance.destroy();
			this.oActiveAudioInstance = null;
		}
	};

	oMessenger.prototype.getVideoCall = function({ lot, vc, user_id }){
		const _this = this;

		if (!lot)
			return false;

		switch(vc){
				case 'start':
					if (!$(_this.sJitsiMain).length && (!_oMessenger.isBlockVersion() || (_oMessenger.isBlockVersion() && _oMessenger.oSettings.lot === lot)))
						$.get('modules/?r=messenger/get_call_popup', { lot },
							function (oData) {
								if (!oData.code) {
									processJsonData(oData);
									_this.playSound('call', true);
									_this.broadcastMessage({
										lot: lot,
										addon: 'vc',
										type: 'vc',
										vc: 'got'
									});

									if (typeof(_this.aJitisActiveUsers[lot]) === 'undefined')
										_this.aJitisActiveUsers[lot] =  { owner: user_id, got: 0 };
									else
										_this.aJitisActiveUsers[lot].owner = user_id;
								}
							}, 'json');
				break;
				case 'stop':
					if ($(`div[data-conferance='${lot}']`).length && _this.aJitisActiveUsers[lot].owner === user_id) {
						_this.onCloseCallPopup($(`div[data-conferance='${lot}']`), lot);
					}
					break;
				case 'got':
					if (typeof(_this.aJitisActiveUsers[lot]) !== 'undefined')
				        _this.aJitisActiveUsers[lot].got++;
					else
						_this.aJitisActiveUsers[lot] =  { owner: 0, got: 1 };

					break;
				case 'break':
                    if (typeof(_this.aJitisActiveUsers[lot]) !== 'undefined') {
                    	if (+_this.aJitisActiveUsers[lot].got)
                    		 _this.aJitisActiveUsers[lot].got--;

                        if (!_this.aJitisActiveUsers[lot].got && _this.aJitisActiveUsers[lot].owner === _this.oSettings.user_id) {
                            _this.closeJitsi(_this.sJitsiMain, lot);
                            _this.beep();
                        }
                    }
				break;
		}
	};
	
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
			$.get('modules/?r=messenger/update_lot_brief', { lot_id: lot },
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
		});
	};
	
	oMessenger.prototype.onReconnectFailed = function(oData) {	
		$(this.sConnectingArea).hide();
		$(this.sConnectionFailedArea).fadeIn();
		
		clearInterval(this.iTimer);
	};	
	
	/**
	* Check if specified lot is currently active
	*@param int iId profile id 
	*@return boolean
	*/
	oMessenger.prototype.isActiveLot = function(iId){
		return +this.oSettings.lot === +iId;
	}

	/**
	* Search for lot by participants list
	*@param int iId profile id 
	*@param function fCallback callback function 
	*/	
	oMessenger.prototype.findLotByParticipantsList = function(fCallback){
		var _this = this;
		$.post('modules/?r=messenger/find_lot', {participants:this.getParticipantsList()},
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
		if (!document.hasFocus())
		{
			this.playSound('incomingMessage');
			this.updatePageIcon(true);
		}
	};

	oMessenger.prototype.getSendAreaAttachmentsIds = function(bClean = true){
		const oObject = { length: 0 };
		$(_oMessenger.sSendAttachmentArea)
			.children()
			.each(function(){
				const oPicture = $('picture', $(this)),
				 	  iId = $(oPicture).data('id'),
					  sType = $(oPicture).data('type');

				if (iId && sType) {
					if (typeof oObject[sType] === 'undefined' || !Array.isArray(oObject[sType]))
						oObject[sType] = [];

					oObject[sType].push(iId);
					oObject.length++;
				}
			});

		if (bClean === true)
			$(_oMessenger.sSendAttachmentArea).html('');

		return oObject;
	};

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
				case 'reaction':
				case 'delete':
				case 'edit':
				case 'vc':
						iJotId = oAction.jot_id || 0;
						break;
				default:
						iJotId = oObjects
									.last()
									.data('id');
									
						_this.iPanding = true;
						break;
			}

			const iLotId = _this.oSettings.lot; // additional check for case when ajax request is not finished yet but another talk is selected
			$.post('modules/?r=messenger/update',
			{
				url: this.oSettings.url,
				type: this.oSettings.type,
				jot: iJotId,
				lot: this.oSettings.lot,
				load: sAction,
                read:(_this.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive()) || !_this.isMobile()
			},
			function(oData)
			{
				const oList = $(_this.sTalkList);

				if (iLotId !== _this.oSettings.lot)
						return ;

				_this.iPanding = false;
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
											if ($('div[data-id="' + $(this).data('id') + '"]', oList).length)
												$(this).remove();

                                            if ($(_this.sJotMessageViews, this).length) {
                                            	$(_this.sJotMessageViews, this)
													.find('img')
													.each(function(){
														$(`${_this.sJot} ${_this.sJotMessageViews} img[data-viewer-id="${$(this).data('viewer-id')}"]`).remove();
													})
													.end()
													.fadeIn();
											}
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
							case 'vc':
									$('div[data-id="' + iJotId + '"] ' + _this.sJotMessage, oList)
										.html(oData.html)
										.parent()
										.bxTime();// don't update attachment for message and don't broadcast as new message
								return;
							case 'delete':
									const onRemove = function(){
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
									break;
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
								break;
							case 'reaction':
									let iOriginalCount = 0;
								
									$(oData.html)
										.each(function(){
										iOriginalCount += $(this).data('count');
									});
										
									$(`div[data-id="${iJotId}"] ${_this.sReactionsArea} > span`, oList)
										.each(function(){
											iOriginalCount -= $(this).data('count');
										});

									if (iOriginalCount > 0)
										_this.playSound('reaction');

									const oReaction = $(`div[data-id="${iJotId}"] ${_this.sReactionsArea}`, oList);

									$('> span', oReaction).remove();

									oReaction
										.prepend(oData.html);

									if (oData.html.length)
										$(_this.sReactionMenu, oReaction).fadeIn();
									else
										$(_this.sReactionMenu, oReaction).fadeOut();
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
					if (bMode !== 'edit')
						_this.findLotByParticipantsList(fCallback);
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
									maxResults: 20,
									onResults: function(){
										$(this)
											.find('.results')
											.css({'background-color': $('.bx-def-color-bg-page').css('background-color')});
									},
									onSelect: function(result, response){
										$(this)
											.before(`<b class="bx-def-color-bg-hl bx-def-round-corners">
														<img class="bx-def-thumb bx-def-thumb-size bx-def-margin-sec-right" src="${result.icon}" /><span>${result.value}</span>
														<input type="hidden" name="users[]" value="${result.id}" /></b>`)
											.find('input')
											.val('')
											.end()
											.find('.results.transition')
											.hide()
											.removeClass('visible')
											.addClass('hidden');

										onSelectFunc();
										return false;
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

	oMessenger.prototype.onJotAddReaction = function(oEmoji, iJotId) {
		const { id } = oEmoji,
			_this = this;

		if (id && iJotId) {
			const oReactionsArea = $(`div[data-id="${iJotId}"] ${this.sReactionsArea}`, this.sTalkList),
				oReaction = $(`span[data-emoji="${id}"]`, oReactionsArea);

			if (!oReaction.length) {
				oReactionsArea.prepend(
					this.sReactionTemplate
						.replace(/__emoji_id__/g, id)
						.replace(/__parts__/g, this.oSettings.user_id)
						.replace(/__count__/g, 1)
				)
					.fadeIn(() => _this.playSound('reaction'));
			} else {
				let iCount = +$(oReaction).data('count'),
					aParts = $(oReaction).data('part').toString().split(',');

				if (!~aParts.indexOf(this.oSettings.user_id + '')) {
					iCount++;
					aParts.push(this.oSettings.user_id);
					$(oReaction).data('part', aParts.join(','));
					$(oReaction).data('count', iCount);

					if ($('> span', oReaction).length)
						$('> span', oReaction).text(iCount);

					_this.playSound('reaction');
				}
			}

			$.post('modules/?r=messenger/jot_reaction', { jot: iJotId, emoji: oEmoji, action: 'add'}, function (oData) {

				if ($(_this.sReactionItem, oReactionsArea).length === 1)
					$(_this.sReactionMenu, oReactionsArea).fadeIn();

				if (+!oData.code)
					_this.broadcastMessage({
						jot_id: iJotId,
						addon: 'reaction'
					});

			}, 'json');
		}
	};

	oMessenger.prototype.onTextAreaAddEmoji = function (oEmoji) {
		let range = this.quill.getSelection(true);
		this.quill.insertText(range.index, oEmoji.native, Quill.sources.USER);
		this.quill.setSelection(range.index + oEmoji.native.length, 1, Quill.sources.API);
	};


    /**
     * Run Jitsi video chat
     *@param string sId of the image
     */
    oMessenger.prototype.startVideoCall = function(oEl, iLotId, oOptions = {}){
        const _this = this;
        if (oEl)
      		bx_loading_btn($(oEl), true);

		if ('undefined' !== typeof(window.ReactNativeWebView)) {
			if (typeof window.glBxVideoCallJoined === 'undefined') {
				window.glBxVideoCallJoined = [];
				window.glBxVideoCallJoined.push(function (e) {
					$.get('modules/?r=messenger/create_jitsi_video_conference/', {lot_id: iLotId}, function (oData) {
							const { message, opened, code, jot_id, room } = oData;

							bx_loading_btn($(oEl), false);

							if (+code === 1) {
								bx_alert(message);
								return;
							}
							if (typeof opened !== 'undefined' && Array.isArray(opened))
								if (Array.isArray(opened))
									opened.map(iLotId => _this.updateJots({
										action: 'vc',
										jot_id: iLotId
									}));

							if (iLotId) {
								const oInfo = { type: 'vc', vc: 'start' };

								if (jot_id && !oData.new) {
									oInfo.jot_id = jot_id;
									oInfo.vc = 'join';
								}

								_this.broadcastMessage(oInfo);
								_this.updateJots(oInfo);``
							}

							if (typeof window.glBxVideoCallTerminated === 'undefined') {
								window.glBxVideoCallTerminated = [];
								window.glBxVideoCallTerminated.push(function (e) {
									$.get('modules/?r=messenger/stop_jvc/', { lot_id: iLotId }, (oData) => {
										const oInfo = {
											jot_id: jot_id,
											addon: 'vc',
											type: 'vc',
											vc: 'stop'
										};

										if (+oData.code && oData.msg)
											bx_alert(oData.msg);

										_this.broadcastMessage(oInfo);
										_this.updateJots(oInfo);

									}, 'json');
								});
							}

							const oVideoParams = { uri: room };
							if (typeof oOptions.startAudioOnly !== 'undefined' && oOptions.startAudioOnly === true)
								oVideoParams['audio'] = true;

							// call mobile video call
							if (typeof bx_mobile_apps_post_message === 'function')
								bx_mobile_apps_post_message({ video_call_start: oVideoParams });

						},
						'json');
				});
			};

			return false;
		};

        let oParams = Object.assign({}, oOptions);
        if (typeof oParams.callback !== 'undefined')
           	delete oParams['callback'];

	    $(window).dolPopupAjax({
          url: bx_append_url_params(`modules/?r=messenger/get_jitsi_conference_form/${iLotId}`, oParams),
          id: {
                force: true,
                value: _this.sJitsiVideo.substr(1)
               },
               fog: false,
               removeOnClose: true,
               closeOnOuterClick: false,
			   onBeforeShow: () => oOptions && typeof oOptions.callback == 'function' && oOptions.callback(),
               onShow: () => oEl && bx_loading_btn($(oEl), false),
            });
    };

	oMessenger.prototype.onCloseCallPopup = function (oEl, iLotId, sType = 'break') {
		const oParams = {
			type: 'vc',
			vc: sType,
			lot: iLotId
		},
		iJot = $(this.sJitsiJoinButton)
			.last()
			.closest(this.sJot)
			.data('id');

		this.stopActiveSound();
		this.broadcastMessage(oParams);

		if (iJot)
			this.updateJots(Object.assign(oParams, {
				action: 'vc',
				jot_id: iJot
			}));

		if (typeof(this.aJitisActiveUsers[iLotId]) !== 'undefined')
			delete this.aJitisActiveUsers[iLotId];

		$(oEl)
			.closest('.bx-popup-active:visible')
			.dolPopupHide({
				removeOnClose: true
			});
	};

	oMessenger.prototype.closeJitsi = function(oElement, iLotID){
		const oJitsi = this.oJitsi,
			  _this = this,
			  fClose = 	function(){
			  const jotId = $(_this.sJitsiJoinButton)
					  		.last()
					  		.closest(_this.sJot)
				  			.data('id');

					  sFunc = () => {
						  const oInfo = {
							  jot_id: jotId,
							  addon: 'vc',
							  type: 'vc',
							  vc: 'stop'
						  };
						  _this.broadcastMessage(oInfo);
						  _this.updateJots(oInfo);

						  if (typeof(_this.aJitisActiveUsers[iLotID]) !== 'undefined')
							  delete _this.aJitisActiveUsers[iLotID];
					  };

					  $.get('modules/?r=messenger/stop_jvc/', { lot_id: iLotID || _this.oSettings.lot }, (oData) => {
						  if (+oData.code && oData.msg)
							  bx_alert(oData.msg);
						  sFunc();
					  }, 'json');

				  if (oJitsi){
					    oJitsi.dispose();
					    _this.oJitsi = null;
				   }
		 },
		oPopupWindow = oElement && $(oElement).closest(this.sActivePopup);

		if (oPopupWindow && oPopupWindow.length)
			oPopupWindow.dolPopupHide({
				removeOnClose: true,
				onBeforeHide: fClose,
			});
		else
			fClose();

		$(window).unbind('beforeunload');
	};

	/**
	* Returns object with public methods 
	*/
	return {
		/**
		 * Init main Lot's settings and object to work with (settings, real-time frame work, page builder and etc...)
		 *@param object oOptions options
		 */
		init: function (oOptions) {
			const _this = this;
			if (_oMessenger != null) return true;

			_oMessenger = new oMessenger(oOptions);

			/* Init users Jot template  begin */
			_oMessenger.loadMembersTemplate();
			/* Init users Jot template  end */

			/* Init sockets settings begin*/
			if (_oMessenger.oRTWSF != undefined && _oMessenger.oRTWSF.isInitialized()) {

				$(window).on('beforeunload', function () {
					if (_oMessenger.oRTWSF != undefined)
						_oMessenger.oRTWSF.end({
							user_id: oOptions.user_id
						});
				});

				_oMessenger.oRTWSF.onTyping = function (oData) {
					_this.onTyping(oData);
				};

				_oMessenger.oRTWSF.onMessage = function (oData) {
					_this.onMessage(oData);
				};

				_oMessenger.oRTWSF.onStatusUpdate = function (oData) {
					_this.onStatusUpdate(oData);
				};

				_oMessenger.oRTWSF.onServerResponse = function (oData) {
					_this.onServerResponse(oData);
				};

				_oMessenger.oRTWSF.onReconnecting = function (oData) {
					_oMessenger.onReconnecting(oData);
				};

				_oMessenger.oRTWSF.onReconnected = function (oData) {
					_oMessenger.onReconnected(oData);
				};

				_oMessenger.oRTWSF.onReconnectFailed = function (oData) {
					_oMessenger.onReconnectFailed(oData);
				};

				_oMessenger.oRTWSF.getSettings = function () {
					return $.extend({status: _oMessenger.iStatus}, _oMessenger.oSettings);
				};

			} else {
				console.log('Real-time frameworks was not initialized');
				return false;
			}

			if (typeof oMessengerStorage !== 'undefined' && !_oMessenger.oStorage) {
				_oMessenger.oStorage = new oMessengerStorage();
			}
			/* Init connector settings end */
			return true;
		},

		/**
		 * Init Lot settings only (occurs when member selects any lot from lots list)
		 *@param object oOptions options
		 */
		initJotSettings: function (oOptions) {
			const _this = this;

			_oMessenger.initJotSettings(oOptions);
			$(window).on('focus', () => _oMessenger.updatePageIcon());
		},

		/**
		 * Init settings, occurs when member opens the main messenger page
		 * @param int iLotId, if defined select this lot
		 * @param int iJotId, if defined select this jot
		 * @param int iProfileId if profile id of the person whom to talk
		 * @param sDirection set which browser version to use RTL or LTR
		 * @param object oBuilder page builder class
		 */
		initMessengerPage: function (iLotId, iJotId, iProfileId, sDirection, oBuilder) {
			_oMessenger.oJotWindowBuilder = oBuilder || window.oJotWindowBuilder;

			if (typeof oMessengerMemberStatus !== 'undefined') {
				oMessengerMemberStatus.init(function (iStatus) {
					_oMessenger.iStatus = iStatus;
					if (typeof _oMessenger.oRTWSF !== "undefined")
						_oMessenger.oRTWSF.updateStatus({
							user_id: _oMessenger.oSettings.user_id,
							status: iStatus,
						});
				});
			}

			if (_oMessenger.oJotWindowBuilder !== undefined) {
				_oMessenger.oJotWindowBuilder.setDirection(sDirection);
				$(window).on('load resize', function (e) {
					if (e.type === 'load') {
						if (iLotId && iJotId)
							_oMessenger.loadTalk(iLotId, iJotId, false, false);
						else if (iProfileId || $(_oMessenger.sLotsListSelector).length === 0)
							_oMessenger.createLot({user: iProfileId});
						else if (!_oMessenger.isMobile() && $(_oMessenger.sLotsListSelector).length > 0)
							$(_oMessenger.sLotsListSelector).first().click();
					} else
						_oMessenger.updateSendAreaButtons();

					_oMessenger.oJotWindowBuilder.resizeWindow();
				});

				_oMessenger.oJotWindowBuilder.loadRightColumn = function () {
					if ($(_oMessenger.sLotsListSelector).length > 0)
						$(_oMessenger.sLotsListSelector).first().click();
					else
						_oMessenger.createLot();
				};
			} else {
				console.log('Page Builder was not initialized');
			}

			_oMessenger.updatePageIcon();
		},

		// init public methods
		loadTalk: function (iLotId, bMakeAllAsRead) {
			_oMessenger.loadTalk(iLotId, undefined, false, !!bMakeAllAsRead);
			if (typeof _oMessenger.oHistory.pushState === 'function') {
				let oHistory = {'lot': iLotId};
				_oMessenger.oHistory.pushState(oHistory, null);
			}
			return this;
		},
		searchByItems: function (sText) {
			_oMessenger.searchByItems(_oMessenger.iFilterType, sText);
			return this;
		},
		createLot: function createLot(oObject) {
			_oMessenger.createLot(oObject);
			return this;
		},
		onSaveParticipantsList: function (iLotId) {
			_oMessenger.saveParticipantsList(iLotId);
			return this;
		},
		onLeaveLot: function (iLotId) {
			_oMessenger.leaveLot(iLotId);
			return this;
		},
		onViewDeletedJot: function (iJotId) {
			_oMessenger.viewJot(iJotId);
			return this;
		},
		onMuteLot: function (oEl, iLotId) {
			_oMessenger.muteLot(iLotId, oEl);
			return this;
		},
		onStarLot: function (oEl, iLotId) {
			_oMessenger.starLot(iLotId, oEl);
			return this;
		},
		onDeleteLot: function (iLotId) {
			_oMessenger.deleteLot(iLotId);
			return this;
		},

		showLotsByType: function (iType) {
			_oMessenger.searchByItems(iType);
			return this;
		},
		onDeleteJot: function (oObject, bCompletely) {
			_oMessenger.deleteJot(oObject, bCompletely);
		},
		onEditJot: function (oObject) {
			_oMessenger.editJot(oObject);
		},
		onSaveJot: function (oObject) {
			_oMessenger.saveJot(oObject);
		},
		onCancelEdit: function (oObject) {
			_oMessenger.cancelEdit(oObject);
		},
		onCopyJotLink: function (oObject) {
			_oMessenger.copyJotLink(oObject);
		},
		/**
		 * Methods below occur when messenger gets data from the server
		 */
		onTyping: function (oData) {
			_oMessenger.showTyping(oData);
			return this;
		},
		onMessage: function (oData) {
			const bSilent = _oMessenger.oSettings.user_id === oData.user_id || ( oData.type === 'vc' && oData.vc !== 'start' );

			try
			{
				if (!_oMessenger.isBlockVersion() && (!_oMessenger.isMobile() || (_oMessenger.isMobile() && oData.lot !== _oMessenger.oSettings.lot)))
					_oMessenger.upLotsPosition(oData, bSilent);

			} catch (e) {
				console.log('Lot list message update error', e);
			}

			try {
				if (oData.lot === _oMessenger.oSettings.lot)
					_oMessenger.updateJots(oData, bSilent);

				if (oData.type === 'vc' && +oData.lot)
					_oMessenger.getVideoCall(oData);

			} catch (e) {
				console.log('Talk history update error', e);
			}

			return this;
		},
		onStatusUpdate: function (oData) {
			_oMessenger.updateStatuses(oData);
			return this;
		},
		onServerResponse: function (oData) {
			if (oData.addon === undefined || !oData.addon.length)
				_oMessenger.sendPushNotification(oData);
			return this;
		},

		/**
		 * Loads form for files uploading in popup
		 *@param string sUrl link, if not specify default one will be used
		 *@param function fCallback callback function,  executes on window show
		 */
		showPopForm: function (sMethod, fCallback) {
			const sUrl = 'modules/?r=messenger/' + (sMethod || 'get_upload_files_form'),
				sText = _oMessenger.quill.getText();

			$(window).dolPopupAjax({
				url: sUrl,
				id: {force: true, value: _oMessenger.sAddFilesForm.substr(1)},
				onShow: function () {

					if (typeof fCallback == 'function')
						fCallback();

					if (sText.length)
						$(_oMessenger.sAddFilesFormComments).text(sText);

					setTimeout(() => _oMessenger.updateCommentsAreaWidth(), 100);
				},
				closeElement: true,
				closeOnOuterClick: false,
				removeOnClose: true,
				onHide: function () {
					_oMessenger.updateScrollPosition('bottom');
				}
			});
		},

		/**
		 * Executes on files uploading window close
		 *@param string sMessage confirmation message
		 *@param int iFilesNumber files number
		 */
		onCloseUploadingForm: function (sMessage, iFilesNumber) {
			if (!iFilesNumber || (iFilesNumber && confirm(sMessage))) {
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
		zoomImage: function (iId) {
			$(window).dolPopupAjax({
				url: 'modules/?r=messenger/get_big_image/' + iId + '/' + $(window).width() + '/' + $(window).height(),
				id: {force: true, value: 'bx-messenger-big-img'},
				top: '0px',
				left: '0px',
				onBeforeShow: function () {
					$('#bx-messenger-big-img, #bx-messenger-big-img .bx-popup-element-close, #bx-messenger-big-img img, #bx-popup-fog').click(function () {
						$('#bx-messenger-big-img').dolPopupHide().remove();
					});
				},
				closeElement: true
			});
		},

		/**
		 * Select giphy item to send, allows to add message to gif image.
		 *@param string sId of the image
		 */
		onSelectGiphy: function (oElement) {
			const oUploader = _oMessenger.oFilesUploader;

			if (oUploader && (oUploader.getFiles().length || oUploader.isLoadingStarted())) {
				$(_oMessenger.sGiphMain).fadeOut();
				return; 
			}
			else
				$(_oMessenger.sGiphMain)
					.fadeOut(() => {
						const oObject = $(oElement)
										.clone()
										.wrap('<div class="giphy-item"></div>')
										.parent();

						_oMessenger.updateSendArea(false);
						$(_oMessenger.sSendAttachmentArea)
							.html(
								oObject
									.append(
											$(`<i class="sys-icon times"></i>`)
												.on('click', function(){
													$(this)
														.closest('.giphy-item')
														.fadeOut()
														.remove();

													_oMessenger.updateSendArea(true);
												})
										)
						);
				});

		},
		loadTalkFiles: function (oBlock, iCount, fCallback) {
			bx_loading(oBlock, true);
			$.get('modules/?r=messenger/get_talk_files/', {
				number: iCount,
				lot_id: _oMessenger.oSettings.lot
			}, function (oData) {
				bx_loading(oBlock, false);

				if (!+oData.code && !($('.bx-msg-box-container', oBlock).length && $(oData.html).hasClass('bx-msg-box-container')))
					$(oBlock)
						.append(oData.html)
						.bxTime();

				if (typeof (fCallback) === 'function')
					fCallback(oData);
			}, 'json');

		},

		initJitsi: function(oJitsi, bNew = false, bChatSync = false){
			_oMessenger.oJitsi = oJitsi;

			if (oJitsi._lotId) {
				const oInfo = { type: 'vc', vc: 'start' };

				if (oJitsi._jotId && !bNew) {
					oInfo.jot_id = oJitsi._jotId;
					oInfo.vc = 'join';
				} else
                    _oMessenger.aJitisActiveUsers[oJitsi._lotId] = { owner: _oMessenger.oSettings.user_id, got: 0 };

				_oMessenger.broadcastMessage(oInfo);
				_oMessenger.updateJots(oInfo);

				$(window)
					.on('beforeunload', () => _t('_bx_messenger_are_you_sure_close_jisti'))
					.on('unload', () => {
						$.get('modules/?r=messenger/stop_jvc/', { lot_id: oJitsi._lotId }, (oData) => {
							const oInfo = {
								jot_id: oJitsi._jotId,
								addon: 'vc',
								type: 'vc',
								vc: 'stop'
							};
							_oMessenger.broadcastMessage(oInfo);
							_oMessenger.updateJots(oInfo);

						}, 'json');
						oJitsi.dispose();
					});

				if (bChatSync)
					oJitsi.on('outgoingMessage', ({ message }) => {
					_oMessenger.sendMessage(message, { vc: oJitsi._lotId});
					_oMessenger.updateJots({
						action: 'msg'
						});
					});

				oJitsi.on('videoConferenceLeft', () => {
					_oMessenger.closeJitsi(_oMessenger.sJitsiMain, oJitsi._lotId);
				});
			}
		},

		onJitsiClose:(oElement) => {
			_oMessenger.closeJitsi(oElement);
		},

		onJitsiFullScreen:() => {
			if (_oMessenger.oJitsi){
				let iframe = $('iframe[id^="jitsiConference"]');
				/*$(iframe).on('keydown', (event) => {
					if (event.keyCode === 115) event.stopPropagation();
				}, true);

				$(iframe).on('keypress', (event) => {
					if (event.keyCode === 115) event.stopPropagation();
				}, true);*/

				$(iframe).get(0).requestFullscreen();
			}
		},
		/**
		 * Run Jitsi video chat
		 *@param string sId of the image
		*/
		onStartVideoCall: function(oEl, iLotId){
            _oMessenger.startVideoCall(oEl, iLotId || _oMessenger.oSettings.lot);
		},
        getCall: function (oEl, iLotId, bAudioOnly = false){
			if (!iLotId)
				return ;

			if (_oMessenger.oSettings.lot !== iLotId)
				_oMessenger.loadTalk(iLotId);

			_oMessenger.startVideoCall(undefined, iLotId || _oMessenger.oSettings.lot, {
				startAudioOnly: +bAudioOnly,
				callback : () => {
					_oMessenger.onCloseCallPopup(oEl, iLotId, 'get_call');
				}
			});
        },
		/**
		 * Show only marked as important lot
		 *@param object oEl
		 */
		showStarred: function (oEl) {
			if (!_oMessenger.iStarredTalks) {
				$(oEl)
					.addClass('active')
					.find('i.star')
					.addClass('fill')
			} else
				$(oEl)
					.removeClass('active')
					.find('i.star')
					.removeClass('fill');

			_oMessenger.iStarredTalks = !_oMessenger.iStarredTalks;
			this.searchByItems($('#items').val());
		},

		removeFile: function (oEl, id) {
			$.get('modules/?r=messenger/delete_file', {id: id}, function (oData) {
				if (!parseInt(oData.code)) {
					if (!oData.empty_jot)
						$(oEl)
							.parents('.delete')
							.parent()
							.fadeOut('slow',
								function () {
									$(this).remove();
								});
					else
						$(oEl)
							.parents(_oMessenger.sJot)
							.fadeOut('slow',
								function () {
									$(this).remove();
								});
				} else
					bx_alert(oData.message);
			}, 'json');
		},

		downloadFile: function (iFileId) {
			$.get('modules/?r=messenger/download_file/' + iFileId, {id: iFileId}, function (oData) {
				if (parseInt(oData.code))
					bx_alert(oData.message);
			});
		},
		sendVideoRecord: function (oFile, oCallback) {
			let fileName = (new Date().getTime()),
				formData = new FormData();

			if (oFile.type !== undefined) {
				let sExt = (~oFile.type.indexOf(';') ? oFile.type.substring(0, oFile.type.lastIndexOf(';')) : oFile.type).replace('video/', '.');
				fileName += sExt;
			};

			formData.append('name', fileName);
			formData.append('file', oFile);

			bx_loading($(_oMessenger.sFilesUploadAreaOnForm), true);
			$.ajax({
				url: 'modules/?r=messenger/upload_video_file',
				data: formData,
				type: 'POST',
				dataType: 'json',
				contentType: false,
				processData: false,
			})
				.done(
					function (oData) {
						bx_loading($(_oMessenger.sFilesUploadAreaOnForm), false);
						if (!parseInt(oData.code)) {
							const sMessage = $(_oMessenger.sAddFilesFormComments).text();
							_oMessenger.sendMessage(sMessage, { files: [fileName] }, function () {
								if (typeof oCallback == 'function')
									oCallback();
							});
						} else
							bx_alert(oData.message);
					});


		},
		updateCommentsAreaWidth: function (fWidth) {
			_oMessenger.updateCommentsAreaWidth(fWidth);
		},
		onAddReaction: function (oObject, bNear) {
			_oMessenger.onAddReaction(oObject, bNear);
		},
		onRemoveReaction: function (oObject) {
			const _this = this,
				oEmoji = $(oObject),
				oReactionArea = oEmoji.closest(_oMessenger.sReactionsArea),
				iJotId = +$(oEmoji).closest(_oMessenger.sJot).data('id'),
				sEmojiId = $(oEmoji).data('emoji'),
				fUpdateParams = () => {
					$(oEmoji).data('part', aParts.join(','));
					$(oEmoji).data('count', aParts.length);
					_oMessenger.broadcastMessage({
						jot_id: iJotId,
						addon: 'reaction'
					});
				};

			if (!iJotId)
				return;

			let iCount = +$(oEmoji).data('count'),
				aParts = $(oEmoji)
							.data('part')
							.toString()
							.split(',');

			if (~aParts.indexOf(_oMessenger.oSettings.user_id + '')) {
				if (iCount === 1)
					$(oEmoji)
						.fadeOut()
						.remove();
				else
					$('> span.count', oEmoji)
						.text(--iCount);

				aParts = aParts.filter((iPart) => {
					return +iPart !== _oMessenger.oSettings.user_id
				});

				$.get('modules/?r=messenger/update_reaction', {
					jot: iJotId,
					emoji_id: sEmojiId,
					action: 'remove'
				}, function (oData) {
					if (!parseInt(oData.code)) {
						fUpdateParams();
						if (!$(_oMessenger.sReactionItem, oReactionArea).length)
							$(_oMessenger.sReactionMenu, oReactionArea).fadeOut();
					}
				}, 'json');
			} else {
				iCount++;
				aParts.push(_oMessenger.oSettings.user_id);
				if ($('> span.count', oEmoji).length)
					$('> span.count', oEmoji).text(iCount);
				else
					$(oEmoji).append(`<span>${iCount}</span>`);

				_oMessenger.playSound('reaction');
				$.get('modules/?r=messenger/update_reaction', {
					jot: iJotId,
					emoji_id: sEmojiId,
					action: 'add'
				}, function (oData) {
					if (!parseInt(oData.code))
						fUpdateParams();
				}, 'json');
			}
		},
		onEmojiInsert: (oEmoji) => {
			if (_oMessenger.oActiveEmojiObject['type'] === 'reaction' && +_oMessenger.oActiveEmojiObject['param'])
				_oMessenger.onJotAddReaction(oEmoji, +_oMessenger.oActiveEmojiObject['param']);
			else
			if (_oMessenger.oActiveEmojiObject['type'] === 'textarea')
				_oMessenger.onTextAreaAddEmoji(oEmoji);

			setTimeout(() => $(`${_oMessenger.sEmojiId}`)
				.hide(), 0); // if to close in the same time, emoji categories will not work in the next open
		},
		cleanGiphyAreas: () => {
			if ($(_oMessenger.sSendAttachmentArea).children().length) {
				$(_oMessenger.sGiphMain).fadeOut();
				$(_oMessenger.sSendAttachmentArea).html('');
			}
		},
		updateJots(oJots, sType){
			if (Array.isArray(oJots))
				oJots.map( iJot => _oMessenger.updateJots({
					action: sType || 'edit',
					jot_id: iJot
				}));
		},
		onHangUp: (oEl, iLotId) => {
			_oMessenger.onCloseCallPopup(oEl, iLotId);
		},
		initGiphy: function () {
			let iTotal = 0;
			let iScrollPosition = 0;
			const oContainer = $(_oMessenger.sGiphyItems),
				oScroll = $('.bx-messenger-giphy-scroll'),
				fInitVisibility = (sType, sValue) => {
					let stopLoading = false;
					oScroll.on('scroll', (e) => {
							const { scrollLeft,  scrollWidth, clientWidth} = e.currentTarget;
							const iItems = $('picture', oContainer).length;
							const scrollLeftMax = scrollWidth - clientWidth;
							let	bPassed = scrollLeft >= scrollLeftMax*0.6; // 60% passed
							iScrollPosition = scrollLeft;

							if (!bPassed || (iTotal && iItems && iItems >= iTotal))
								return;

							if (!stopLoading) {
								stopLoading = true;
								fGiphy(sType, sValue, () => setTimeout(() => {
									stopLoading = false;
									oScroll.scrollLeft(iScrollPosition);
								}, 0));
							}
					});
				},
				fGiphy = (sType, sValue, fCallback) => {
					const fHeight = oContainer.height();
					$('div.search', _oMessenger.sGiphyBlock).addClass('loading');
					$.get('modules/?r=messenger/get_giphy', {
							height: fHeight,
							action: sType,
							filter: sValue,
							start: $('picture', oContainer).length
						}, function (oData) {
							iTotal = oData.total;

							oContainer
								.append(
									oData.code
										? oData.message
										: oData.html
								)
								.setRandomBGColor();

							$('div.search', _oMessenger.sGiphyBlock).removeClass('loading');
							if (typeof fCallback === 'function')
								fCallback(sType, sValue);
						},
						'json');
				};

			if ($(_oMessenger.sGiphMain).css('visibility') === 'visible') {
				let iTimer = 0;
				$('input', _oMessenger.sGiphyBlock).keypress(function (e) {
					clearTimeout(iTimer);
					iTimer = setTimeout(() => {
						iScrollPosition = 0;
						iTotal = 0;
						oContainer.html('');
						oScroll.scrollLeft(0);
						const sFilter = $(this).val();
						fGiphy(sFilter && 'search', sFilter, fInitVisibility);

					}, 1000);
					return true;
				}).on('keydown', function (e) {
					if (e.keyCode === 8 || e.keyCode === 46)
						$(this).trigger('keypress');
				});

				if (oContainer && !oContainer.find('img').length) {
					fGiphy(undefined, undefined, fInitVisibility);
				}
			}
		},
		updateAttachmentArea: function(bCanHide){
			return _oMessenger.updateSendArea(bCanHide);
		},
		initUploader: oUploader => _oMessenger.oFilesUploader = oUploader
	}
})(jQuery);

/** @} */
