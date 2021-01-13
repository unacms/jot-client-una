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
		this.sFriendsList = '.bx-messenger-friends-list';
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
		this.sJotAreaInfo = '.bx-messenger-jots-info';
		this.sAttachmentBlock = '.bx-messenger-attachment';
		this.sAttachmentFiles = '.bx-messenger-attachment-files';
		this.sGiphyImages = '.bx-messenger-static-giphy';
		this.sAttachmentImages = '.bx-messenger-attachment-file-images';
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
		this.sConversationBlockWrapper = '.bx-messenger-conversation-block-wrapper';
		this.sUnreadJotsCounter = '#unread-jots-counter';

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
		this.iMaxHistory = oOptions.max_history_number || 50;
		this.iStatus = document.hasFocus() ? 1 : 2; // 1- online, 2-away
		this.iActionsButtonWidth = '2.25';
		this.iScrollDownSpeed = 1500;
		this.aJitisActiveUsers = {};
		this.iHideUnreadBadge = 1000;
		this.iRunSearchInterval = 500; // seconds
		this.iMinHeightToStartLoading = 5; // scroll height to start history loading
		this.iMinTimeBeforeToStartLoadingPrev = 300; // 300 millseconds before to start loading history
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
		this.direction = oOptions.direction || 'LTR';
		this.aPlatforms = ['MacIntel', 'MacPPC', 'Mac68K', 'Macintosh', 'iPhone', 'iPod', 'iPad', 'iPhone Simulator', 'iPod Simulator', 'iPad Simulator', 'Pike v7.6 release 92', 'Pike v7.8 release 517'];
		this.oStorage = null;
		this.oHistory = window.history || {};
		this.oFilesUploader = null;
		this.oActiveAudioInstance = null;
		this.oActiveEmojiObject = Object.create(null);
		this.sJitsiServerUrl = oOptions.jitsi_server;
		this.aDatesItervals = [];
		this.sDateIntervalsSelector = '.bx-messenger-date-time-hr';
		this.sDateIntervalsItem = `${this.sDateIntervalsSelector} > .bx-messenger-date-time-value`;
		this.iScrollbarWidth = 0;
		this.sDateIntervalsTemplate = oOptions.date_intervals_template;
		this.aLoadingRequestsPool = []; // contains requests to load the talks when member clicks many talks with small delay and there is not enough time to load each talk

		const _this = this;
		$(this).on('message', () => this.beep());

		// Lot's(Chat's) settings
		this.oSettings = {
							'url'	 : oOptions.url || window.location.href,
							'title'  : document.title || '',
							'lot'	 : oOptions.lot || 0,
							'name': (oOptions && oOptions.name) || 0,
							'user_id': (oOptions && oOptions.user_id) || 0
						};

		this.iSelectedJot = oOptions.jot_id || 0;
		this.iLastUnreadJot = oOptions.last_unread_jot || 0;
		this.iUnreadJotsNumber = oOptions.unread_jots || 0;
		this.bAllowAttachMessages = oOptions.allow_attach || 0;
		this.iLotType = oOptions.type || 0;
		this.iSelectedPersonToTalk = oOptions.selected_profile || 0;
		this.isBlockMessenger = typeof oOptions.block_version !== 'undefined' ? oOptions.block_version : $(this.sLotsBlock).length === 0;

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

	oMessenger.prototype.initHeaderButtons = function(){
		$('span.info-menu > i').popup({
			on: 'click',
			hoverable: true,
			boundary: $(this.sJotsBlock)
		});
	}
	/**
	* Init current chat/talk/lot settings
	*/

	oMessenger.prototype.initLotSettings = function(oOptions){
		const _this = this,
			{ lot, jot, last_unread_jot, unread_jots } = oOptions || {};

		this.oSettings.lot = lot || 0;
		this.iSelectedJot = jot || 0;
		this.iLastUnreadJot = last_unread_jot || 0;
		this.iUnreadJotsNumber = unread_jots || 0;

		_this.updateCounters(unread_jots, true);

		/* runs periodic to find not processed videos in chat history and replace them with processed videos */
		setInterval(
			function()
			{
				_this.updateProcessedMedia();
			}, _this.iUpdateProcessedMedia
		);

		this.initJotIcons(this.sTalkList);

		_this.initHeaderButtons();

		if (!_this.oRTWSF.isInitialized() || (!lot && !_this.iSelectedPersonToTalk && _this.iLotType === 2))
			_this.blockSendMessages(true);
		else
			_this.blockSendMessages(false);
	};

	oMessenger.prototype.initScrollArea = function() {
		const _this = this;
		let aReadJots = [];
		let iBottomScreenPos = $(_this.sTalkBlock).scrollTop() + $(_this.sTalkBlock).innerHeight();
		let bStartLoading = !(_this.iLastUnreadJot || _this.iSelectedJot);
		let iCounterValue = +$(_this.sUnreadJotsCounter).text();
		let iUpdateCounter = null;

		// find the all intervals in history
		_this.iScrollbarWidth = $(_this.sTalkBlock).prop('offsetWidth') - $(_this.sTalkBlock).prop('clientWidth');

		$(_this.sScrollArea).css('right', parseInt($(_this.sScrollArea).css('right')) + _this.iScrollbarWidth + 'px');
		$(_this.sTalkBlock).scroll(function(){
			const isScrollAvail = $(this).prop('scrollHeight') > $(this).prop('clientHeight'),
				iScrollHeight = $(this).prop('scrollHeight') - $(this).prop('clientHeight'),
				iScrollPosition = $(this).prop('scrollHeight') - $(this).prop('clientHeight') - $(this).scrollTop(),
				iPassPixelsToStart = isScrollAvail ? _this.iMinHeightToStartLoading/100 * $(this).prop('clientHeight') * iScrollHeight/$(this).prop('clientHeight') : 0,
				isTopPosition = ($(this).scrollTop() <= iPassPixelsToStart) || ($(this).scrollTop() === 0),
				isBottomPosition = $(this).scrollTop() > ($(this).prop('scrollHeight') - $(this).prop('clientHeight') - iPassPixelsToStart);

			if (!isScrollAvail)
				return;

			iBottomScreenPos = $(_this.sTalkBlock).scrollTop() + $(_this.sTalkBlock).innerHeight();
			if (_this.iLastUnreadJot) {
				iCounterValue = +$(_this.sUnreadJotsCounter).text();
				$(_this.sTalkListJotSelector).each(function () {
					const iId = +$(this).data('id');
					const { top } = $(this).position();
					const iJotBottomPos = top + $(this).innerHeight();

					if (iId < _this.iLastUnreadJot)
						return;

					if (iId > _this.iLastUnreadJot && iJotBottomPos <= iBottomScreenPos) {
						aReadJots.push(iId);
						$(_this.sUnreadJotsCounter).text(--iCounterValue);
						if (!iCounterValue)
							$(_this.sUnreadJotsCounter).hide();

						clearTimeout(iUpdateCounter);
					}
				});

				if (aReadJots.length && _this.iLastUnreadJot !== aReadJots[aReadJots.length - 1]) {
					_this.iLastUnreadJot = aReadJots[aReadJots.length - 1];
					iUpdateCounter = setTimeout(() => {
						if (document.hasFocus()) {
							_this.broadcastView(_this.iLastUnreadJot);
							_this.updateCounters(iCounterValue);
						}
					}, 1000);
				}
			}

			if (!isBottomPosition && !isTopPosition && bStartLoading)
				bStartLoading = false;

			if (iScrollPosition <= $(_this.sTalkListJotSelector).last().height() && !iCounterValue)
				$(_this.sScrollArea).fadeOut();
			else
				$(_this.sScrollArea).fadeIn();

			if ((isBottomPosition || isTopPosition)){
				if (!bStartLoading) {
					bStartLoading = true;
					_this.iLoadTimout = setTimeout(
						function () {
							_this.updateJots({
								action: isTopPosition ? 'prev' : 'new',
								position: isBottomPosition ? 'freeze' : 'position',
								last_viewed_jot: _this.iLastUnreadJot
							});
						}, _this.iMinTimeBeforeToStartLoadingPrev);
				}
			}
			else
				clearTimeout(_this.iLoadTimout);

			_this.attachDate($(this).scrollTop());
		});
	}

	oMessenger.prototype.updateDateList = function() {
		this.aDatesItervals = $(this.sDateIntervalsSelector).get().reverse();
	}

	oMessenger.prototype.attachDate = function(iScrollTop) {
		if (!iScrollTop)
			return ;

		const _this = this;
		_this.aDatesItervals.some(function(oDateObject){
			const { top } = $(oDateObject).position();
			const oItem = $('>div', oDateObject);
			if (top <= iScrollTop) {
				$('>div', _this.sDateIntervalsSelector)
					.removeClass('attached')
					.css('width', '100%');

				oItem
					.addClass('attached')
					.css('width', `calc( 100% - ${_this.iScrollbarWidth}px )`);
				return true;
			}

			return false;
		});
	}

	oMessenger.prototype.loadTalkFiles = function(oBlock, iCount, fCallback) {
		bx_loading(oBlock, true);
		$.get('modules/?r=messenger/get_talk_files/', {
			number: iCount,
			lot_id: this.oSettings.lot
		}, function ({ code, html, total }) {
			bx_loading(oBlock, false);

			if (!code && !($('.bx-msg-box-container', oBlock).length && $(html).hasClass('bx-msg-box-container')))
				$(oBlock)
					.append(html)
					.bxTime();

			if (typeof (fCallback) === 'function')
				fCallback({ total });
		}, 'json');

	};

	oMessenger.prototype.updateLotSettings = function(oOptions) {
		const oSidebarPanel = $(this.sConversationBlockWrapper),
			  _this = this;

			_this.initLotSettings(oOptions);
			$('.ui.sidebar', oSidebarPanel)
				.sidebar({
					context: oSidebarPanel,
					dimPage: true,
					scrollLock: true,
					onVisible:function(){
						const { context } = $(this);
						if ($(context).hasClass('files') && !$('.event', context).length){
							_this.loadTalkFiles($('.segment', context), $('.event', context).length, ({ total }) =>
							{
										let bPassed = false;
										const iTotal = total || 0;

										if (!iTotal)
											return ;

										$('.segment', context)
											.visibility({
												once: false,
												continuous: true,
												context: context,
												// load content when scroll passed 60%
												onUpdate: function({ height, pixelsPassed, percentagePassed }){
													const iItems = $('.event', $(this)).length,
														iViewArea = $(context).height(),
														iPassed = pixelsPassed >= (height-iViewArea)/2;

													if (!iTotal || !percentagePassed || iItems >= iTotal || !iPassed)
														return ;

													if (!bPassed) {
														bPassed = true;
														_this.loadTalkFiles($(this), iItems, () => setTimeout(() => bPassed = false, 0));
													}
												}
											});
									}
								);
						}

					}
				})
				.sidebar('setting', {
					transition: 'overlay',
					mobileTransition: 'overlay'
				});
	}

	oMessenger.prototype.initTextEditor = function(oSettings) {
		return new oMessengerEditor(Object.assign({}, oSettings, {
			showToolbar: () => !this.isMobile()
		}));
	}

	oMessenger.prototype.initTextArea = function() {
		const _this = this;

		this.oEditor = this.initTextEditor({
			selector: this.sMessengerBox,
			placeholder: _t('_bx_messenger_post_area_message'),
			onEnter: () => $(_this.sSendButton).click(),
			onUp: () => {
				if ($(_this.sTalkListJotSelector).length && _this.oEditor.length <= 1) {
					const oJot = $(`${_this.sTalkListJotSelector}`).last();
					if (+oJot.data('my'))
						_this.editJot(oJot);
					return false;
				}
				return true;
			},
			onChange: () => {
				const { ops } = _this.oEditor.getContents();

				_this.updateSendAreaHeight();

				if (_this.oEditor.length > 1)
					_this.oStorage.saveLot(_this.oSettings.lot, JSON.stringify(ops));
				else
					_this.oStorage.deleteLot(_this.oSettings.lot);

				// show typing area when member post the message
				_this.oRTWSF.typing({
					lot: _this.oSettings.lot,
					name: _this.oSettings.name,
					user_id: _this.oSettings.user_id
				});

				_this.updateSendButton();
		  }
		});

		// when member clicks on send message icon
		$(this.sSendButton).on('click', () => {
			if (_this.sendMessage(_this.oEditor.length === 1 ? '' : _this.oEditor.html())){
				_this.oEditor.setContents([]);
				_this.oEditor.focus();
				_this.oFilesUploader.clean();
				_this.oStorage.deleteLot(_this.oSettings.lot);
				$(_this.sSendButton).hide();
			}
		});

		//remove all edit jot areas on their lost focus
		$(document).on('mouseup', function(oEvent){
			_this.removeEditArea(oEvent);
		})
		.on('click touchstart', (oEvent) => _this.onOuterClick(oEvent));

		this.updateSendAreaButtons();

		$(_this.sSendAreaActionsButtons)
			.find('a.smiles')
			.on('click', () => _this.getEmojiPopUp(() => {
					const oEmoji = $(_this.sEmojiId),
						bHidden = !oEmoji.is(":visible"),
						iParentHeight = $(_this.sTalkAreaWrapper).height();

					if (bHidden)
						oEmoji.fadeIn('fast', function () {
							const iHeight = oEmoji.height();

							$(this).css({
								top: iParentHeight - iHeight - $(_this.sMessangerParentBox).height(),
								left: '0.5rem',
								right: '',
								visibility: 'visible'
							});
						});
					else
						oEmoji.fadeOut().css('visibility', 'hidden');

					_this.oActiveEmojiObject = {'type': 'textarea'};
				}));

		// enable video recorder if it is not IOS/Mac devices
		if(!this.aPlatforms.includes(navigator.platform))
			$(_this.sSendAreaActionsButtons)
				.find('li.video').show();

		$(_this.sGiphySendArea)
			.on('click',
					function(e){
						if ($(_this.sGiphMain).is(':visible'))
							$(_this.sGiphMain).fadeOut();
						else
							$(_this.sGiphMain).fadeIn(function(){
								$(this).css('display', 'flex');
								_this.initGiphy(e);
							});
					});
    }

   oMessenger.prototype.getEmojiPopUp = function(fCallback) {
	   const _this = this;
		if (!$('emoji-picker').length)
		   $.get('modules/?r=messenger/get_emoji_picker',(sData) => {
			   $(_this.sTalkAreaWrapper)
				   .append($(sData));

			   fCallback();
		   });
	   else
		   fCallback();
	}

	oMessenger.prototype.updateSendAreaHeight = function() {
		const fMaxHeight = parseInt($(this.sMessengerBox).css('max-height'));
		if ($(this.sMessengerBox).outerHeight() >= fMaxHeight)
			$(this.sMessengerBox).css('overflow-y', 'auto');
		else
			$(this.sMessengerBox).css('overflow-y', 'visible');
	}

	oMessenger.prototype.updateSendArea = function(bFilesEmpty){
		if (bFilesEmpty)
			$(this.sBottomGroupsArea).hide();
		else
			$(this.sBottomGroupsArea).show();

		if (this.oEditor.length <= 1 && bFilesEmpty)
			$(this.sSendButton).fadeOut();
		else
			$(this.sSendButton).fadeIn();
	};

	oMessenger.prototype.updateSendButton = function(){
		const { length } = this.getSendAreaAttachmentsIds(false);
		const iFiles = this.oFilesUploader && this.oFilesUploader.getFiles().length;

		if (this.oEditor.length <= 1 && !length && !iFiles)
			$(this.sSendButton).fadeOut();
		else
			$(this.sSendButton).fadeIn();
	};

	oMessenger.prototype.onOuterClick = function(oEvent){
		if (!($(oEvent.target).is('[class*=smile]') || $(oEvent.target).closest(this.sEmojiId).length || $(oEvent.target).siblings('[class*=smile]').length))
			$(`${this.sEmojiId}`)
				.hide()
				.css('visibility', 'hidden');

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

		// If member didn't finish the message, add it to post message area
		let sStorageMessage = this.oStorage.getLot(this.oSettings.lot);

		_this.oEditor.setContents([]);
		if (typeof sStorageMessage === 'string' && sStorageMessage.length){
			let mixedValue = JSON.parse(sStorageMessage);
			if (Array.isArray(mixedValue)) {
				_this.oEditor.setContents(mixedValue);
				$(_this.sSendButton).fadeIn();
			}
			else
				_this.oEditor.setText(mixedValue);

			_this.updateSendAreaHeight();
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

	oMessenger.prototype.isMobileDevice = function(){
		return 	(
				/(android|bb\d+|meego|UNA).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino|android|ipad|playbook|silk/i.test(navigator.userAgent||navigator.vendor||window.opera)
					||
				/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test((navigator.userAgent||navigator.vendor||window.opera).substr(0,4))
				);
	}

	oMessenger.prototype.isBlockVersion = function(){
		return this.isBlockMessenger;
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
			{ lot, user } = oOptions || {};

		bx_loading($(_this.sMainTalkBlock), true);

		_this.oSettings.lot = +lot;
		// block send area if it is new talk
		if (!lot)
			_this.blockSendMessages(true);

		$.post('modules/?r=messenger/create_lot', { profile: user || 0, lot: lot || 0 }, function({ header, code, history, title, message }){
			bx_loading($(_this.sMainTalkBlock), false);
					if (!code){
						$(_this.sJotsBlock)
							.find('.bx-db-header')
							.replaceWith(header);

						if (!lot) {
							$(_this.sJotsBlock)
								.find(_this.sTalkBlock)
								.html(history);
						}

						if (typeof title !== 'undefined')
							$(document).prop('title', title);
					
						_this.updateScrollPosition('bottom');

						_this.initUsersSelector(+lot ? 'edit' : '');
						if (_this.oJotWindowBuilder !== undefined)
							_this.oJotWindowBuilder.changeColumn('right');

						if (user) {
							_this.iSelectedPersonToTalk = user;
							_this.blockSendMessages(false);
						}

					} else
                    {
                        if (message)
                            bx_alert(message);
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

							if (oData.header) {
								$(_this.sJotsBlock)
									.find('.bx-db-header')
									.replaceWith(oData.header)

								if (_this.oJotWindowBuilder !== undefined)
									_this.oJotWindowBuilder.updateColumnSize();

								_this.initHeaderButtons();
							}

							if (oData.lot) {
								_this.oSettings.lot = oData.lot;
								_this.blockSendMessages(false);
								if (!_this.isBlockVersion())
									_this.upLotsPosition(_this.oSettings);
							}
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
		const _this = this,
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
			iJotId = oJot.data('id') || 0;


			_this.getEmojiPopUp(() => {
				const oEmoji = $(_this.sEmojiId);
				oEmoji.fadeIn('fast', function() {
					$(this).css({
						top: _this.calculatePositionTop(oJot, $(_this.sEmojiId)),
						left: bNear && !_this.isMobile() ? $(oObject).position().left : '',
						visibility: 'visible'
					});
					_this.oActiveEmojiObject = {'type': 'reaction', 'param': iJotId};
				})
			});
	};

	oMessenger.prototype.deleteLot = function(iLotId){
		const _this = this;
		if (iLotId)
				$.post('modules/?r=messenger/delete', { lot: iLotId }, function(oData){
					if (parseInt(oData.code) === 1)
							window.location.reload();
		
						if (!parseInt(oData.code))
						{
							if (_this.isBlockVersion())
                                window.location.reload();
							else
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

							_this.broadcastMessage({
								action: 'msg',
								addon: 'remove_lot',
								lot: iLotId
							});
						}
				}, 'json');
	};

	oMessenger.prototype.clearLot = function(iLotId){
		const _this = this;
		if (iLotId) {
			bx_loading($(_this.sMainTalkBlock), true);
			$.post('modules/?r=messenger/clear_history', {lot: iLotId}, function ({code, message}) {
				if (!parseInt(code)) {
					bx_loading($(_this.sMainTalkBlock), false);
					$(_this.sTalkList).html('');
					_this.broadcastMessage({
						action: 'msg',
						addon: 'clear'
					});
				} else
					bx_alert(message);
			}, 'json');
		}
	};

	oMessenger.prototype.deleteJot = function(oObject, bCompletely){
		const _this = this,
			oJot = $(oObject).closest(this.sJot),
			iJotId = oJot.data('id') || 0,
			checkScroll	= function()
			{
				if ($(_this.sTalkBlock).prop('scrollHeight') <= $(_this.sTalkBlock).prop('clientHeight') && $(_this.sJot, _this.sTalkBlock).length === 1)
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
									if (!$(this).next(_this.sJot).length)
										$(this).prev(_this.sDateIntervalsSelector).remove();

									$(this).remove();
									checkScroll();
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

		this.oEditor.focus();
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
							const oEditEditor = _this.initTextEditor({
								selector: _this.sEditJotAreaId,
								onEnter: () => {
									_this.saveJot($(_this.sEditJotAreaId));
									_this.oEditor.focus();
								},
								onChange: () => updateScrollFunction(),
								onESC: () => {
									_this.cancelEdit(__this);
									_this.oEditor.focus();
								}
							});

							_this.oActiveEditQuill = oEditEditor.oEditor;
							oEditEditor.focus();

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

		// IOS mobile devices only
		if (_this.aPlatforms.includes(navigator.platform) && _this.isMobileDevice()) {
			bx_prompt('&nbsp;', _this.sJotUrl + iJotId, () => {
				$('#bx-popup-prompt-value').get(0).setSelectionRange(0, 999999);
				if (document.queryCommandSupported("copy")) {
					document.execCommand('copy');
					$('#bx-popup-prompt').dolPopupHide();
					$('#bx-popup-prompt-value').get(0).blur();
				}
			}, undefined, {
				ok:{
					title: _t('_bx_messenger_share_jot')
				}
			});

			// iOS only selects "form" elements with SelectionRange
			return ;
		}

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
			const aMatch = sText.match(/<a[^"](?!class="bx-mention[^"]*").*href="(https?:\/\/[a-z0-9-+&@#\/%?=~_|!:,.;]*[a-z0-9-+&@#\/%=~_|])".*a>/);
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
		$.post('modules/?r=messenger/get_attachment', { jot_id: iJotId}, function(oData)
		{
			if (!parseInt(oData['code']))
			{
				$(_this.sReactionsArea, '[data-id="' + iJotId + '"]')
					.before(
								$(oData['html'])
									.waitForImages(() => _this.updateScrollPosition('bottom'))
							);

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
			.find('> div')
			.removeClass('bx-def-font-semibold')
			.find('div')
			.removeClass('bx-def-font-extrabold');
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
		const aImg = $(`${_oMessenger.sGiphyImages} img, ${_oMessenger.sAttachmentImages} img`, $(this));
		let iTotalImg = aImg.length;
		const waitImgLoad = () =>
			{
				iTotalImg--;
				if (!iTotalImg && typeof fCallback === 'function')
					fCallback(this);
			};

			if (!iTotalImg) {
				if (typeof fCallback === 'function')
					setTimeout(() => fCallback(this), 0);
			}
			else
				aImg.one('load',function(){
					waitImgLoad();
				}).each(function() {
					if(this.complete)
						$(this).load();
				});

		return this;
	};

    oMessenger.prototype.markJotsAsRead = function(iLotId, fCallback){
        $.post('modules/?r=messenger/mark_jots_as_read', { lot: iLotId }, function(oData){
            if (!parseInt(oData.code) && typeof fCallback === 'function')
                fCallback();
        }, 'json');
    };

    oMessenger.prototype.updateCounters = function(iNumber, bForceUpdate = false){
		const oCounter = $(`${this.sLotSelector}[data-lot="${this.oSettings.lot}"] ${this.sBubble}`);
		const iCounter = bForceUpdate ? iNumber : +$(this.sUnreadJotsCounter).text();

		if (iCounter)
			oCounter.show().text(iCounter);
		else
		{
			oCounter.hide();
			$(this.sUnreadJotsCounter).hide();
		}

		if (bForceUpdate) {
			$(this.sUnreadJotsCounter).text(iCounter);
			if (iCounter)
				$(this.sUnreadJotsCounter).show();
		}

		if (!$(this.sUnreadJotsCounter).is(':visible'))
			oCounter.hide();

		this.iUnreadJotsNumber = iCounter;
	}

    oMessenger.prototype.broadcastView = function(iJotId){
		const iJot = iJotId ? iJotId : (this.iLastUnreadJot ? this.iLastUnreadJot : $(this.sTalkListJotSelector).last().data('id'));
		const oJot = $(`[data-id="${iJot}"]`, this.sTalkList);

		if (!+oJot.data('my') && +oJot.data('new')) {
			$.get('modules/?r=messenger/viewed_jot', { jot_id: iJot }, ({ unread_jots, last_unread_jot }) => {
				this.updateCounters(unread_jots, true);
				this.iLastUnreadJot = last_unread_jot;
			}, 'json');

			oJot.data('new', 0);

			return this.broadcastMessage({
				jot_id: iJot,
				addon: 'check_viewed'
			});
		}

		return;
    };

	/**
	 * Load history for selected lot
	 * @param iLotId
	 * @param iJotId
	 * @param bDontChangeCol
	 * @param fCallback
	 * @param bMarkAsRead
	 */
	oMessenger.prototype.loadTalk = function(iLotId, iJotId, bDontChangeCol, fCallback, bMarkAsRead = false){
		const _this = this,
              fEmpty = { done: (r) => r()};

		if (!iLotId)
			return fEmpty;

		const oLotBlock = $(`[data-lot="${iLotId}"]${this.sLotSelector}`);
        _this.selectLotEmit(oLotBlock);
        if (_this.isActiveLot(iLotId))
		{
			if (_this.isMobile() && !bDontChangeCol) {
				_this.oJotWindowBuilder.changeColumn();
				_this.updateScrollPosition('bottom');
				return fEmpty;
			}
		}
        else
        {
			$(_this.sJotsBlock)
				.find('.bx-db-header')
				.html('')
				.end()
				.find(_this.sTalkList)
				.html('');
		}

    	bx_loading($(this.sMainTalkBlock), true);

        // to change active lot ID in the same time when user click on load but not when history is loaded
		_this.oSettings.lot = iLotId;
		_this.checkNotFinishedTalks();
		_this.blockSendMessages(true);

		return $.post('modules/?r=messenger/load_talk', { lot_id: iLotId, jot_id: iJotId, mark_as_read: +bMarkAsRead },
			function({ title, history, header, code, unread_jots, last_unread_jot })
		{

			bx_loading($(_this.sMainTalkBlock), false);
			if (+code)
				window.location.reload();
			else
			if (~code)
			{
				_this.blockSendMessages(false);
				$(_this.sJotsBlock)
						.find('.bx-db-header')
						.replaceWith(header)
						.end()
						.find(_this.sTalkBlock)
						.html(history)
						.end()
						.bxTime()
						.addTimeIntervals()
						.show(
							function()
							{
								if (_this.oJotWindowBuilder) {
									
									if (!bDontChangeCol)
										_this.oJotWindowBuilder.changeColumn();
									else
										_this.oJotWindowBuilder.updateColumnSize();
								}

								_this.updateLotSettings({
									lot: iLotId,
									last_unread_jot,
									unread_jots
								});

								_this.updateCounters(unread_jots, true);
								_this.updatePageIcon(undefined, iLotId);
							}
						)
						.waitForImages(() => _this.setPositionOnSelectedJot(fCallback));

					if (typeof title !== 'undefined')
						$(document).prop('title', title);

					_this.setUsersStatuses(oLotBlock);

					_this.blockSendMessages();

					if (+$(_this.sTalkListJotSelector).last().data('new')) {
						if (!_this.iLastUnreadJot || $(_this.sSelectedJot, _this.sTalkList).nextAll(_this.sJot).length < _this.iMaxHistory/2)
						_this.broadcastView($(_this.sTalkListJotSelector).last().data('id'));
					}
					/* ----  End ---- */
				}
		}, 'json');
	};

	oMessenger.prototype.loadJotsForLot = function(iLotId, fCallback){
		const _this = this;
		
		bx_loading($(this.sJotsBlock), true);
		$.post('modules/?r=messenger/load_jots', { id: iLotId }, function({ code, history }){
			bx_loading($(_this.sJotsBlock), false);
			if (code === 1)
					window.location.reload();
						
			if (!parseInt(code))
			{
				$(_this.sJotsBlock)
					.find(_this.sTalkBlock)
					.html(history)
					.end()
					.bxTime()
					.waitForImages(() => _this.updateScrollPosition('bottom'));

					if (typeof fCallback == 'function')
						fCallback();
			}
		}, 'json');	
	}
		
	oMessenger.prototype.sendPushNotification = function(oData){
			const { sent, addon: { jot_id }, lot } = oData;
			$.post('modules/?r=messenger/send_push_notification', { sent, jot_id, lot });
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
		if (!+_this.oSettings.lot){
			if (!oParams.participants.length && +_this.iSelectedPersonToTalk) {
				oParams.participants.push(_this.iSelectedPersonToTalk);
				_this.iSelectedPersonToTalk = 0;
			}
			oParams.type = this.iLotType;
		}

		oParams.message = $.trim(sMessage);
		if (!oParams.message.length && !oParams.files.length && typeof oParams.giphy === 'undefined')
			return;

		oParams.tmp_id = msgTime.getTime();

		// remove MSG (if it exists) from clean history page
		if ($('.bx-msg-box-container', _this.sTalkList).length)
				$('.bx-msg-box-container', _this.sTalkList).remove();	
		
		if (oParams.message.length > this.iMaxLength) 
			oParams.message = oParams.message.substr(0, this.iMaxLength);

		if ((oParams.message || oParams.files.length || (typeof oParams.giphy !== 'undefined' && oParams.giphy.length)) && _this.bAllowAttachMessages)
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

		_this.updateScrollPosition('bottom');

		// save message to database and broadcast to all participants
		$.post('modules/?r=messenger/send', oParams, function({ jot_id, header, tmp_id, message, code, time, lot_id }){
			
				switch(parseInt(code))
				{
					case 0:
						const iJotId = parseInt(jot_id);
						const sTime = time || msgTime.toISOString();
						if (iJotId)
						{
							if (typeof lot_id !== 'undefined') {
								if (typeof header !== 'undefined')
									$(_this.sJotsBlock)
										.find('.bx-db-header')
										.replaceWith(header);

								_this.updateLotSettings({ lot: lot_id });
								$(_this.sFriendsList).remove();
							}

							if (typeof tmp_id !== 'undefined') {
								$('[data-tmp="' + tmp_id + '"]', _this.sTalkList)
									.attr('data-id', jot_id)
									.find('time')
									.html('')
									.attr('datetime', sTime)
									.closest(_this.sJot)
									.bxTime(undefined, true)
									.linkify();

								$(_this.sTalkList).addTimeIntervals();
							}
									
							if (oParams.files.length || typeof oParams.giphy !== 'undefined') {
								_this.attacheFiles(iJotId);
							}
							
							if (!_this.isBlockVersion())
								_this.upLotsPosition(_this.oSettings);
						}

						if (!_this.iAttachmentUpdate)
							_this.broadcastMessage({
								addon: {
									jot_id: jot_id
								}
							});
						break;
					case 1:
						if (message) {
							bx_alert(message);
							$(`[data-tmp="${oParams.tmp_id}"]`, _this.sTalkList).remove();
						}
						else
							window.location.reload();
						break;
					default:						
						bx_alert(message);
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
		const list = [];
		
		if ($(this.sUserSelector).length){
			$(this.sUserSelector).each(function(){
				list.push($(this).val());
			});
		} 
		else if ($(this.sUserTopInfo).length){
			const iUserId = parseInt($(this.sUserTopInfo).data('user-id'));
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
					if (typeof(_this.aJitisActiveUsers[lot]) !== 'undefined') {
						if ($(`div[data-conferance='${lot}']`).length && _this.aJitisActiveUsers[lot].owner === user_id) {
							_this.onCloseCallPopup($(`div[data-conferance='${lot}']`), lot);
						}
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
				case 'join':
					_this.stopActiveSound();
				break;
		}
	};
	
	/**
	* Move lot's brief to the top of the left side when new message received or just update brief message
	*@param object oObject lot's settings
	*/
	oMessenger.prototype.upLotsPosition = function(oObject, bSilentMode = false){
		const _this = this,
			{ lot , addon, lot_id } = oObject,
			oLot = $('div[data-lot=' + lot + ']');

		let	oNewLot = undefined;
		if (addon === 'remove_lot' && oLot.length) {
			oLot.fadeOut().remove();
			if ($(_this.sLotsListSelector).length > 0)
			{
				$(_this.sLotsListSelector).first().click();
				if (_this.oJotWindowBuilder !== undefined)
					_this.oJotWindowBuilder.changeColumn('right');
			}
			else
				_this.createLot();

			return;
		}

		if (typeof addon === 'string' && addon !== 'delete')
			return;

		$.get('modules/?r=messenger/update_lot_brief', { lot_id: lot },
				function({ html, code })
				{
					if (+code)
						return ;

					const sHtml = html.replace(new RegExp(_this.sJotUrl + '\\d+', 'i'), _t('_bx_messenger_repost_message'));
					oNewLot = $(sHtml).css('display', 'flex');
												
					if (!oLot.is(':first-child'))
					{
							const sFunc = () =>	{

													$(_this.sLotsListBlock)
														.prepend($(oNewLot)
														.bxTime()
														.fadeIn('slow'));

													_this.updatePageIcon();
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
						
						if (typeof addon === 'undefined' || typeof addon === 'object') /* only for new messages */
						{
							if (!bSilentMode)
								$(_this).trigger(jQuery.Event('message'));

							if (_this.isActiveLot(lot) && !(_this.isMobile() && !_oMessenger.oJotWindowBuilder.isHistoryColActive()))
								_this.selectLotEmit($(oNewLot));
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
	oMessenger.prototype.showTyping = function({ name, lot }) {
		const _this = this,
			sName = name ? name.toLowerCase() : '';
	
		if (this.isActiveLot(lot))
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
		const _this = this;
			
		$(this.sInfoArea).fadeIn();
		$(this.sTypingArea).parent().hide();
		$(this.sConnectingArea).show();	
		$(' > span', _this.sConnectingArea).html('');
		
		this.blockSendMessages(true);
		clearInterval(this.iTimer);	

		this.iTimer = setInterval(function(){
			let sHTML = $(' > span', _this.sConnectingArea).html();
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
		return iId && +this.oSettings.lot === +iId;
	}

	/**
	* Search for lot by participants list
	*@param int iId profile id 
	*@param function fCallback callback function 
	*/	
	oMessenger.prototype.findLotByParticipantsList = function(fCallback){
		const _this = this;
		$.post('modules/?r=messenger/find_lot', { participants: this.getParticipantsList() },
			function(oData){
				if (oData.lot) {
					_this.oJotWindowBuilder.resizeWindow();
					_this.loadJotsForLot(parseInt(oData.lot), fCallback);
					_this.updateLotSettings({
						lot: oData.lot
					});
				}
				else {
					$(_this.sJotsBlock)
						.find(_this.sTalkList)
						.html('');

					_this.oSettings.lot = 0;
					_this.blockSendMessages(true);
				}
			}, 
		'json');
	}

	oMessenger.prototype.isViewInBottomPosition = function(){
		const iHeight = $(this.sTalkBlock).prop('scrollHeight'),
			  iClient = $(this.sTalkBlock).prop('clientHeight'),
			  _this = this;

		if (iHeight > iClient){
			const iCurrentScrollHeight = iHeight - iClient;
			const iCurrentScrollPos = $(_this.sTalkBlock).scrollTop();

			if ((iCurrentScrollHeight - iCurrentScrollPos) > iClient)
				return false;
		}

		return true;
	}
	/**
	* Correct scroll position in history area depends on loaded messages (old history or new just received)
	*@param string sPosition position name
	*@param string sEff name of the effect for load 
	*@param object oObject any history item near which to place the scroll 
	*@param function fCallback executes when scrolling complete
	*/
	oMessenger.prototype.updateScrollPosition = function(sPosition, sEff, mixedObject, fCallback){
		const sEffect = sEff,
			  iHeight = $(this.sTalkBlock).prop('scrollHeight');

		let iPosition = 0;
		switch(sPosition){
			case 'top':
					const { pos } = mixedObject || {};
					iPosition = pos ? pos : 0;
					break;
			case 'bottom':
					iPosition = iHeight;
					break;
			case 'position':
					iPosition = typeof mixedObject !== 'undefined' ? mixedObject.position().top : 0;
					break;
			case 'center':
					iPosition = mixedObject.position().top - $(this.sTalkBlock).prop('clientHeight')/2;
					break;
			case 'freeze':
					iPosition = $(this.sTalkBlock).scrollTop();
					break;
		}

		if (sEffect !== 'slow') {
			$(this.sTalkBlock).scrollTop(iPosition);
			if (typeof fCallback === 'function')
				fCallback ();
		}
		else
			$(this.sTalkBlock).animate({
											scrollTop: iPosition,
										 }, sEffect === 'slow' ? _this.iScrollDownSpeed : 0,
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

	oMessenger.prototype.getDateSeparator = function(sTime){
		let sFormat = 'dddd Do';

		if (!moment().isSame(sTime, 'month'))
			sFormat += ', MMMM ';

		if (!moment().isSame(sTime, 'year'))
			sFormat += 'YYYY';

		return this.sDateIntervalsTemplate.replace(/__date__/g, moment(sTime).lang(glBxTimeLang).format(sFormat));
	}

	$.fn.addTimeIntervals = function(){
		const oJots = $(`${_oMessenger.sJot} time`, this);
		let sDate = oJots.first().attr('datetime');

		if (!oJots.length)
			return this;

		const oFirstItem = oJots.first().closest(_oMessenger.sJot);
		if (!oFirstItem.prev().hasClass(_oMessenger.sDateIntervalsSelector.substr(1)))
			oFirstItem.before(_oMessenger.getDateSeparator(sDate));

		oJots.each(function(){
			const sTime = $(this).attr('datetime');
			if (!moment(sTime).isSame(sDate, 'day')){
				sDate = sTime;
				const oItem = $(this).closest(_oMessenger.sJot);
				if (!oItem.prev().hasClass(_oMessenger.sDateIntervalsSelector.substr(1)))
					oItem.before(_oMessenger.getDateSeparator(sTime));
			}
		});

		_oMessenger.updateDateList();
		return this;
	}

	/**
	* Update history area, occurs when new messages are received(move scroll to the very bottom) or member loads the history(move scroll to the very top)
	*@param object oAction info about an action
	*/
	oMessenger.prototype.updateJots = function(oAction, bSilentMode = false){
		const _this = this;
		const { addon, position, action, last_viewed_jot, callback } = oAction;

		let sAction = typeof addon === 'string' ? addon : (action !== 'msg' ? action : 'new'),
			iRequestJot = 0,
			iJotId = 0;

		const sPosition = position || (sAction === 'new' ? 'bottom' : 'position'),
			oObjects = $(this.sTalkListJotSelector);

		if ((sAction === 'new' || sAction === 'prev') && _this.iPanding)
			return;

		switch(sAction)
		{
			case 'check_viewed':
			case 'reaction':
			case 'delete':
			case 'edit':
			case 'vc':
					iJotId = oAction.jot_id || 0;
					break;
			case 'clear':
				  return $(_this.sTalkList).html('');
			case 'prev':
				iJotId = oObjects
					.first()
					.data('id');

				_this.iPanding = true;
				break;
			case 'new':
				iRequestJot = (typeof addon === 'object' && typeof addon.jot_id !== 'undefined' ? addon.jot_id : 0);
				if (!$(_this.sTalkListJotSelector).length)
					sAction = 'all';
			default:
				iJotId = oObjects
					.last()
					.data('id');

				_this.iPanding = true;
				break;
		}

		const iLotId = _this.oSettings.lot; // additional check for case when ajax request is not finished yet but another talk is selected

		if (sAction === 'prev')
			bx_loading($(`[data-id="${iJotId}"]${_this.sJot}`), true);

		$.post('modules/?r=messenger/update',
		{
			url: this.oSettings.url,
			jot: iJotId,
			lot: this.oSettings.lot,
			load: sAction,
			req_jot: iRequestJot,
			focus: +((_this.isMobile() && !_oMessenger.oJotWindowBuilder.isHistoryColActive()) ? false : document.hasFocus()),
			last_viewed_jot
		},
		function({ html, unread_jots, code, last_unread_jot, allow_attach, remove_separator })
		{
			bx_loading($(`[data-id="${iJotId}"]${_this.sJot}`), false);
			const oList = $(_this.sTalkList);

			_this.iPanding = false;
			if (iLotId !== _this.oSettings.lot)
					return ;

			if (!parseInt(code))
			{
				if (iJotId === undefined)
						oList.html('');

					switch(sAction)
					{
						case 'all':
						case 'new':
								_this.updateCounters(unread_jots, true);
								if (last_unread_jot && (!_this.iLastUnreadJot || _this.iLastUnreadJot < last_unread_jot))
									_this.iLastUnreadJot = last_unread_jot;

								_this.bAllowAttachMessages = allow_attach;
								if (!html.length)
									return ;

								$(oList)
								.append(
										$(html)
										.filter(_this.sJot)
										.each(
												function(){
															if ($('div[data-id="' + $(this).data('id') + '"]', oList).length)
																$(this).remove();

															$(`${_this.sJotMessageViews} img`, this)
																.each(function(){
																	$(`${_this.sJot} ${_this.sJotMessageViews} img[data-viewer-id="${$(this).data('viewer-id')}"]`).remove()
																})
																.end()
																.closest(_this.sJotMessageViews)
																.fadeIn();
												})
										.waitForImages(() => _this.updateScrollPosition(sPosition ? sPosition : 'bottom', 'fast', oObjects.last()))
									)
									.addTimeIntervals();


								if ((_this.isBlockVersion() || (_this.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive())) && !bSilentMode)  /* play sound for jots only on mobile devices when chat area is active */
									$(_this).trigger(jQuery.Event('message'));

								if ($(_this.sTalkListJotSelector).length > _this.iMaxHistory && iRequestJot)
								{
									let iCountToRemove = $(_this.sTalkListJotSelector).length - _this.iMaxHistory;
									while(iCountToRemove-- > 0){
										$(_this.sTalkListJotSelector).first().remove();
									}
								}

								break;
						case 'prev':
							if (remove_separator && $('>', oList).first().hasClass(_this.sDateIntervalsSelector.substr(1))) {
								$('>', oList).first().remove();
							}

							oList
								.prepend($(html)
									.filter(_this.sJot)
									.each(function(){
										$(`${_this.sJotMessageViews} img`, this)
											.each(function(){
												if ($(`${_this.sJot} ${_this.sJotMessageViews} img[data-viewer-id="${$(this).data('viewer-id')}"]`).length)
													$(this).remove();
											})
											.end()
											.closest(_this.sJotMessageViews)
											.fadeIn();
									}))
								.addTimeIntervals()
								.waitForImages(oParent => {
									const iId = oObjects.first().data('id');
									if (+iId)
										setTimeout(() => {
											const iTop = $(`[data-id="${iId}"]${_this.sJot}`, oParent).position().top || 0;
											if (iTop)
												_this.updateScrollPosition('top', 'fast', {pos: iTop});
										}, 0);
								});

							break;
						case 'edit':
						case 'vc':
							if (!$('div[data-id="' + iJotId + '"]').length)
								oList
									.append(html);
							else
							$('div[data-id="' + iJotId + '"] ' + _this.sJotMessage, oList)
									.html(html)
									.parent()
									.bxTime();// don't update attachment for message and don't broadcast as new message
							return;
						case 'delete':
								const onRemove = function(){
										if (!$(this).next(_this.sJot).length)
												$(this).prev(_this.sDateIntervalsSelector).remove();

											$(this).remove();
												_this.updateScrollPosition('bottom');
										};
								if (html.length)
								{
										$('div[data-id="' + iJotId + '"] ' + _this.sJotMessage, oList)
										.html(html)
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
						    const aUsers = $(html).filter('img');

							if (aUsers.length) {
    						    aUsers.each(function(){
									let iProfileId = $(this).data('viewer-id');
									$(`${_this.sJot} ${_this.sJotMessageViews} img[data-viewer-id="${iProfileId}"]`).remove();
								});

								return $(`div[data-id="${iJotId}"] ${_this.sJotMessageViews}`, oList)
									.html(html)
									.fadeIn();
							}
							break;
						case 'reaction':
								let iOriginalCount = 0;

								$(html)
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
									.prepend(html);

								if (html.length)
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

				if (typeof callback === 'function')
					callback();
			}

			if (sAction === 'prev')
				bx_loading($(_this.sTalkBlock), false);

		}, 'json');
	};
		
	/**
	* Init user selector when create or edit participants list of the lot
	*@param boolean bMode if used for edit or to create new lot
	*/
	oMessenger.prototype.initUsersSelector = function(bMode){
		const _this = this,
				onSelectFunc = function(fCallback)
				{
					if (bMode !== 'edit')
						_this.findLotByParticipantsList(fCallback);

					if (_this.oJotWindowBuilder !== undefined)
							_this.oJotWindowBuilder.updateColumnSize();
				};

				$(_this.sUserSelectorBlock + ' .ui.search')
						.search({
									clearable: true,
									duration: 0,
									searchDelay: 0,
									type : 'category',
									boundary: $(_this.sJotsBlock),
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
									  categories : 'results',
									  categoryResults : 'results',
									  categoryName    : 'name',
									  results : 'results',
									  title   : 'value',
									  image	  : 'icon',
									  name	  : 'name'
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
					$(_this.sUserSelectorInput)
						.focus();
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
		this.oEditor.addToCurrentPosition(oEmoji.native);
	};

	oMessenger.prototype.isUnaMobileApp = function () {
		return  'undefined' !== typeof(window.ReactNativeWebView) &&
				'undefined' !== typeof(window.glBxNexusApp) &&
				parseInt(window.glBxNexusApp.ver.split('.').join('')) >= 140;
	};

    /**
     * Run Jitsi video chat
     *@param string sId of the image
     */
    oMessenger.prototype.startVideoCall = function(oEl, iLotId, sRoom, oOptions = {}){
        const _this = this,

			fDesktopCall = () => {
				 let oParams = Object.assign({}, oOptions, !iLotId && { url : encodeURIComponent(_this.oSettings.url), title: _this.oSettings.title });
				 if (typeof oParams.callback !== 'undefined')
					 delete oParams['callback'];

				if (oEl)
					bx_loading_btn($(oEl), true);

				 $(window).dolPopupAjax({
					 url: bx_append_url_params(`modules/?r=messenger/get_jitsi_conference_form/${iLotId}`, oParams),
					 id: {
						 force: true,
						 value: _this.sJitsiVideo.substr(1)
					 },
					 fog: false,
					 removeOnClose: true,
					 closeOnOuterClick: false,
					 onBeforeShow: () => oOptions && typeof oOptions.callback === 'function' && oOptions.callback(),
					 onShow: () => oEl && bx_loading_btn($(oEl), false),
				 });
			 },

			 fMobileCall = () => {
				 if ('undefined' !== typeof(window.ReactNativeWebView)) {

						 if (typeof window.glBxVideoCallJoined === 'undefined') {
							 window.glBxVideoCallJoined = [];
						 }

						 window.glBxVideoCallJoined.push(function (e) {
							 if (oEl)
								 bx_loading_btn($(oEl), true);

							 if (oOptions && typeof oOptions.callback === 'function')
								 oOptions.callback();
							 
							 $.get('modules/?r=messenger/create_jitsi_video_conference/', { lot_id: iLotId }, function (oData) {
									 const { message, opened, code, jot_id } = oData;
									 bx_loading_btn($(oEl), false);

									 if (+code === 1) {
										 bx_alert(message);
										 return;
									 }

									 if (typeof opened !== 'undefined' && Array.isArray(opened))
										 if (Array.isArray(opened))
											 opened.map(jot_id => _this.updateJots({
												 action: 'vc',
												 jot_id
											 }));

									 if (iLotId) {
										 const oInfo = { type: 'vc', vc: 'start' };

										 if (jot_id && !oData.new) {
											 oInfo.jot_id = jot_id;
											 oInfo.vc = 'join';
										 }
										  else
											 _this.playSound('call', true);

										 _this.broadcastMessage(oInfo);
										 _this.updateJots(oInfo);
									 }

									 if (typeof window.glBxVideoCallTerminated === 'undefined') {
										 window.glBxVideoCallTerminated = [];
									 }

									 window.glBxVideoCallTerminated.push(function (e) {
										 	 _this.stopActiveSound();
											 $.get('modules/?r=messenger/stop_jvc/', {lot_id: iLotId}, (oData) => {
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
								 },
								 'json');
						 });

						 const oVideoParams = { uri: ( _this.sJitsiServerUrl ? `${_this.sJitsiServerUrl}/${sRoom}` : sRoom ) };
						 if (typeof oOptions.startAudioOnly !== 'undefined' && oOptions.startAudioOnly === true)
							 oVideoParams['audio'] = true;

						 // call mobile video call
						 if (typeof bx_mobile_apps_post_message === 'function')
							 bx_mobile_apps_post_message({ video_call_start: oVideoParams });
				 };

			return false;
		};

		if (_this.isMobileDevice() && $(_this.sJitsiButton).length){
			if (!_this.isUnaMobileApp())
				bx_confirm(_t('_bx_messenger_jitsi_mobile_warning'), fDesktopCall, fMobileCall);
			else
				fMobileCall();
		}
		else
			fDesktopCall();
    };

	oMessenger.prototype.onCloseCallPopup = function (oEl, iLotId, sType = 'break', bClose = true) {
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

		if (bClose) {
			if (iJot)
				this.updateJots(Object.assign(oParams, {
					action: 'vc',
					jot_id: iJot
				}));

			if (typeof (this.aJitisActiveUsers[iLotId]) !== 'undefined')
				delete this.aJitisActiveUsers[iLotId];
		}

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

				  	  _this.stopActiveSound();
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

	oMessenger.prototype.setPositionOnSelectedJot = function(fCallback){
		const _this = this;
		if ($(_this.sSelectedJot, _this.sTalkList).length)
			_this.updateScrollPosition('center', 'fast', $(_this.sSelectedJot, _this.sTalkList),
				function(){
					$(_this.sScrollArea).fadeIn('slow', () => {
						if (typeof fCallback === 'function')
							fCallback();
					});
				});
		else
			_this.updateScrollPosition('bottom', undefined, undefined, () => {
				if (typeof fCallback === 'function')
					fCallback();
			});
	}

	/**
	 * Init settings, occurs when member opens the main messenger page
	 * @param fCallback
	 */
	oMessenger.prototype.initMessengerPage = function(fCallback) {
		const _this = this;
		_this.oJotWindowBuilder = window.oJotWindowBuilder;
		if (typeof oMessengerMemberStatus !== 'undefined') {
			oMessengerMemberStatus.init(function (iStatus) {
				_this.iStatus = iStatus;
				if (typeof _this.oRTWSF !== "undefined")
					_this.oRTWSF.updateStatus({
						user_id: _this.oSettings.user_id,
						status: iStatus,
					});
			});
		}

		if (_this.oJotWindowBuilder !== undefined) {
			_this.oJotWindowBuilder.setDirection(_this.direction);
			$(window).on('load resize', function (e) {
				if (e.type !== 'load')
					_this.updateSendAreaButtons();

				_this.oJotWindowBuilder.resizeWindow(() => {
					_this.selectLotEmit($(`[data-lot="${_this.oSettings.lot}"]${_this.sLotSelector}`));
					$(_this.sTalkList).waitForImages(() => _this.setPositionOnSelectedJot(fCallback));
				});
			});

			_this.oJotWindowBuilder.loadRightColumn = function() {
				if ($(_this.sLotsListSelector).length > 0)
					$(_this.sLotsListSelector).first().click();
				else
					_this.createLot();
			};
		} else {
			console.log('Page Builder was not initialized');
		}

		_this.updatePageIcon();
	};

	/**
	 * Loads form to popup
	 *@param string sUrl link, if not specify default one will be used
	 *@param function fCallback callback function,  executes on window show
	 */

	oMessenger.prototype.showPopForm = function (sMethod, fCallback) {
		const sUrl = `modules/?r=messenger/${sMethod}`,
			sText = _oMessenger.oEditor.getText();

		$(window).dolPopupAjax({
			url: sUrl,
			id: { force: true, value: 'bx-messenger-popup'},
			onShow: function () {
				setTimeout(() => typeof fCallback === 'function' && fCallback(), 100);
			},
			closeElement: true,
			closeOnOuterClick: false,
			removeOnClose: true,
			onHide: function () {
				_oMessenger.updateScrollPosition('bottom');
			}
		});
	}
	oMessenger.prototype.initGiphy = function() {
		let iTotal = 0;
		let iScrollPosition = 0;
		const _this = this,
			oContainer = $(_this.sGiphyItems),
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
				$('div.search', _this.sGiphyBlock).addClass('loading');
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

						$('div.search', _this.sGiphyBlock).removeClass('loading');
						if (typeof fCallback === 'function')
							fCallback(sType, sValue);
					},
					'json');
			};

		if ($(_this.sGiphMain).css('visibility') === 'visible') {
			let iTimer = 0;
			$('input', _this.sGiphyBlock).keypress(function (e) {
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
			if (_oMessenger != null)
				return true;

			_oMessenger = new oMessenger(oOptions);
			if (createjs) {
				createjs.Sound.registerSound(_oMessenger.incomingMessage, 'incomingMessage');
				createjs.Sound.registerSound(_oMessenger.reaction, 'reaction');
				createjs.Sound.registerSound(_oMessenger.call, 'call');
			}

			/* Init users Jot template  begin */
			_oMessenger.loadMembersTemplate();
			/* Init users Jot template  end */

			/* Init sockets settings begin*/
			if (typeof _oMessenger.oRTWSF !== 'undefined' && _oMessenger.oRTWSF.isInitialized()) {

				$(window).on('beforeunload', function () {
					if (_oMessenger.oRTWSF !== undefined)
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

			/* Init sockets settings end */

			// init browser storage
			if (typeof oMessengerStorage !== 'undefined' && !_oMessenger.oStorage) {
				_oMessenger.oStorage = new oMessengerStorage();
			}

			// init tex area
			_oMessenger.initTextArea();
			const oInitParams = {
									lot: oOptions.lot,
									jot: oOptions.jot_id,
									last_unread_jot: oOptions.last_unread_jot,
									unread_jots: oOptions.unread_jots,
									allow_attach: oOptions.allow_attach
								};

			if (!_oMessenger.isBlockVersion())
				_oMessenger.initMessengerPage();
			else
				_oMessenger.setPositionOnSelectedJot();

			_oMessenger.updateLotSettings(oInitParams);
			_oMessenger.initScrollArea();

			_oMessenger.checkNotFinishedTalks();
			$(_oMessenger.sTalkBlock).addTimeIntervals();

			// attach on ESC button return from create talk area
			$(document).on('keydown', function(e){
				const { keyCode } = e;
				if (keyCode === 27 && $(e.target).prop('id') === _oMessenger.sUserSelectorInput.substr(1)) {
					const oSelector = $(`${_oMessenger.sLotsListSelector}.active`);
					if (oSelector.length)
						oSelector.click();
					else
						$(_oMessenger.sLotsListSelector).first().click();
				}
			});

			if (!oOptions.lot && !_this.iSelectedPersonToTalk)
				_oMessenger.initUsersSelector();

			$(window).on('focus', () => {
				_oMessenger.updatePageIcon();
				_oMessenger.broadcastView();
			});

			if ((+oOptions.selected_profile || +oOptions.jot_id) && _oMessenger.oJotWindowBuilder && _oMessenger.isMobile())
				_oMessenger.oJotWindowBuilder.changeColumn('right');
		},

		/**
		 * Init Lot settings only (occurs when member selects any lot from lots list)
		 *@param object oOptions options
		 */
		initLotSettings: function (oOptions) {
			_oMessenger.initLotSettings(oOptions);
		},

		initTextArea: function () {
			_oMessenger.initTextArea();
		},
		loadTalk: function (iLotId) {
			if (~_oMessenger.aLoadingRequestsPool.indexOf(iLotId))
				return;

			const iLength = _oMessenger.aLoadingRequestsPool.length;
			const fLoading = () => 	{
				const lot = _oMessenger.aLoadingRequestsPool[0];

				if (+lot)
					_oMessenger
						.loadTalk(lot, undefined, _oMessenger.isMobile() && _oMessenger.oJotWindowBuilder.isHistoryColActive())
						.done(() =>
						{
							if (typeof _oMessenger.oHistory.pushState === 'function') {
								_oMessenger.oHistory.pushState({ lot : iLotId }, null);
							}

							if (_oMessenger.aLoadingRequestsPool.length){
								if (_oMessenger.aLoadingRequestsPool.length > 1)
									_oMessenger.aLoadingRequestsPool = _oMessenger.aLoadingRequestsPool.slice(-1);
								else
									_oMessenger.aLoadingRequestsPool.shift();

								fLoading();
							}
					    });
			}

			_oMessenger.selectLotEmit($(`[data-lot="${iLotId}"]${_oMessenger.sLotSelector}`));
			_oMessenger.aLoadingRequestsPool.push(iLotId);

			if (!iLength)
				fLoading();

			return this;
		},
		onScrollDown: function () {
			const { iUnreadJotsNumber, iMaxHistory, iSelectedJot, sUnreadJotsCounter, oSettings: { lot } } = _oMessenger;
			if (iMaxHistory >= iUnreadJotsNumber && !iSelectedJot)
				_oMessenger.updateScrollPosition('bottom', 'fast');
			else
			{
				_oMessenger.loadTalk(lot, undefined, undefined, undefined, true);
				$(sUnreadJotsCounter)
					.text('')
					.hide();
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
		onClearLot: function (iLotId) {
			_oMessenger.clearLot(iLotId);
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
				if (!_oMessenger.isBlockVersion())
					_oMessenger.upLotsPosition(oData, bSilent);

			} catch (e) {
				console.log('Lot list message update error', e);
			}

			try {
				if (+oData.lot === +_oMessenger.oSettings.lot)
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
			const { addon } = oData;

			if ( addon && typeof addon.jot_id !== 'undefined')
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
				sText = _oMessenger.oEditor.getText();

			$(window).dolPopupAjax({
				url: sUrl,
				id: { force: true, value: _oMessenger.sAddFilesForm.substr(1) },
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
		initJitsi: function(oJitsi, bNew = false, bChatSync = false){
			_oMessenger.oJitsi = oJitsi;

			if (oJitsi._lotId) {
				const oInfo = { type: 'vc', vc: 'start' };

				if (oJitsi._jotId && !bNew) {
					oInfo.jot_id = oJitsi._jotId;
					oInfo.vc = 'join';
				} else {
					_oMessenger.playSound('call', true);
					_oMessenger.aJitisActiveUsers[oJitsi._lotId] = {owner: _oMessenger.oSettings.user_id, got: 0};
				}

				_oMessenger.broadcastMessage(oInfo);
				_oMessenger.updateJots({
					action: 'msg'
				});

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
							_oMessenger.stopActiveSound();
							
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

		onJitsiClose:(oElement, iLotId) => {
			_oMessenger.closeJitsi(oElement, iLotId);
		},

		/**
		 * Run Jitsi video chat
		 *@param string sId of the image
		*/

		onStartVideoCall: function(oEl, iLotId, sRoom){
            _oMessenger.startVideoCall(oEl, iLotId, sRoom);
		},

        getCall: function (oEl, iLotId, sRoom, bAudioOnly = false){
			if (!iLotId)
				return ;

			if (!_oMessenger.isActiveLot(iLotId))
				_oMessenger.loadTalk(iLotId);

			_oMessenger.startVideoCall(undefined, iLotId, sRoom,{
				startAudioOnly: +bAudioOnly,
				callback : () => {
					_oMessenger.onCloseCallPopup(oEl, iLotId, 'get_call', false);
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
		updateAttachmentArea: function(bCanHide){
			return _oMessenger.updateSendArea(bCanHide);
		},
		initUploader: oUploader => _oMessenger.oFilesUploader = oUploader
	}
})(jQuery);

/** @} */