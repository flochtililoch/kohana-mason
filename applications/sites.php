<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	// ****** DEVELOPMENT ******
	'dev.blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'dev.blah.fr/assets'
			)	
		),
	'dev.blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'dev.blah.es/assets'
			)
		),
	'dev.blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'dev.blah.co.uk/assets'
			)
		),
	// ****** TESTING ******
	'test.blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::TESTING,
		'cdn'		=> array(
			'blah.fr/a1',
			'blah.fr/a2',
			'blah.fr/a3'
			)	
		),
	'test.blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::TESTING,
		'cdn'		=> array(
			'blah.es/assets',
			'blah.es/assets',
			'blah.es/assets'
			)
		),
	'test.blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::TESTING,
		'cdn'		=> array(
			'blah.co.uk/a1',
			'blah.co.uk/a2',
			'blah.co.uk/a3'
			)
		),
	// ****** STAGING ******
	'stag.blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::STAGING,
		'cdn'		=> array(
			'blah.fr/assets',
			'blah.fr/assets',
			'blah.fr/assets'
			)	
		),
	'stag.blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::STAGING,
		'cdn'		=> array(
			'blah.es/assets',
			'blah.es/assets',
			'blah.es/assets'
			)
		),
	'stag.blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::STAGING,
		'cdn'		=> array(
			'blah.co.uk/assets',
			'blah.co.uk/assets',
			'blah.co.uk/assets'
			)
		),
	// ****** PRODUCTION ******
	'blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::PRODUCTION,
		'cdn'		=> array(
			'blah.fr/a1',
			'blah.fr/a2',
			'blah.fr/a3'
			)	
		),
	'blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::PRODUCTION,
		'cdn'		=> array(
			'blah.es/assets',
			'blah.es/assets',
			'blah.es/assets'
			)
		),
	'blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::PRODUCTION,
		'cdn'		=> array(
			'blah.co.uk/a1',
			'blah.co.uk/a2',
			'blah.co.uk/a3'
			)
		)
);