<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => 'blah.fr',
			'#2' => 'blah.fr',
			'#3' => 'blah.fr'
			)	
		),
	'blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => 'blah.es',
			'#2' => 'blah.es',
			'#3' => 'blah.es'
			)
		),
	'blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => 'blah.co.uk',
			'#2' => 'blah.co.uk',
			'#3' => 'blah.co.uk'
			)
		)
);