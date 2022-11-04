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
    'version_from' => '12.0.5',
	'version_to' => '12.0.6',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '13.0.0-A1'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_12.0.5_12.0.6/',
	'home_uri' => 'messenger_update_1205_1206',

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
		'js/filepond-plugin-file-validate-size.min.js',
		'js/filepond-plugin-image-preview.min.js',
		'js/filepond.min.js',
		'template/css/filepond-plugin-image-preview.min.css',
		'template/css/filepond.min.css',
		'template/private_chat_window.html',
		'js/emoji-mart/css',
		'js/emoji-mart/js'
	),
);
