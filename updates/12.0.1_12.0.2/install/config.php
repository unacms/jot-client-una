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
    'version_from' => '11.0.7',
	'version_to' => '12.0.0',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '12.0.0-B1'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_11.0.7_12.0.0/',
	'home_uri' => 'messenger_update_1107_1200',

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
		'execute_sql' => 1,
        'update_files' => 1,
        'update_languages' => 1,
		'clear_db_cache' => 1,
        'process_menu_triggers' => 1,
    ),
	
	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',
	
	'delete_files' => array(
		'js/filepond.jquery.js',		
		'template/filepond_config.html',
		'template/talk_header_menu.html',
	),
);
