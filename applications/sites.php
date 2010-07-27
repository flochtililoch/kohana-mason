<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'blah.fr' => array(
		'appname'	=> 'blah',
		'locale'	=> 'fr_FR',
		'channel'	=> 102,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => $_SERVER['HTTP_HOST'],
			'#2' => $_SERVER['HTTP_HOST'],
			'#3' => $_SERVER['HTTP_HOST']
			)	
		),
	'blah.es' => array(
		'appname'	=> 'blah',
		'locale'	=> 'es_ES',
		'channel'	=> 103,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => $_SERVER['HTTP_HOST'],
			'#2' => $_SERVER['HTTP_HOST'],
			'#3' => $_SERVER['HTTP_HOST']
			)
		),
	'blah.co.uk' => array(
		'appname'	=> 'blah',
		'locale'	=> 'en_GB',
		'channel'	=> 4,
		'env'		=> Kohana::DEVELOPMENT,
		'cdn'		=> array(
			'#1' => $_SERVER['HTTP_HOST'],
			'#2' => $_SERVER['HTTP_HOST'],
			'#3' => $_SERVER['HTTP_HOST']
			)
		)
);