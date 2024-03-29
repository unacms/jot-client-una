/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup	UnaModules
 * @{
 */
 
/**
 * Builder class
 * Adapt messenger main page for different devises
 */ 

;window.oJotWindowBuilder = (function(){
	const _oPrivate = {
				sLeftAreaName: '.bx-messenger-items-list',
				sLotSelector: '.bx-messenger-jots-snip',
				sRightAreaName: '.bx-messenger-main-block', //left column of the body without header
				sLeftTopBlockArea: '#bx-messenger-block-head',
				sBothColumnsParent: '.bx-layout-row',
				sBlockHeaderArea: '.bx-messenger-block > .bx-db-header',
				sPrevBlocks: '#bx-content-wrapper',
				oLeftCol: null,
				oRightCol: null,
				iLeftSize: '30%',
				iRightSize: '70%',
				sActiveType:'both',
				iMainAreaHeight:null, //area of the body without header with both columns
				iLeftAreaHeight:null, //left column header height
				iRightAreaHeight:null, //right column header height
				iResizeTimeout:null,
				sDirection: 'LTR', // by default left to right;
				isRTL: function(){
					return this.sDirection === 'RTL';
				},

				updateLeftHeight:function(){
					this.iLeftAreaHeight = $(this.sLeftTopBlockArea).outerHeight();
					$(this.sLeftAreaName).height(this.iMainAreaHeight - this.iLeftAreaHeight);
				},
				updateRightHeight: function(){
						this.iRightAreaHeight = $(this.sBlockHeaderArea).last().outerHeight();
						if (this.iRightAreaHeight == null)
							return;

						$(this.sRightAreaName).height(this.iMainAreaHeight - this.iRightAreaHeight);
				},
				init: function(){
						const iParent = $(this.sBothColumnsParent).outerWidth();
						
						if (!iParent || this.oLeftCol != null || this.oRightCol != null) return ;

						if (this.isRTL()) {
							this.oLeftCol = $(this.sBothColumnsParent + ' > div').last();
							this.oRightCol = $(this.sBothColumnsParent + ' > div').first();
						} else
						{
							this.oLeftCol = $(this.sBothColumnsParent + ' > div').first();
							this.oRightCol = $(this.sBothColumnsParent + ' > div').last();
						}
						const iLeftW = (this.oLeftCol.outerWidth()*100/iParent).toFixed(2);
						if (!iLeftW)
							return;
						
						this.iRightSize = (100 - iLeftW) + '%';
						this.iLeftSize = iLeftW + '%';
				},
				isMobile:function(){
					return $(window).width() <= 720;
				},
				changeColumn:function(sSide){
					this.init();
					if (this.isMobile())
					{
						if (sSide)
							this.sActiveType = sSide;
						else
							this.sActiveType = this.sActiveType === 'left' ? 'right' : 'left';
					}
					else
						this.sActiveType = 'both';
					
					this.resizeColumns();
				},
				activateLeft:function(){
						this.oRightCol.hide().width('0%');
						this.iRightSize = '0%';
						this.oLeftCol.width('100%').fadeIn();
						this.updateLeftHeight();
				},
				activateRight:function(){
						this.oLeftCol.hide().width('0%');
						this.iLeftSize = '0%';
						this.oRightCol.width('100%').fadeIn();
						this.updateRightHeight();
				},
				activateBoth:function(){
						if (parseInt(this.iRightSize) === 0 || parseInt(this.iRightSize) === 100)
						{
							this.iLeftSize = '30%';
							this.iRightSize = '70%';
							if (!parseInt(this.iRightAreaHeight))
									oJotWindowBuilder.loadRightColumn();
						}	
						
						this.oLeftCol.width(this.iLeftSize).fadeIn('slow');						
						this.oRightCol.width(this.iRightSize).fadeIn('slow');
											
						this.updateLeftHeight();
						this.updateRightHeight();
				},
				onResizeWindow:function(){
					this.init();
						if (this.isMobile())
							this.sActiveType = this.sActiveType === 'both' ? 'left' : this.sActiveType;
						else
							this.sActiveType = 'both';

					this.iMainAreaHeight = window.innerHeight;
					if ($(this.sPrevBlocks).length)
						this.iMainAreaHeight -= $(this.sPrevBlocks).offset().top;

					this.resizeColumns();
				},

				resizeColumns:function(){

						switch(this.sActiveType){
							case 'left' : this.activateLeft(); break;
							case 'right' : this.activateRight(); break;
							 default:
								this.activateBoth();	
						}					
					}
		};
		
	return {
			setDirection:function(sDirection){
				if (sDirection !== 'LTR')
					_oPrivate.sDirection = 'RTL';
			},
			resizeWindow:function(fCallback)
			{
				 clearTimeout(_oPrivate.iResizeTimeout);
				_oPrivate.iResizeTimeout = setTimeout(function(){
														_oPrivate.onResizeWindow();
														if (typeof fCallback === 'function')
															fCallback();
													 }, 300);
			},
			updateColumnSize:function()
			{
				_oPrivate.resizeColumns();
			},
			changeColumn:function(sSide, fCallback)
			{
				_oPrivate.changeColumn(sSide);
				if (typeof fCallback === 'function')
					fCallback();
			},
			isHistoryColActive:function()
			{
				return _oPrivate.sActiveType === 'both' || _oPrivate.sActiveType === 'right';
			},
			loadRightColumn:function()
			{
				console.log('Occurs when resizing window and right column is empty. Overwrite it in class owner.');
			},			
			isMobile:function()
			{
				return _oPrivate.isMobile();
			}
		}
})();

/** @} */
