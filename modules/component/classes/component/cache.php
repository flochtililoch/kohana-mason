<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana Cache
 * 
 * Caching library for Kohana PHP 3
 * Modified to get config items from Kohana static members instead of a config file
 *
 * @package    	Component
 * @category   	Cache
 * @author     	Kohana Team
 * @modifiedby	Florent Bonomo
 * @copyright  	(c) 2009-2010 Kohana Team
 * @license    	http://kohanaphp.com/license
 */
abstract class Component_Cache extends Kohana_Cache
{

	/**
	 * Get a singleton cache instance. If no configuration is specified,
	 * it will be loaded using the standard configuration 'type' setting.
	 *
	 * @param   string   the name of the cache driver to use [Optional]
	 * @return  Kohana_Cache
	 */
	public static function instance($type = NULL)
	{
		// Resolve type
		$type === NULL and $type = Kohana::$cache_driver;

		// Return the current type if initiated already
		if (isset(Cache::$instances[$type]))
			return Cache::$instances[$type];

		// Create a new cache type instance
		$cache_class = 'Cache_'.$type;
		Cache::$instances[$type] = new $cache_class;

		// Return the instance
		return Cache::$instances[$type];
	}
	
	/**
	 * Ensures singleton pattern is observed, loads the default expiry
	 */
	protected function __construct()
	{
		$this->_default_expire = Kohana::$cache_expire;
	}

}	// End Component_Cache