<?php

/**
 * PHPTAL modifiers
 *
 * @package    PHPTAL
 * @author     Florent Bonomo
 */
 
/**
 * Perform a sub-request
 */
function phptal_tales_comp($paths, $nothrow)
{
	// Convert paths to array
	$paths = explode(' ', trim($paths));
	
	// First string in paths is a comp
	$comp = array_shift($paths);
	
	if(count($paths))
	{
		$params = array();
		foreach($paths as $path)
		{
			$segments = explode('/', $path);
			$obj = '$ctx->'.array_shift($segments);
			if(count($segments))
			{
				$params[] = '\''.$segments[count($segments)-1].'\' => $ctx->path('.$obj.', \''.implode('/', $segments).'\')';
			}
			// If there's just one object path, no needs to run path() on it
			else
			{
				$params[] ='\''.$path.'\' => '.$obj;
			}
		}
		$params = ', array('.implode(', ', $params).')';	
	}
	else
	{
		$params = '';
	}

	return 'phptal_tostring(Request::factory(\''.$comp.'\',$ctx->controller'.$params.')->execute()->response)';
}

/**
 * Load an asset file
 */
function phptal_tales_asset($src, $nothrow)
{
	return '\''.Kohana::resource($src).'\'';
}

/**
 * Translate an URI
 */
function phptal_tales_uri($src, $nothrow)
{
	return '\''.I18n::instance()->uri($src).'\'';
}

/**
 * Translate an URI and make it relative to base uri
 */
function phptal_tales_base_uri($src, $nothrow)
{
	return '$_SERVER[\'REQUEST_URI\'].($_SERVER[\'REQUEST_URI\'] !== \'/\'? \'/\':\'\').\''.I18n::instance()->uri($src).'\'';
}

/**
 * Dump an object from within the context using Kohana::debug method
 */
function phptal_tales_dump($src, $nothrow)
{
	return 'Kohana::debug('.phptal_tales($src, $nothrow).')';
}