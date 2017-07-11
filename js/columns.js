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

var oJotWindowBuilder = (function(){
	var _oPrivate = {
				sLeftAreaName: '.bx-messanger-items-list',
				sLotSelector: '.bx-messenger-jots-snip',
				sRightAreaName: '.bx-messenger-main-block', //left column of the body without header
				sLeftTopBlockArea: '#bx-messangger-block-head',
				sBothColumnsParent: '.bx-layout-row',
				sInfoUsersArea: '.bx-messenger-top-user-info',
				sBlockHeaderArea: '.bx-messenger-block > .bx-db-header',
				sToolbar: '#bx-toolbar',
				oLeftCol: null,
				oRightCol: null,
				iLeftSize: '30%',
				iRightSize: '70%',
				sActiveType:'both',
				iMainAreaHeight:null, //area of the body without header with both columns
				iLeftAreaHeight:null, //left column header height
				iRightAreaHeight:null, //right column header height
				iResizeTimeout:null,
				
				updateLeftHeight:function(){					
					this.iLeftAreaHeight = $(this.sLeftTopBlockArea).outerHeight();
					$(this.sLeftAreaName).height(this.iMainAreaHeight - this.iLeftAreaHeight);
				},
				
				updateRightHeight:function(){
						this.iRightAreaHeight = $(this.sInfoUsersArea).length ? $(this.sInfoUsersArea).outerHeight() : $(this.sBlockHeaderArea).outerHeight();	

						if (this.iRightAreaHeight == null) return ;
					
						$(this.sRightAreaName).height(this.iMainAreaHeight - this.iRightAreaHeight);
				},
					
				init:function(){
						var iParent = $(this.sBothColumnsParent).width();
						
						if (this.oLeftCol != null || this.oRightCol != null) return ;
						
						this.oLeftCol = $(this.sBothColumnsParent + ' > div').first();
						this.oRightCol = $(this.sBothColumnsParent + ' > div').last();
						
						this.iLeftSize = this.oLeftCol.outerWidth()*100/iParent + '%' || this.iLeftSize;
						this.iRightSize = this.oRightCol.outerWidth()*100/iParent + '%' || this.iRightSize;	
				},
				isMobile:function(){
						return $(window).width() <= 720;						
					},
			
				changeColumn:function(sSide){
						this.init();
						
						if (this.isMobile()){
							if (sSide)
								this.sActiveType = sSide;
							else	
								this.sActiveType = this.sActiveType == 'left' ? 'right' : 'left';	
						}	
						else
							this.sActiveType = 'both';
					
						this.resizeColumns();	
					},
				activateLeft:function(){					
						this.oRightCol.hide();
						this.oLeftCol.width('100%').fadeIn();
						this.updateLeftHeight();						
					},
				activateRight:function(){
						this.oLeftCol.hide();
						this.oRightCol.width('100%').fadeIn();
						this.updateRightHeight();											
					},
					
				activateBoth:function(){
						if (!parseInt(this.iRightSize) || !parseInt(this.iLeftSize) || (parseInt(this.iRightSize) == 100 && parseInt(this.iRightSize) == 100)){
							this.iLeftSize = '30%';
							this.iRightSize = '70%';
							if (!parseInt(this.iRightSize))
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
								this.sActiveType = this.sActiveType == 'both' ? 'left' : this.sActiveType;
						 else 
							 this.sActiveType = 'both';

						this.iMainAreaHeight = $(window).height() - $(this.sToolbar).outerHeight();						
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
			resizeWindow:function(){
				 clearTimeout(_oPrivate.iResizeTimeout);
				_oPrivate.iResizeTimeout = setTimeout(
														function()
														{
															_oPrivate.onResizeWindow()
														}, 300);
			},
			changeColumn:function(sSide){
				_oPrivate.changeColumn(sSide);
			},
			loadRightColumn:function(){
				console.log('Occurs when resizing window and right column is empty. Overwrite it in class owner.');
			},			
			isMobile:function(){
				return _oPrivate.isMobile();
			}
		}
})();

/** @} */
