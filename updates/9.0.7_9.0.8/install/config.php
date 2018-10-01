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
    'version_from' => '9.0.7',
	'version_to' => '9.0.8',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '9.0.0-RC10'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_9.0.7_9.0.8/',
	'home_uri' => 'messenger_update_907_908',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',

    /**
    *Menu triggers.
    */
    'menu_triggers' => array(
        'trigger_profile_view_actions',
    ),

    /**
     * Installation/Uninstallation Section.
     */
    'install' => array(
        'execute_sql' => 0,
        'update_files' => 1,
        'update_languages' => 0,
        'process_menu_triggers' => 1,
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
