<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'timezone'		=> 'Europe/Paris',			// Server Timezone
	'i18n_source'	=> 'en-gb',					// Default language used in templates
	'debug'			=> Kohana::DEBUG_JSON,		// Set level of debug (FALSE = disabled, Kohana::DEBUG_JSON, Kohana::DEBUG_HTML)
	'caching'		=> FALSE,					// Enable or disable internal caching
		'cache_life'	=> 0,
		'cache_dir'		=> APPPATH.'var/cache',
	'profile'		=> TRUE,					// Enable or disable internal profiling
	'logging'		=> FALSE,					// Enable or disable internal logging	
		'log_dir'		=> APPPATH.'var/log',
	'errors'		=> TRUE
);