<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	// Server Timezone
	'timezone'		=> '%1$s',
	
	// Default language used in templates
	'i18n_source'	=> '%2$s',
	
	// Set level of debug (FALSE = disabled, Kohana::DEBUG_JSON, Kohana::DEBUG_HTML)
	'debug'			=> Kohana::DEBUG_JSON,
	
	// Enable or disable internal caching
	'caching'		=> FALSE,
		'cache_life'	=> 0,
		'cache_dir'		=> APPPATH.'var/cache',
	
	// Enable or disable internal profiling
	'profile'		=> FALSE,
	
	// Enable or disable internal logging	
	'logging'		=> TRUE,
		'log_dir'		=> APPPATH.'var/log',
		
	// Enable or disable errors display	
	'errors'		=> TRUE
);