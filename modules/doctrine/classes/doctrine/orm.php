<?php defined('SYSPATH') or die('No direct script access.');

use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;

/**
 * Doctrine ORM EntityManager Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Doctrine_Orm extends EntityManager
{
	/**
	 * Create a new Entity Manager
	 * using passed config or default config
	 *
	 * @param	array			Settings to initiate EntityManager
	 * @return	EntityManager	Instance
	 * @access	public
	 * @static
	 */
	public static function factory($db_config = NULL)
	{
		// Load default config if none passed as argument
		if($db_config === NULL)
		{
			$db_config = Kohana::config('orm');
		}
		
		// Set up drivers & configuration
		$config = new Configuration;
		
		// Config defines cache status
		if(Kohana::$caching === TRUE)
		{
			$cache_driver = 'Doctrine\Common\Cache\\'.$db_config->cache_driver;
			$cache = new $cache_driver;
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}
		
		$config->setMetadataDriverImpl(new YamlDriver(Kohana::list_paths('model/yaml')));
		$config->setAutoGenerateProxyClasses(FALSE);
		
		// Proxy configuration
		$config->setProxyDir($db_config->paths['proxies']);
		$config->setProxyNamespace('Proxies');
		
		if(Kohana::$logging === TRUE)
		{
			$config->setSqlLogger(new Doctrine_Profiler);
		}

		// Create EntityManager
		return Orm::create($db_config->connection_options[Kohana::$environment], $config);
	}
	
	/**
	 * Create Entity Manager Singleton
	 *
	 * @return	EntityManager	Entity Manager Singleton
	 * @access	public
	 * @static
	 */
	public static function instance()
	{
	    // If instance does not exists
	    if(Orm::$_instance === NULL)
	    {
	        // Create instance
	        Orm::$_instance = Orm::factory();
	    }
	    
	    // Return Main Entity Manager instance
	    return Orm::$_instance;
	}
	
	/**
	 * Attach entity to EntityManager
	 *
	 * @param   Entity			Attach an entity
	 * @return	EntityManager	Entity Manager Singleton
	 * @access	public
	 * @static
	 */
	public static function bind($entity)
	{
		Orm::instance()->persist($entity);
		
		// Return Main Entity Manager instance
	    return Orm::$_instance;
	}
	
	/**
	 * Save attached entities.
	 *
	 * @param   Entity			Attach an entity
	 * @return	EntityManager	Entity Manager Singleton
	 * @access	public
	 * @static
	 */
	public static function save($entity = NULL)
	{
		// If trying to attach entity
		if($entity !== NULL)
		{
			Orm::bind($entity);
		}

		// Save all changes to attached entities
		return Orm::instance()->flush();
	}
	
	/**
	 * Load an entity.
	 *
	 * @param   string			Entity name
	 * @param   string			Repository name
	 * @return	mixed			EntityRepository object
	 * @access	public
	 * @static
	 */
	public static function load($entity, $repository = 'Entities')
	{
		return Orm::instance()->getRepository($repository.'\\'.$entity);
	}
	
	protected static $_instance = NULL;

}	// End Doctrine_Orm