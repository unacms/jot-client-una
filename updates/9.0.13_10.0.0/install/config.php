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
    'version_from' => '9.0.13',
	'version_to' => '10.0.0',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '10.0.0-B1'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_9.0.13_10.0.0/',
	'home_uri' => 'messenger_update_9013_1000',

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
		'clear_db_cache' => 1
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',	

);
