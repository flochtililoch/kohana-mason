<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	// Assets files types with their matching extension
	'types' => array('scripts' => 'js', 'stylesheets' => 'css'),
	
    // Path to the folder where packed files should be written
    'dest' => CACHEPATH.'assets/',
	
	// Path to the compressor binary
	'packer_bin' => SCPPATH.'/tools/yuicompressor-2.4.2.jar',
	
	// Command line used to pack a file
	'packer_cl' => 'java -jar %1$s -o %2$s %3$s --charset utf-8'
);