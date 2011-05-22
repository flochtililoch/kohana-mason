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
	$paths = explode(';', trim($paths));
	
	// First string in paths is a comp
	$comp = array_shift($paths);
	
	if(count($paths))
	{
		$params = array();
		foreach($paths as $path)
		{
			$params[] = phptal_tale($path, $nothrow);
		}
		$params = ', array('.implode(', ', $params).')';	
	}
	else
	{
		$params = '';
	}

	return 'phptal_tostring(Request::factory(\''.$comp.'\', NULL, array(), $ctx->controller'.$params.')->execute())';
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
	$paths = explode(',', trim($paths));

	// First string in path is class name/method_name
	$static = explode('/', array_shift($paths));
	$class = $static[0];
	$method = $static[1];
	
	if(count($paths))
	{
		$params = array();
		foreach($paths as $path)
		{
			$params[] = phptal_tale($path, $nothrow);
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
	return 'Debug::dump('.phptal_tales($src, $nothrow).')';
}

/**
 * Dump an object from within the context using Kohana::debug method
 */
function phptal_tales_in_array($src, $nothrow)
{
	// Convert path to array
	$paths = explode(',', trim($src));
	return 'is_array('.phptal_tales($paths[1], $nothrow).') && in_array('.phptal_tales($paths[0], $nothrow).', '.phptal_tales($paths[1], $nothrow).')';
}

function phptal_tales_null($src, $nothrow)
{
	return phptal_tales($src, $nothrow).' === NULL';
}