<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	// Cache driver
	'cache_driver' => 'ApcCache',
	
	// DB connection settings
	'connection_options' => array(
		Kohana::DEVELOPMENT => array(
			'driver' => 'pdo_mysql',
			'host' => '%1$s',
			'dbname' => '%2$s',
			'user' => '%3$s',
			'password' => '%4$s'
			),
		Kohana::STAGING => array(
			'driver' => 'pdo_mysql',
			'host' => '%1$s',
			'dbname' => '%2$s',
			'user' => '%3$s',
			'password' => '%4$s'
			),
		Kohana::TESTING => array(
			'driver' => 'pdo_mysql',
			'host' => '%1$s',
			'dbname' => '%2$s',
			'user' => '%3$s',
			'password' => '%4$s'
			),
		Kohana::PRODUCTION => array(
			'driver' => 'pdo_mysql',
			'host' => '%1$s',
			'dbname' => '%2$s',
			'user' => '%3$s',
			'password' => '%4$s'
			)
		),
	
	// Path to proxies classes
	'paths' => array(
		'proxies' => CACHEPATH.'classes/proxies'
	)
);