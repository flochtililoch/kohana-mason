<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'blah.fr',
			'blah.fr',
			'blah.fr'
			)	
		),
	'blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'blah.es',
			'blah.es',
			'blah.es'
			)
		),
	'blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'blah.co.uk',
			'blah.co.uk',
			'blah.co.uk'
			)
		)
);