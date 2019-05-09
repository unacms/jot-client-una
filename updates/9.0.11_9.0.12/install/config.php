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
    'version_from' => '9.0.11',
	'version_to' => '9.0.12',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_9.0.11_9.0.12/',
	'home_uri' => 'messenger_update_9011_9012',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',
	
  /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
		'execute_sql' => 1,
        'update_files' => 1,
        'update_languages' => 1,
		'clear_db_cache' => 1
    ),
	
	'storages' => array(
        'bx_messenger_photos_resized',
		'bx_messenger_videos_processed',
		'bx_messenger_mp3_processed'
    ),
	
	'transcoders' => array(
		'bx_messenger_mp3',
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',

    /**
     * Files Section
     */
    'delete_files' => array(
        'js/feather.min.js'
    ),
);
