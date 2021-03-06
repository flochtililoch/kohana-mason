<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's Core Class Extension
 *
 * @author     Florent Bonomo
 */
class Kohana extends Kohana_Core
{	
	/**
	 * Debug constants
	 */
	const DEBUG_JSON = 1;	//	0001
	const DEBUG_HTML = 2;	//	0010
	const DEBUG_CACHE = 4;	//	0100

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
	 * Core caching status flag
	 *
	 * @access	public
	 * @static
	 */
	public static $debug = FALSE;
	
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
	 * CDNs
	 *
	 * @access	public
	 * @static
	 */
	public static $cdn = array();
	
	/**
	 * Index used to spread CDNs over resources
	 *
	 * @access	public
	 * @static
	 */
	public static $cdn_idx = NULL;
		
	/**
	 * Cache storing resources and their corresponding CDN
	 * Used by all components
	 *
	 * @access	public
	 * @static
	 */
	public static $resources = array();
	
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
		
		// Determine if we are running in a command line environment
		Kohana::$is_cli = (PHP_SAPI === 'cli');

		// Determine if we are running in a Windows environment
		Kohana::$is_windows = (DIRECTORY_SEPARATOR === '\\');
		
		// Determine if we are running in safe mode
		Kohana::$safe_mode = (bool) ini_get('safe_mode');
		
		// Enable the Kohana auto-loader
        spl_autoload_register(array('Kohana', 'auto_load'));

        // Enable the Kohana auto-loader for unserialization
        ini_set('unserialize_callback_func', 'spl_autoload_call');

		// Access from browser
		if(!Kohana::$is_cli)
		{
			// define application name, environment, locale, channel & CDNs
			define('APPNAME', apache_getenv('appname'));
			Kohana::$environment = constant('Kohana::'.strtoupper(apache_getenv('env')));
			Kohana::$locale = apache_getenv('locale');
			Kohana::$channel =apache_getenv('channel');
			Kohana::$cdn =  explode(';', apache_getenv('cdn'));
		}
		// Access from CLI
		else
		{
			// Extract first arg provided as application name
			$appname = (string) $_SERVER['argv'][1];
			if(!is_dir(APPSPATH.$appname))
			{
				die("\n\nApplication [$appname] does not exists\n\n");
			}
			define('APPNAME', $appname);

			// Remove it from args
			unset($_SERVER['argv'][1]);
			$argv = array_values($_SERVER['argv']);
		}
		
		// Define application path
		define('APPPATH', realpath(APPSPATH.APPNAME).DIRECTORY_SEPARATOR);
		
		// Load application core settings
		$settings = Kohana::load(APPPATH.'config/kohana.php');
		
		// Define cache & log paths
		define('CACHEPATH', realpath($settings['cache_dir']).DIRECTORY_SEPARATOR);		
		define('LOGPATH', realpath($settings['log_dir']).DIRECTORY_SEPARATOR);
		
		// Create cache dir if not present
		if(!is_dir(CACHEPATH.'kohana'))
		{
			// Create directory
			mkdir(CACHEPATH.'kohana', 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod(CACHEPATH.'kohana', 0777);
		}
		
				
		// Add Application path to global paths
		array_unshift(Kohana::$_paths, APPPATH);
		
		// Set the default time zone
        date_default_timezone_set($settings['timezone']);
		
		// Enable or disable internal caching
		Kohana::$caching = (bool) $settings['caching'][Kohana::$environment];
		
		// Set the default cache lifetime
		Kohana::$cache_life = (int) $settings['cache_life'];
		
		// Enable or disable debug mode
		Kohana::$debug = $settings['debug'];

		// Load the files paths cache
		if(Kohana::$caching === TRUE)
		{
			Kohana::$_files = Kohana::cache('Kohana::find_file()');
		}

		// Load the config & attach a file reader to config. Multiple readers are supported
		Kohana::$config = Kohana_Config::instance()->attach(new Kohana_Config_File);
		
		// If deployment is running, stop here
		if(is_file(CACHEPATH.'deploying-'.APPNAME))
		{
		    die('Back soon!');
		}

		// Set profiling
		if (isset($settings['profile'][Kohana::$environment]))
		{
			Kohana::$profiling = (bool) $settings['profile'][Kohana::$environment];
		}
		
		// Start a new benchmark
		if (Kohana::$profiling === TRUE)
		{
			$benchmark = Profiler::start('Kohana', __FUNCTION__);
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

		// Set error handling
		Kohana::$errors = (bool) $settings['errors'][Kohana::$environment];
		if(Kohana::$errors === TRUE)
		{
			// Enable Kohana exception handling, adds stack traces and error source.
			set_exception_handler(array('Kohana_Exception', 'handler'));

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

		// Set the system character set
		if(isset($settings['charset']))
		{
			Kohana::$charset = strtolower($settings['charset']);
		}
		
		// Set the MB extension encoding to the same character set
		if (function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding(Kohana::$charset);
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
		if(isset($settings['logging'][Kohana::$environment]) && $settings['logging'][Kohana::$environment] === TRUE)
		{
			Kohana::$logging = TRUE;
			Kohana::$log->attach(new Log_File(LOGPATH . 'kohana'));
		}

		// Init Locale and Channel
		I18n::init(Kohana::$locale, Kohana::$channel, $settings['i18n_source']);
		
		// Load Components tree
		if(! (Kohana::$caching === TRUE && Kohana::$tree = Kohana::cache('tree')) )
		{
			Component::init();
			Kohana::$tree = Component::tree();

		    if(Kohana::$caching === TRUE)
			{
			    // tree cache never expire
				Kohana::cache('tree', Kohana::$tree, 0);
			}
	    }
	
		// Load Resources
		if(! (Kohana::$caching === TRUE && Kohana::$resources = Kohana::cache('resources')) )
		{
			Kohana::$resources = array();
	    }

		// Stop benchmarking
		if(isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

	    // Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
		// If no source is specified, the URI will be automatically detected.
        if(Kohana::$is_cli === FALSE)
		{
			$request = Request::factory();

			// Process the whole request
			$response = $request->execute();

			// If there is still unused params, URI is wrong -> 404
			if(strlen($request->shift_param()))
			{
				$response->status(404);
			}
			// Send correct header
			$response->send_headers();

			// Display rendered page
			echo $response->body();

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
		// Transform the class name into a path
		$file = strtr($class, array('\\' => '/', '_' => '/'));

		// Non-Kohana classes that don't respect lower case convention
		if(($path = Kohana::find_file('classes', $file))
			
		 // OR Kohana classes & already compiled components 
		 || ($path = Kohana::find_file('classes', strtolower($file).@self::$tree['cache_ids'][$class]))
		
		 // OR components that haven't been compiled yet
		 || (preg_match('/^controller\/(\w+?)\/(.*\/)*?(\w*)$/i', strtolower($file), $component) && $path = Component::compile($component))	)

			// Load the class file
			return require $path;
		
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
	public static function cache($name, $data = NULL, $lifetime = NULL, $namespace = NULL)
	{
		if($namespace === NULL)
		{
			// Default Namespace
			$namespace = APPNAME.'_'.Kohana::$locale.'_'.Kohana::$channel.'_'.Kohana::$environment;
		}
		
		$name = $namespace.$name;
		
		if ($lifetime === NULL)
		{
			// Use the default lifetime
			$lifetime = Kohana::$cache_life;
		}

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
	 * Retrieves assets for the main request
	 *
	 * @param	string	asset type
	 * @return  array   assets files list
	 */
	public static function assets($type)
	{
		$assets_key = 'assets_'.$type.'_'.sha1(serialize(Request::$initial->assets));

		if(! (Kohana::$caching === TRUE && $assets = Kohana::cache($assets_key)) )
		{
			// Development and Staging environments loads unpacked assets
			$files = array($type => Request::$initial->assets[$type]);
			
			// Testing and Production environments loads packed assets
			if(!in_array(Kohana::$environment, array(Kohana::DEVELOPMENT, KOHANA::STAGING) ) )
			{
				$files = Asset::instance()->pack($files);
			}
			
			// Flatern assets array
			$assets = array();
			foreach(array_keys($files[$type]) as $cache_key)
			{
				$assets += $files[$type][$cache_key];
			}

			if(Kohana::$caching === TRUE)
			{
				Kohana::cache($assets_key, $assets);
			}
		}
		return $assets;
	}
	
	/**
	 * Returns a resource object for a given resource path
	 * contains :
	 *	cdn index key, used to spread resources across cdns to help parallel download
	 * 	resource given path or realpath if required
	 *
	 * @param	string	resource path
	 * @param	boolean	flag to enforce file presence
	 * @return	resource object
	 * @access	public
	 */
	public static function resource($path, $file_exists = TRUE)
	{
		// We should have at least one CDN defined in the list
		if(!count(Kohana::$cdn))
		{
			return FALSE;
		}
		
		// If this resource already has associated CDN, don't reprocess
		if(!array_key_exists($path, Kohana::$resources))
		{
			// Shift to the next CDN in the list
			Kohana::$cdn_idx = (Kohana::$cdn_idx !== NULL && (Kohana::$cdn_idx < (count(Kohana::$cdn) - 1)) ? Kohana::$cdn_idx + 1 : 0);
			
			$realpath = NULL;
			$newpath = $path;

			// If file presence is mandatory, get its realpath
			if($file_exists === TRUE)
			{
				preg_match('/(.*)\.(.*)$/', $path, $matches);

				$tmp_rel_path = Kohana::find_file('comps', $matches[1], $matches[2]);

				if($tmp_rel_path !== FALSE)
				{
					// Extract the relative realpath
					$realpath = realpath($tmp_rel_path);
					$path = str_replace(str_replace($path, '', $tmp_rel_path), '', $realpath);
					
					if(!in_array(Kohana::$environment, array(Kohana::DEVELOPMENT, KOHANA::STAGING) ) )
					{
						$newpath = substr($path, 0, -1 * (strlen($matches[2])+1)).'-'.filemtime($realpath).'.'.$matches[2];
						$dir = dirname(Asset::config()->dest.$newpath);
						
						// Create cache dir if not present
						if(!is_dir($dir))
						{
							// Create directory
							mkdir($dir, 0777, TRUE);

							// Set permissions (must be manually set to fix umask issues)
							chmod($dir, 0777);
						}
						copy($realpath, Asset::config()->dest.$newpath);
					}
				}
				else
				{
					throw new Kohana_View_Exception('The requested asset file :file could not be found', array(
						':file' => $matches[1].'.'.$matches[2],
					));
				}
			}
			
			// Set resource object keys
			$res = new StdClass();
			$res->path = $newpath;
			$res->cdn = Kohana::$cdn[Kohana::$cdn_idx];
			$res->realpath = $realpath;
			
			// Cache processed resource
			Kohana::$resources[$path] = $res;
		}

		// Return the resource object
		return Kohana::$resources[$path];
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
			if(!(Kohana::$debug & Kohana::DEBUG_CACHE))
			{
				system(sprintf($cmd, CACHEPATH.'classes/controller'));
				system(sprintf($cmd, CACHEPATH.'i18n'));
				system(sprintf($cmd, CACHEPATH.'kohana'));
				system(sprintf($cmd, CACHEPATH.'views'));
			}
			Cache::instance()->delete_all();
		}
		else
		{
		    // resources cache never expire
			Kohana::cache('resources', Kohana::$resources, 0);
		}
		
		// Kohana default shutdown
		parent::shutdown_handler();
	}
	
	/**
	 * Check if a given environment is valid / Return valid environments list
	 *
	 * @param	mixed		envirnoment name to check; if FALSE, will return an array containing all valid environments
	 * @return  mixed		environment is valid bool / list of valid envs.
	 * @access	public
	 * @static
	 */
	public static function valid_environment($environment = FALSE)
	{
		$valid_environments = array(
			Kohana::DEVELOPMENT => 'dev',
			Kohana::TESTING => 'test',
			Kohana::STAGING => 'stag',
			Kohana::PRODUCTION => 'www'
			);
		
		if($environment === FALSE)
		{
			return $valid_environments;
		}
		else
		{
			$environment = (array) $environment;
			foreach($environment as $env)
			{
				if(!in_array($env, $valid_environments))
				{
					return FALSE;
				}
			}
			return $environment;
		}
	}	

}	// End Kohana