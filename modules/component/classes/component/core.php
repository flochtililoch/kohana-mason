<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Component Class
 * Used to convert a tree of component
 * into Kohana classes
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Core
{
	
	const AUTOHANDLER = 'autohandler';
	const DHANDLER = 'dhandler';

	/**
	 * Original paths per applications
	 * @access	protected
	 * @static
	 */
	protected static $_original_paths = array();
	
	/**
	 * Locales to process when building the tree
	 * @access	protected
	 * @static
	 */
	protected static $_enabled_locales = array();
	
	/**
	 * Locales to process when building the tree
	 * @access	protected
	 * @static
	 */
	protected static $_internal_trees = array();
	
	/**
	 * Locales to process when building the tree
	 * @access	protected
	 * @static
	 */
	protected static $_external_trees = array();
	
	/**
	 * Init Component environment
	 *
	 * @param	string	Application name (optional)
	 * @access	public
	 * @static
	 */
	public static function init($appname = NULL)
	{
		// Set per application settings if appname passed as parametre
		if($appname !== NULL)
		{
			Component::set_app($appname);
		}
		else
		{
			$appname = APPNAME;
		}
		
		// Init locales container
		Component::$_enabled_locales = array();
		
		// Loop through all existing sites
		foreach(Kohana::load(APPSPATH.'sites.php') as $application)
		{
			// If pointed site runs within current app
			if($application['appname'] === $appname)
			{
				// Save its locale
				Component::$_enabled_locales[] = $application['locale'];
			}
		}
		
		// Load internal and external trees paths
		Component::$_internal_trees = Kohana::config('component.internal');
		Component::$_external_trees = Kohana::config('component.external');
	}
	
	/**
	 * De-Init Component environment
	 *
	 * @param	string	Application name
	 * @access	public
	 * @static
	 */
	public static function deinit($appname)
	{
		// Reset static storage
		Component::$_enabled_locales = array();
		Component::$_internal_trees = array();
		Component::$_external_trees = array();
		
		// Restore original application include paths
		Kohana::set_paths(Component::$_original_paths[$appname]);
	}
	
	/**
	 * Set Application to be used by Component tree builder
	 *
	 * @param	string	Application name
	 * @access	public
	 * @static
	 */
	public static function set_app($appname)
	{
		// Don't allow more than one execution per app
		if(isset(Component::$_original_paths[$appname]))
		{
			return FALSE;
		}
		
		// Get current include paths
		$paths = Kohana::include_paths();
		
		// Save original paths
		Component::$_original_paths[$appname] = $paths;
		
		// Remove the two first elements of the array (apppath and cachepath)
		array_shift($paths);
		array_shift($paths);
		
		// Prepend new application specific paths
		array_unshift($paths, APPSPATH.$appname.DIRECTORY_SEPARATOR);
		
		// Then applies them to Kohana
		Kohana::set_paths($paths);
	}
    
   /**
	 * Compile xml components into php controller
	 *
	 * @param	array	Component informations
	 * @return	string	Compiled component absolute path
	 * @access	public
	 * @static
	 */
	public static function compile($component)
	{
		$path = $component[1];
		$directory = str_replace('_', '/', $component[2]);
		$controller = $component[3];

		// Build class name
		$class = str_replace(' ', '_', ucwords(str_replace('/', ' ', $component[0])));

		// Get xml comp path
		$xml_comp = Kohana::find_file('comps', $path.'/'.$directory.$controller, 'xml');

		// Generate cache dir name matching the component
		$cache_dir = CACHEPATH.'/classes/controller/'.$path.'/'.$directory;

		// Set full path of compiled comp
		$compiled_comp = $cache_dir.$controller.Kohana::$tree['cache_ids'][strtolower($class)].EXT;

        // If cache directory doesn't exists
		if (!is_dir($cache_dir))
		{
			// Create directory
			mkdir($cache_dir, 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod($cache_dir, 0777);
		}

		// Load xml component
		$xml = simplexml_load_file($xml_comp);

		// Compile attributes
		$attributes = array();
		foreach($xml->attr as $keys)
		{
			foreach($keys->key as $value => $key)
			{
				$attributes[] = sprintf('public $%1$s = %2$s;', $key->attributes()->__toString(), $key[0]);
			}
		}

		// Compile output variables
		$view_engine = Kohana::config('view.engine');
		$process = array();
		if($xml->process)
		{
			// Look for attributes of <process/> tag
			foreach($xml->process->attributes() as $attribute => $value)
			{
				// If attribute "engine" found, the store it specificaly for this current class
				if($attribute === 'engine')
				{
					$view_engine = $value;
				}
			}

			// Loop through all keys under <process/>
			foreach($xml->process as $keys)
			{
				// Each key being a view variable
				foreach($keys->key as $value => $key)
				{
					$process[] = sprintf('self::$_process[\'%2$s\'] = %3$s;', $class, $key->attributes()->__toString(), $key[0]);
				}
			}
		}

		// Find inheritance : if a parent autohandler exists, then class inherits from its parent
		$extends = 'Controller';
		if(Kohana::$tree['comps'][$path][$directory][$controller]['inherit'] !== NULL)
		{
			// Extends from Controller by default for orphans comps
			$extends = Kohana::$tree['comps'][$path][$directory][$controller]['inherit'];
		}

		// Write php file
		file_put_contents(
			$compiled_comp,
			'<?php'.sprintf( 
				substr(file_get_contents(MODPATH.'component/sample/controller'.EXT), 5, -1),		// comp template
				$xml_comp,															                // xml comp path
				$class,																				// class name
				$extends,																			// extended from
				implode($attributes, chr(13).chr(9)),												// attributes
				$path,																				// component origin
				addslashes($directory),																// component path
				$controller,																		// component name
				$view_engine !== NULL ? $view_engine : 'NULL',										// view rendering engine
				trim($xml->php),																	// php code
				implode($process, chr(13).chr(9).chr(9))					                        // view variables
				));

		// Return compiled comp path
		return $compiled_comp;
	}
	
	/**
	 * Build the components tree by listing by scanning path defined in config
	 * Stores it in static $_tree
	 * 
	 * @return	array	Component tree
	 * @access	public
	 * @static
	 */
	public static function tree()
	{
		// If comp tree is not built yet
		if (Component::$_tree === NULL)
		{
		    // Prepare routes containers
    		Component::$_tree['routes'] = array('internal' => array(), 'external' => array());
			foreach(Component::$_enabled_locales as $locale)
			{
				Component::$_tree['routes']['external'][$locale] = array();
			}

		    // Loop through comps tree 
		    foreach(array_merge(Component::$_internal_trees, Component::$_external_trees) as $dir)
		    {
		        // Scan dir for components
		        Component::_scan_files($dir);
			}
		}

		// Return static comp tree
		return Component::$_tree;
	}
	
	/**
	 * Components tree
	 * @access	protected
	 * @static
	 */
	protected static $_tree = NULL;
	
	/**
	 * Convert list of files into component tree
	 *
	 * @param	string	component tree root dir
	 * @return	void
	 * @access	protected
	 * @static
	 */
	protected static function _scan_files($path)
	{
	    // Get the last part of the path (used as key in tree)
        preg_match('/^(.*)\/(.*)$/', $path, $matches);
	    $root_dir = $matches[1];
	    $tree = $matches[2];

        // List files within the directory
		$files = Kohana::list_files($path);
		
		// Prepare variables
		$comps = array();
		$routes_defaults = array('catchall' => array(), 'controller' => array());
		$routes = array('internal' => $routes_defaults, 'external' => array());
		
		// Only external routes needs to be translated
		foreach(Component::$_enabled_locales as $locale)
		{
			$routes['external'][$locale] =  $routes_defaults;
		}
		
		// Flattern file list
		array_walk_recursive(
			$files,
			create_function(
				'$file, $key, $comps',
				'$comps[0][$file] = str_replace("\\\", "/", $key);'		// Convert windows path
				),
			    array(&$comps)											// http://www.keyvez.net/2007/06/getting-around-phps-call-time-pass-by.html
			);

		// Sort components from shallowest to deepest in the tree
		uasort(
			$comps,
			create_function(
				'$x, $y',
				'$x = substr_count($x,"/");
				 $y = substr_count($y,"/");
				 if($x == $y)
				 {
					return 0;
				 }
				 return ($x < $y) ? -1 : 1;'
				)
			);

		// Loop through all tree entries
		foreach($comps as $file => $entry)
		{
		    // If the entry is a controller
			if(preg_match('/^'.preg_quote($path, '/').'\/(.*\/)?([a-zA-Z0-9]*)\.xml$/', $entry, $comp))
			{
				$directory = $comp[1];
				$controller = $comp[2];
				 // Autohandlers are not routable
				if($controller !== Component::AUTOHANDLER)
				{
					// Only external routes needs to be translated
					if(in_array($path, Component::$_external_trees))
					{
						foreach(Component::$_enabled_locales as $locale)
						{
							$routes['external'][$locale][($controller === Component::DHANDLER ? 'catchall' : 'controller')][$tree.'/'.$directory.$controller] = Component::_route($comp, $tree, $locale);
						}
					}
					if(in_array($path, Component::$_internal_trees))
					{
						$routes['internal'][($controller === Component::DHANDLER ? 'catchall' : 'controller')][$tree.'/'.$directory.$controller] = Component::_route($comp, $tree);
					}
				}
				Component::$_tree['comps'][$tree][$directory][$controller]['inherit'] = Component::_inherit($comp, $comps, $path);
				Component::$_tree['cache_ids']['controller_'.str_replace('/', '_', $tree.'/'.$directory.$controller)] = filemtime($file);
			}

			// If the entry is a view
			if(preg_match('/^'.preg_quote($path, '/').'\/(.*\/)?_([a-zA-Z0-9]*)\/(def|[a-zA-Z]{2})\.?(def|[a-zA-Z]{2})?\.?(def|\d+)?\.?xhtml$/', $entry, $view))
			{
			    //  <language>.<country>.<channel>.xhtml
				//      OR
				//  <language>.<country>.xhtml (assumes default channel)
				//      OR
				//  <language>.xhtml (assumes default country & channel)
			    $directory = $view[1];
			    $controller = $view[2];
			    $language = (!isset($view[3]) || $view[3] === 'def') ? 'def' : $view[3];
			    $country = (!isset($view[4]) || $view[4] === 'def') ? 'def' : $view[4];
			    $channel = (!isset($view[5]) || $view[5] === 'def') ? 'def' : $view[5];
			    $view_file = (!isset($view[3]) ? 'def' : $view[3]).(!isset($view[4]) ? '' : '.'.$view[4]).(!isset($view[5]) ? '' : '.'.$view[5]);
			    Component::$_tree['comps'][$tree][$directory][$controller]['views'][$language][$country][$channel]['name'] = $view_file;
			    Component::$_tree['comps'][$tree][$directory][$controller]['views'][$language][$country][$channel]['cache_id'] = filemtime($file);
			}
		}

		// Catchall routes need to be reversed (deepest to shallowest)
		foreach(Component::$_enabled_locales as $locale)
		{
			Component::$_tree['routes']['external'][$locale] += $routes['external'][$locale]['controller'] + array_reverse($routes['external'][$locale]['catchall']);
		}
		Component::$_tree['routes']['internal'] += $routes['internal']['controller'] + array_reverse($routes['internal']['catchall']);
	}
	
	/**
	 * Returns for a given component (array resulting from regex)
	 * the corresponding wrapper it should inherit from
	 *
	 * @param	array	component data
	 *						$comp[1] = origin (private, public, ...)
	 *						$comp[2] = directory
	 *						$comp[3] = controller name
	 * @param	array	list of components paths
	 * @return	string	wrapping component path
	 * @access	protected
	 * @static
	 */
	protected static function _inherit($comp, $comps, $path)
	{
		// Split requested component path into segments (no base path for root wrapper)
		$segments = explode('/', ($comp[1] === '') ? '' : '/'.rtrim($comp[1],'/'));

		// If given component is a wrapper (autohandler)
		if($comp[2] === Component::AUTOHANDLER)
		{
			// Dont look in its own directory, but start one level up
			array_pop($segments);
		}
		
		// Browse paths up to the root
		while (count($segments) > 0)
		{
			// Path should read foo/bar/.../
			$dir = ltrim(implode('/', $segments), '/');
			
			// Empty path ? no directory separator
			$dir .= $dir === '' ? '' : '/';
			
			// Check if the current wrapper exists
			$current_wrapper = $path.'/'.$dir.Component::AUTOHANDLER;

			if(in_array($current_wrapper.'.xml', $comps))
			{
				// Our comp inherits from this wrapper's controller
				return str_replace(' ', '_', ucwords(str_replace('/', ' ', str_replace('comps', 'controller', $current_wrapper))));
			}
			
			// One level up
			array_pop($segments);
		}
	}
	
	/**
	 * Set route for given component
     *
	 * @param	array	component data
	 *						$comp[1] = directory
	 *						$comp[2] = controller name
	 * @param	string  tree name
	 * @param	string	locale
	 * @return	route
	 * @access	protected
	 * @static
	 */
	protected static function _route($comp, $path, $locale = NULL)
	{
	    $trail = '/';
	    
	    // Catch all routes
	    if($comp[2] === Component::DHANDLER)
	    {
	        // Uri is just the directory without trailing slash
	        $uri = substr($comp[1], 0, -1);
	        
	        // Trailing slash must exists only if directory's not null
    	    $trail = empty($comp[1]) ? '' : $trail;
	    }
	    // Normal controller routes
	    else
	    {
    	    $uri = $comp[1].$comp[2];
	    }
	    
	    // If locale is set
	    if($locale !== NULL)
	    {
	        // Translate the uri
	        $uri = I18n::instance($locale)->uri($uri);
	    }

	    // Set the route matching given comp
	    $route = new Route($uri.'('.$trail. '<params>)', array('params' => '.*?'));
		    
	    // Set default values and return route
		return $route->defaults(array(
			'directory' => $path.'/'.$comp[1],
			'controller' => $comp[2],
			'action' => 'index'
		));
	}

}   // End Component_Core