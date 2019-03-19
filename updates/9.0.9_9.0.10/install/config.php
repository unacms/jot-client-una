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
    'version_from' => '9.0.9',
	'version_to' => '9.0.10',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_9.0.9_9.0.10/',
	'home_uri' => 'messenger_update_909_9010',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',
	
  /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
		'execute_sql' => 0,
        'update_files' => 1,
        'update_languages' => 0,
		'clear_db_cache' => 1,
        'update_relations' => 1
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',	
	
  /**
      * Relations Section
      */
    'relations' => array(
    	'bx_notifications'
    ),
);
