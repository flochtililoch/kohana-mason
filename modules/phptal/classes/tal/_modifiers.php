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
 * Load an entity from ORM manager
 */
function phptal_tales_orm($path, $nothrow)
{
	// Convert path to array
	$segments = explode('/', trim($path));
	
	// First string in path is an entity
	$entity = array_shift($segments);
	
	return '$ctx->path(ORM::Load(\''.$entity.'\'), \''.implode('/', $segments).'\')';
}

/**
 * Static method call
 */
function phptal_tales_static($paths, $nothrow)
{
	// Convert path to array
	$paths = explode(' ', trim($paths));
	
	// First string in path is class name/method_name
	$static = explode('/', array_shift($paths));
	$class = $static[0];
	$method = $static[1];
	
	if(count($paths))
	{
		$params = array();
		foreach($paths as $path)
		{
			$segments = explode('/', $path);
			$obj = '$ctx->'.array_shift($segments);
			if(count($segments))
			{
				$params[] = '$ctx->path('.$obj.', \''.implode('/', $segments).'\')';
			}
			// If there's just one object path, no needs to run path() on it
			else
			{
				$params[] ='\''.$path.'\'';
			}
		}
		$params = implode(', ', $params);
	}
	else
	{
		$params = '';
	}

	return 'phptal_tostring('.$class.'::'.$method.'('.$params.'))';
}

/**
 * Return a resource path
 */
function phptal_tales_asset($path, $nothrow)
{
	$res = Kohana::resource(trim($path));
	return '\''.$res->cdn.$res->path.'\'';
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