window.oMessengerPublicLib = (function($) {
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
                    closeElement: true,
                    closeOnOuterClick: false,
                    removeOnClose: true,
                }, oOptions);

                $(window).dolPopupAjax(oPopupOptions);
            }
        }
})(jQuery);