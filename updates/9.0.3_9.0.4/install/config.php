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
    'version_from' => '9.0.3',
	'version_to' => '9.0.4',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0-RC6'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_9.0.3_9.0.4/',
	'home_uri' => 'messenger_update_903_904',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',

	/**
     * Transcoders.
     */
    'transcoders' => array(
    	'bx_messenger_videos_poster',
		'bx_messenger_videos_mp4',
		'bx_messenger_videos_webm'
    ),

    /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
		'execute_sql' => 1,
        'update_files' => 1,
        'update_languages' => 1,
		'register_transcoders' => 1,
		'clear_db_cache' => 1,
    ),

	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',

	/**
     * Files Section
     */
    'delete_files' => array(),
);
