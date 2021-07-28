<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'title' => 'Messenger',
    'version_from' => '12.0.0',
	'version_to' => '12.0.1',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '12.0.0-B1'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_12.0.0_12.0.1/',
	'home_uri' => 'messenger_update_1200_1201',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',
	
	'menu_triggers' => array(
        'trigger_profile_view_actions',
    ),
	
  /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
	    'update_files' => 1,
    	'clear_db_cache' => 1      
    ),
	
	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',
	
	'delete_files' => array(),
);
