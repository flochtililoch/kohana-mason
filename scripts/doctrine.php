<?php

// Init app
require __DIR__.'/init.php';

// Initiate Entity manager
$orm = Orm::factory();
$configuration = new \Doctrine\Common\Cli\Configuration();
$configuration->setAttribute('em', $orm);

// If create or update mode, add to arguments all paths where entities have been found
if(in_array('--create', $_SERVER['argv']) || in_array('--update', $_SERVER['argv']) || in_array('--complete-update', $_SERVER['argv']))
{
	$_SERVER['argv'][] = '--class-dir='.implode(',', Kohana::list_paths('classes/entities'));
}

// If convert mapping mode
elseif(in_array('orm:generate-entities', $_SERVER['argv']))
{
	// Run CLI Controller
	$cli = new \Doctrine\Common\CLI\CLIController($configuration);
	
	// Create cache dir if not present
	if(!is_dir(CACHEPATH.'classes'))
	{
		// Create directory
		mkdir(CACHEPATH.'classes', 0777, TRUE);

		// Set permissions (must be manually set to fix umask issues)
		chmod(CACHEPATH.'classes', 0777);
	}

	// Each path containing mapping informations
	foreach(Kohana::list_paths('model/yaml') as $path)
	{
		// Use passed arguments
		$argv = $_SERVER['argv'];
		$argv[] = '--from='.$path;
		$argv[] = '--dest='.CACHEPATH.'classes';
		
		// Run CLI
		$cli->run($argv);
	}
	
	// And stop here
	exit(0);
}

// Run CLI Controller
use \Doctrine\Common\Cli\CliController;
$cli = new \Doctrine\Common\Cli\CliController($configuration);
$cli->run($_SERVER['argv']);