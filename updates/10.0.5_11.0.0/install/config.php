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
    'version_from' => '10.0.5',
	'version_to' => '11.0.0',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '10.1.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_10.0.5_11.0.0/',
	'home_uri' => 'messenger_update_1005_1100',

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
		'clear_db_cache' => 1,
    ),
	
	/**
     * Category for language keys.
     */
    'language_category' => 'Messenger',	
	
	'delete_files' => array(
		'data/notify.wav',
		'js/dropzone.js',
		'js/imagesloaded.pkgd.min.js',
		'template/css/dropzone.css',
		'template/uploader_form.html',
		'template/images/icons/android-chrome-192x192.png',
		'template/images/icons/android-chrome-512x512.png',
		'template/images/icons/apple-touch-icon.png',
		'template/images/icons/browserconfig.xml',
		'template/images/icons/manifest.json',
		'template/images/icons/mstile-144x144.png',
		'template/images/icons/mstile-70x70.png',
		'template/images/icons/mstile-150x150.png',
		'template/images/icons/mstile-310x150.png',
		'template/images/icons/mstile-310x310.png',
		'template/images/icons/safari-pinned-tab.svg'
	),
);
