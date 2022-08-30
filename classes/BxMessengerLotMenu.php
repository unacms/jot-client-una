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
 * View block menu.
 */
class BxMessengerLotMenu extends BxBaseModTextMenuView
{
    protected $_oModule;
    private $_sPopupTalOptions = '';
    private $_iProfileId = 0;
    public function __construct($aObject, $oTemplate = false)
    {
        $this-> MODULE = 'bx_messenger';
        $this->_oModule = BxDolModule::getInstance($this-> MODULE);
        $this->_iProfileId = bx_get_logged_profile_id();

        parent::__construct($aObject, $this->_oModule->_oTemplate);
    }

    public function _getMenuItem ($aMenu)
    {
        $CNF = &$this->_oModule->_oConfig->CNF;
        $oModule = &$this->_oModule;

        switch($aMenu['name']){
            case 'mute':
                    $bIsMuted = $oModule->_oDb->isMuted($this->_iContentId, $this->_iProfileId);
                    $aMenu['title'] = $bIsMuted ? _t('_bx_messenger_lots_menu_mute_info_on') : _t('_bx_messenger_lots_menu_mute_info_off');
                    $aMenu['icon'] = $bIsMuted ? $CNF['BELL_ICON_OFF'] : $CNF['BELL_ICON_ON'];
                    $aMenu['value'] = +$bIsMuted;
                break;
            case 'star':
                    $bIsFav = $oModule->_oDb->isStarred($this->_iContentId, $this->_iProfileId);
                    $aMenu['icon'] = $CNF['STAR_ICON'] . ((int)$bIsFav ? ' fill' : '');
                    $aMenu['title'] = !$bIsFav ? _t('_bx_messenger_lots_menu_star_on') : _t('_bx_messenger_lots_menu_star_off');
                    $aMenu['value'] = +$bIsFav;
                break;
            default:
                $aMenu['value'] = '';
        }

        return parent::_getMenuItem($aMenu);
    }

    public function getCode ()
    {
        return parent::getCode() . $this->_sPopupTalOptions;
    }

    public function setTemplateById ($iTemplateId)
    {
        if ($iTemplateId == BX_DB_MENU_TEMPLATE_TABS)
            $this->_aObject['template'] = 'menu_messenger_talk_header_hor.html';
        else
            $this->_aObject['template'] = 'menu_messenger_talk_header_ver.html';
    }

    public function isActive($sName){
        if (!isset($this->_aObject['menu_items']))
            $this->_aObject['menu_items'] = $this->getMenuItemsRaw ();

        return !empty($sName) && isset($this->_aObject['menu_items'][$sName]) && (int)$this->_aObject['menu_items'][$sName]['active'];
    }

    protected function _isVisible ($a)
    {
        if (!$this->_iProfileId)
            return false;

        $CNF = &$this->_oModule->_oConfig->CNF;
        $oModule = &$this->_oModule;
        if(!parent::_isVisible($a) || !$this->_iContentId)
            return false;

        $aLotInfo = $this->_oModule->_oDb->getLotInfoById($this->_iContentId);
        switch ($a['name']) {
            case 'settings':
                $sPopupMenuName = time();
                if ($this->_iContentId) {
                    $sPopupMenuName = "lot-info-menu-{$this->_iContentId}";
                    $this->addMarkers(array('lot_menu_id' => $sPopupMenuName));
                }

                $this->_sPopupTalOptions = BxTemplStudioFunctions::getInstance()->transBox($sPopupMenuName, $oModule->_oTemplate->getLotMenuCode($this->_iContentId, bx_get_logged_profile_id()), true);
                return true;

            case 'mute':
            case 'star':
                $this->addMarkers(array('id' => $this->_iContentId));
                return true;

            case 'video_call':
                if ($oModule->_oConfig->isJitsiAllowed($aLotInfo[$CNF['FIELD_TYPE']])) {
                    if (!empty($aLotInfo)) {
                        $aJVC = $oModule->_oDb->getJVC($this->_iContentId);
                        $sRoom = empty($aJVC) ? $oModule->_oConfig->getRoomId($aLotInfo[$CNF['FIELD_ID']], $aLotInfo[$CNF['FIELD_AUTHOR']]) : $aJVC[$CNF['FJVC_ROOM']];
                    } else
                        $sRoom = $oModule->_oConfig->getRoomId();

                    $this->addMarkers(array('room' => $sRoom, 'id' => $this->_iContentId));
                    return true;
                }
        }

        return false;
    }

}

/** @} */
