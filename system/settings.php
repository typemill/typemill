<?php 

DEFINE('DS', DIRECTORY_SEPARATOR);

return [
	'title'					=> 'TYPEMILL',
	'author'				=> 'unknown',
	'copyright'				=> 'copyright',
	'startpage'				=> true,
	'rootPath'				=> __DIR__ . DS .  '..' . DS,
	'theme'					=> ($theme = 'typemill'),
	'themeFolder'			=> ($themeFolder = 'themes'),
	'themeBasePath'			=> __DIR__ . DS . '..' . DS,
	'themePath'				=> __DIR__ . DS . '..' . DS . $themeFolder . DS . $theme,
	'settingsPath'			=> __DIR__ . DS . '..' . DS . 'settings',
	'authorPath'			=> __DIR__ . DS . 'author' . DS,
	'contentFolder'			=> 'content',
	'displayErrorDetails' 	=> false,
	'version'				=> '1.0.2'
];

?>