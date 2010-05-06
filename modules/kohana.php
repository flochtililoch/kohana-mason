<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's Core Class Extension
 *
 * @author     Florent Bonomo
 */
class Kohana extends Kohana_Core
{

	public static $domain_name = NULL;
	public static $domain_ext = NULL;

	/**
	 * Components tree
	 *
	 * @access	public
	 * @static
	 */
	public static $tree = NULL;
	
	/**
	 * Core profiling status flag
	 *
	 * @access	public
	 * @static
	 */
	public static $profiling = FALSE;
	
	/**
	 * Core logging status flag
	 *
	 * @access	public
	 * @static
	 */
	public static $logging = FALSE;
	
	/**
	 * Core caching status flag
	 *
	 * @access	public
	 * @static
	 */
	public static $caching = TRUE;
	
	/**
	 * Core cache driver (module Cache)
	 * Default : Kohana internal file caching
	 *
	 * @access	public
	 * @static
	 */
	public static $cache_driver = 'Apc';
	
	/**
	 * Core cache expire time (module Cache)
	 * Default : never expire
	 *
	 * @access	public
	 * @static
	 */
	public static $cache_expire = 0;
	
	/**
	 * Default locale
	 *
	 * @access	public
	 * @static
	 */
	public static $locale = 'fr_FR';
	
	/**
	 * Default channel
	 *
	 * @access	public
	 * @static
	 */
	public static $channel = 1;
	
	/**
	 * Include paths that are used to find files
	 * before init paths modification
	 *
	 * @access	protected
	 * @static
	 */
	protected static $_paths = array(SYSPATH);
	
	/**
	 * Initializes the environment:
	 *
	 * - Disables register_globals and magic_quotes_gpc
	 * - Determines the current environment
	 * - Set global settings
	 * - Sanitizes GET, POST, and COOKIE variables
	 * - Converts GET, POST, and COOKIE variables to the global character set
	 *
	 *
	 * @throws  Kohana_Exception
	 * @param   array   global settings
	 * @return  void
	 */
	public static function init(array $argv = NULL)
	{
		if (Kohana::$_init)
		{
			// Do not allow execution twice
			return;
		}

		// Kohana is now initialized
		Kohana::$_init = TRUE;
		
		// Enable the Kohana auto-loader
        spl_autoload_register(array('Kohana', 'auto_load'));

        // Enable the Kohana auto-loader for unserialization
        ini_set('unserialize_callback_func', 'spl_autoload_call');

		// If access from browser
		if(array_key_exists('SERVER_NAME', $_SERVER))
		{
			// load domain config
			$sites = Kohana::load(APPSPATH.'sites.php');
			$application = $sites[$_SERVER['SERVER_NAME']];
			
			// define application name, environment, locale & channel
			define('APPNAME', $application['appname']);
			Kohana::$environment = $application['env'];
			Kohana::$locale = $application['locale'];
			Kohana::$channel = $application['channel'];
		}
		
		// Define application, var & cache paths
		define('APPPATH', realpath(APPSPATH.APPNAME).DIRECTORY_SEPARATOR);
		define('VARPATH', realpath(APPPATH.'var').DIRECTORY_SEPARATOR);
		define('CACHEPATH', realpath(VARPATH.'cache').DIRECTORY_SEPARATOR);
		
		// Add Application path to global paths
		array_unshift(Kohana::$_paths, APPPATH);

		// Load the files paths cache
		if(Kohana::$caching === TRUE)
		{
			Kohana::$_files = Kohana::cache('Kohana::find_file()');
		}

		// Load the config & attach a file reader to config. Multiple readers are supported
		Kohana::$config = Kohana_Config::instance()->attach(new Kohana_Config_File);
		
		// If deployment is running, stop here
		if(is_file(VARPATH.'deploying-'.APPNAME))
		{
		    die('Back soon!');
		}

		// Load core settings
		$settings = Kohana::config('kohana');

		// Set profiling
		if (isset($settings['profile']))
		{
			Kohana::$profiling = (bool) $settings['profile'];
		}
		
		// Start a new benchmark
		if (Kohana::$profiling === TRUE)
		{
			$benchmark = Profiler::start('Kohana', __FUNCTION__);
		}
		
		// Set the default time zone
        date_default_timezone_set($settings['timezone']);
		
		// Enable or disable internal caching
		Kohana::$caching = (bool) $settings['caching'];
		
		// Create cache dir if not present
		if(!is_dir(CACHEPATH.'kohana'))
		{
			// Create directory
			mkdir(CACHEPATH.'kohana', 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod(CACHEPATH.'kohana', 0777);
		}
		
		// Set the cache directory path
		Kohana::$cache_dir = realpath(CACHEPATH.'kohana');
		
		// Define which cache driver to use
        if(isset($settings['cache_drv']))
		{
			Kohana::$cache_driver = $settings['cache_drv'];
		}
		
		// Define cache expire time
		if(isset($settings['cache_exp']))
		{
			Kohana::$cache_expire = $settings['cache_exp'];
		}
		
		// Set the new include paths
		Kohana::$_paths = (array) Kohana::config('paths');
		foreach(Kohana::config('paths') as $path)
		{
			$init = $path.DIRECTORY_SEPARATOR.'init'.EXT;

			if(is_file($init))
			{
				// Include the module initialization file once
				require_once $init;
			}
		}
		
		// Start an output buffer
		ob_start();

		// E_DEPRECATED only exists in PHP >= 5.3.0
		if(defined('E_DEPRECATED'))
		{
			Kohana::$php_errors[E_DEPRECATED] = 'Deprecated';
		}

		// Set error handling
		Kohana::$errors = (bool) $settings['errors'];
		if(Kohana::$errors === TRUE)
		{
			// Enable Kohana exception handling, adds stack traces and error source.
			set_exception_handler(array('Kohana', 'exception_handler'));

			// Enable Kohana error handling, converts all PHP errors to exceptions.
			set_error_handler(array('Kohana', 'error_handler'));
		}

		// Enable the Kohana shutdown handler, which catches E_FATAL errors.
		register_shutdown_function(array('Kohana', 'shutdown_handler'));

		// Stop application if register_globals enabled
		if(ini_get('register_globals'))
		{
			// Exit with an error status
			exit(1);
		}

		// Determine if we are running in a command line environment
		Kohana::$is_cli = (PHP_SAPI === 'cli');

		// Determine if we are running in a Windows environment
		Kohana::$is_windows = (DIRECTORY_SEPARATOR === '\\');

		// Set the system character set
		if(isset($settings['charset']))
		{
			Kohana::$charset = strtolower($settings['charset']);
		}

		// Set the base URL
		Kohana::$base_url = '/';

		// Index file is never displayed
		Kohana::$index_file = '';
		
		// Determine if the extremely evil magic quotes are enabled
		Kohana::$magic_quotes = (bool) get_magic_quotes_gpc();

		// Sanitize all request variables
		$_GET    = Kohana::sanitize($_GET);
		$_POST   = Kohana::sanitize($_POST);
		$_COOKIE = Kohana::sanitize($_COOKIE);

		// Load the logger
		Kohana::$log = Kohana_Log::instance();
		
		// Attach the file write to logging. Multiple writers are supported
		if(isset($settings['logging']) && $settings['logging'] === TRUE)
		{
			Kohana::$logging = TRUE;
			Kohana::$log->attach(new Kohana_Log_File(VARPATH . 'log/' . APPNAME . '/kohana'));
		}
		
		// Load Components tree
		if(! (Kohana::$caching === TRUE && Kohana::$tree = Kohana::cache('tree'))  )
		{
			Component::init();
			Kohana::$tree = Component::tree();

		    if(Kohana::$caching === TRUE)
			{
			    // tree cache never expire
				Kohana::cache('tree', Kohana::$tree, 0);
			}
	    }

		// Init Locale and Channel
		I18n::init(Kohana::$locale, Kohana::$channel);

		// Stop benchmarking
		if(isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

	    // Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
		// If no source is specified, the URI will be automatically detected.
        if(Kohana::$is_cli === FALSE)
		{
			echo Request::instance()
				->execute()
				->send_headers()
				->response;
				
			// Echo profile results
			if($settings['profile'] === TRUE)
			{
				echo View::factory('profiler/stats');
			}
			
			// Save persistent entities
			Orm::save();
        }
	}

	/**
	 * Replace Kohana's auto_load method.
	 * Looks for files in filesystem.
	 * Controllers have their cache id in their filename.
	 * In case controller class file not found, try to compile it
	 *
	 * @param   string   class name
	 * @return  boolean
	 * @access	public
	 * @static
	 */
	public static function auto_load($class)
	{
		// Lower case class name
		$class = strtolower($class);
		
		// Transform the class name into a path
		$file = strtr($class, array('\\' => '/', '_' => '/'));

		// If class exists in the filesystem
		if ($path = Kohana::find_file('classes', $file.@self::$tree['cache_ids'][$class]))
		{
			// Load the class file
			require $path;
			
			// Class has been found
			return TRUE;
		}
		// Else if class to load is a controller that has not been cached yet
		elseif (preg_match('/^controller\/(\w+?)\/(.*\/)*?(\w*)$/i', $file, $component) && $path = Component::compile($component))
		{
	        require $path;
			
			// Class has been found
			return TRUE;
		}

		// Class is not in the filesystem
		return FALSE;
	}

	/**
	 * Load / Write the cache
     *
	 * @param   string   name of the cache
	 * @param   mixed    data to cache
	 * @param   integer  number of seconds the cache is valid for
	 * @return  mixed    for getting
	 * @return  boolean  for setting
	 * @access	public
	 * @static
	 */
	public static function cache($name, $data = NULL, $lifetime = 0, $namespace = APPNAME)
	{
		$name = $namespace.$name;

		if ($data === NULL)
		{
			// if cache to retrieve is find_file results, load it directly from apc
			// (to avoid classes paths not being included in cache)
			if($name === $namespace.'Kohana::find_file()')
			{
				if(Kohana::$cache_driver === 'Apc')
				{
					return apc_fetch($name);
				}
				else
				{
					return Kohana_Core::cache($name);
				}
			}
			return Cache::instance()->get($name);
		}

		// Write the cache
		return Cache::instance()->set($name, $data, $lifetime);
	}
	
	/**
	 * List all paths where $dir has been found
	 *
	 * @param	string	directory to look for
	 * @return	array	list of paths
	 * @access	public
	 * @static
	 */
	public static function list_paths($dir)
	{
		$paths = array();
		
		// Loop through each paths
		foreach(self::$_paths as $path)
		{
			// If directory is present in current path, store it
			if(is_dir($path.$dir))
			{
				$paths[] = $path.$dir;
			}
		}
		
		// return paths
		return $paths;
	}
	
	/**
	 * Replace Kohana include paths
	 *
	 * @param	array	New paths to set
	 * @access	public
	 * @static
	 */
	public static function set_paths(Array $paths)
	{
		// Set new paths
		Kohana::$_paths = $paths;
		
		// Reset Kohana files cache
		Kohana::$_files = array();
	}
	
	/**
	 * Shutdown application
	 *
	 * @uses    Kohana::exception_handler
	 * @return  void
	 * @access	public
	 * @static
	 */
	public static function shutdown_handler()
	{
		// If cache is disabled, remove cache entries
		if(Kohana::$caching === FALSE)
		{
			if(Kohana::$is_windows !== TRUE)
			{
				$cmd = 'rm -rf %s';
			}
			else
			{
				$cmd = 'rd "%s" /s /q';
			}
			system(sprintf($cmd, CACHEPATH.'classes/controller'));
			system(sprintf($cmd, CACHEPATH.'i18n'));
			system(sprintf($cmd, CACHEPATH.'kohana'));
			system(sprintf($cmd, CACHEPATH.'views'));
			Cache::instance()->delete_all();
		}
		
		// Kohana default shutdown
		parent::shutdown_handler();
	}

}	// End Kohana