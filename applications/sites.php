<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::TESTING,
		'cdn'		=> array(
			'blah.fr/a',
			'blah.fr/a',
			'blah.fr/a'
			)	
		),
	'blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::DEVELOPMENT,
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
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'blah.co.uk/assets',
			'blah.co.uk/assets',
			'blah.co.uk/assets'
			)
		)
);