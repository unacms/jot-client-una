window.oMUtils = (function($) {
    let mixedMedia = false,
          sScreenMode = false,
          fUpdateScreenMode = () => {
              mixedMedia = $("html").attr('class').match(/.*bx-media-([^\d\s]+)/i);
              sScreenMode = !(mixedMedia === null || !Array.isArray(mixedMedia) || typeof mixedMedia[1] === 'undefined') && mixedMedia[1];
          };

    $(document).ready(fUpdateScreenMode);

    return {
            addBubble: (oData) => {
             const { selector, count_new, code } = oData;
             const sSelectorAddon = '.bx-menu-item-addon';

                //--- Update Child Menu Item
                if( selector )  {
                    const oMenuItem = $(selector);
                    const oMenuItemAddon = oMenuItem.find(sSelectorAddon);

                    if(oMenuItemAddon.length > 0)
                        oMenuItemAddon.html(count_new);
                    else
                        oMenuItem.append(code.replace('{count}', count_new));

                    if(parseInt(count_new) > 0)
                        oMenuItemAddon.show();
                    else
                        oMenuItemAddon.hide();
                }
            },
            showConferenceWindow: (sUrl, oOptions = {}) => {
                const oPopupOptions = Object.assign({
                    id: { force: true, value: 'bx-messenger-vc-call' },
                    url: sUrl,
                    onHide: () => $(document).trigger($.Event('hideConferenceWindow')),
                    onShow: () => $(document).trigger($.Event('showConferenceWindow')),
                    closeOnOuterClick: true,
                    removeOnClose: true,
                }, oOptions);

                $(window).dolPopupAjax(oPopupOptions);
            },
            onSaveGroup: (oElement) => {
                const oForm = $(oElement).closest('form'),
                        iGroupId = $("[name='id']", oForm).val(),
                        sName = $("[name='name']", oForm).val(),
                        sDesc = $("[name='desc']", oForm).val(),
                        sPrivacy = $("[name='allow_view_to']", oForm).val();

                $.post('modules/?r=messenger/save_group', { id: iGroupId, name: sName, desc: sDesc, allow_view_to: sPrivacy }, function(oData){
                    const { code } = oData;
                    if (code)
                        processJsonData(oData);
                    else
                    {
                      $(oElement).closest('.bx-popup-applied:visible').dolPopupHide();
                        if (!code) {
                            bx_loading($('#bx-messenger-left-nav-menu'), true);
                            $.post('modules/?r=messenger/load_groups_list', {}, function ({code, content}) {
                                if (content.length)
                                    $('#bx-messenger-left-nav-menu').replaceWith(content);
                                else
                                    bx_loading($('#bx-messenger-left-nav-menu'), false);
                            }, 'json');
                        }
                    }

                }, 'json');
            },
            onCreateGroup: (iGroupId) => $.post('modules/?r=messenger/get_create_group_form', { group: iGroupId }, (oData) => processJsonData(oData), 'json'),
            getBroadcastFields: () => {
                const { createConvoForm } = window.oMessengerSelectors.CREATE_TALK;
                const aFields = Object.create(null);
                $(createConvoForm).find('input:not([name="csrf_token"]),select,textarea').each(function(){
                        const sType = $(this).prop('type'),
                            sName = $(this).prop('name').replace(/\[\]/, '');

                        switch(sType) {
                            case "checkbox":
                                if ($(this).is(':checked')) {
                                    if (typeof aFields[sName] !== 'undefined')
                                        aFields[sName].push($(this).val());
                                    else
                                        aFields[sName] = [$(this).val()];
                                }
                                break;
                            case "text":
                            case "textarea":
                            case "hidden":
                                if ($(this).val().length && sName.length)
                                    aFields[sName] = $(this).val();
                                break;
                            default:
                                const sInput = $(this).prop('tagName').toLowerCase();
                                if (sInput === "select") {
                                    if ($(this).prop('multiple')){
                                        const aList = [];
                                        $(this).find('option:selected').each(function(){
                                            aList.push($(this).val());
                                        });

                                        aFields[sName] = aList;
                                    } else
                                        aFields[sName] = $(this).val();
                                }
                            }
                        });

                return aFields;
            },
            onCalculateProfiles: () => {
                const { selectedUsersArea, selectedUserElement } = window.oMessengerSelectors.CREATE_TALK,
                    aFields = window.oMUtils.getBroadcastFields();

                const aManuallList = $(selectedUserElement, selectedUsersArea).map(function(){
                    return $(this).data('id');
                }).get();

                if (Object.keys(aFields).length || aManuallList.length)
                    $.post('modules/?r=messenger/calculate_profiles', { data: aFields, manually: aManuallList }, (oData) => {
                        processJsonData(oData);
                    }, 'json');
            },
            toggleList: function(oObject){
                const oDropDown =$(oObject).next();
                return !oDropDown.is(':visible') ? oDropDown.fadeIn() : oDropDown.fadeOut();
            },
            getScreenMode: function() {
                fUpdateScreenMode();
                return sScreenMode;
            },
            isMobile: function(){
                return $(window).width() <= 768;
            },
            isMobileDevice: function(){
            return 	(
                /(android|bb\d+|meego|UNA).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino|android|ipad|playbook|silk/i.test(navigator.userAgent||navigator.vendor||window.opera)
                    ||
                /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test((navigator.userAgent||navigator.vendor||window.opera).substr(0,4))
                );
            },
            isUnaMobileApp: function(){
                return  'undefined' !== typeof(window.ReactNativeWebView) &&
                    'undefined' !== typeof(window.glBxNexusApp) &&
                    parseInt(window.glBxNexusApp.ver.split('.').join('')) >= 140;
            }
    }
})(jQuery);