<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Component Manager Class
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
	 * Type of entities files within the tree
	 * @access	protected
	 * @static
	 */
	protected static $_entities_types = array(
		'xhtml' => 'views',
		'css'	=> 'stylesheets',
		'js'	=> 'scripts',
		'png'	=> 'images',
		'gif'	=> 'images',
		'jpg'	=> 'images'
		);

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
		$cache_dir = CACHEPATH.'classes/controller/'.$path.'/'.$directory;

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
		
		// Find inheritance : if a parent autohandler exists, then class inherits from its parent
		$extends = 'Controller';
		if(Kohana::$tree['comps'][$path][$directory][$controller]['inherit'] !== NULL)
		{
			// Extends from Controller by default for orphans comps
			$extends = Kohana::$tree['comps'][$path][$directory][$controller]['inherit'];
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
					// Check if context key defined
					$context = isset($key->attributes()->context) && $key->attributes()->context->__toString() === 'global' ? '$this->%2$s = %3$s;' : 'self::$_process[\'%2$s\'] = %3$s;';
					$process[] = sprintf($context, $class, $key->attributes()->name->__toString(), $key[0]);
				}
			}
		}

		// Set defaults attributes
		$attributes = array(
			'_path' 			=> 'protected static $_path = \''.$path.'\';',													// Component origin in tree
			'_directory'		=> 'protected static $_directory = \''.addslashes($directory).'\';',							// Component path
			'_name'				=> 'protected static $_name = \''.$controller.'\';',											// Component name
			'_view_file'		=> 'protected static $_view_file = 0;',															// Component view file name
			'_view_engine'		=> 'protected static $_view_engine = '.($view_engine !== NULL ? $view_engine : 'NULL').';',		// View type
			'_process'			=> 'protected static $_process = array();',														// View variables container
			'_instance'			=> 'protected static $_instance = NULL;',														// Controller instance container
			'_wrapping_chain' 	=> 'protected static $_wrapping_chain = NULL;',													// Wrapping chain container
			'_assets'			=> 'protected static $_assets = array();',														// Component's assets
			'_assets_cache_key'	=> 'protected static $_assets_cache_key = \'\';',												// Component's assets cache key
			'_assets_pushed' 	=> 'protected static $_assets_pushed = array();'												// Component's assets pushed flag
		);
		
		// Attributes to be processed
		$attributes_process = array();
		
		// Compile attributes
		foreach($xml->attr as $keys)
		{
			foreach($keys->key as $key)
			{
				// Retrieve all XML tags within <attr/>
				$tag_attributes = array();
				foreach($key->attributes() as $name => $value)
				{
					$tag_attributes[$name] = $value->__toString();
				}
				// If the current tag contains a 'name' attribute
				if(array_key_exists('name', $tag_attributes))
				{
					$visibility = array_key_exists('visibility', $tag_attributes) ? $tag_attributes['visibility'] : NULL;
					
					// If we're trying to redefine a default attribute without specifying its visibility, use its default visibility
					if(array_key_exists($tag_attributes['name'], $attributes) && $visibility === NULL)
					{
						preg_match('/^(.*) \$'.$tag_attributes['name'].' = .*;$/', $attributes[$tag_attributes['name']], $visibility);
						$visibility = $visibility[1];
					}
					
					if(array_key_exists('process', $tag_attributes))
					{
						$accessor = NULL;
						if(preg_match('/(.*)?static(.*)?/', $visibility))
						{
							$accessor = 'self::$';
						}
						else
						{
							$accessor = '$this->';
						}
						$attributes_process[$tag_attributes['name']] = sprintf(
							'%1$s%2$s = %3$s;',
							$accessor,
							$tag_attributes['name'],
							$key[0]
							);
						
						// Make sure the attribute will be set to NULL in class property declaration
						$key[0] = 'NULL';
					}
					

					$attributes[$tag_attributes['name']] = sprintf(
						'%1$s $%2$s = %3$s;',
						$visibility !== NULL ?
							$visibility :
							(substr($tag_attributes['name'], 0, 1) === '_' ? 'protected' : 'public'),
						$tag_attributes['name'],
						$key[0]
						);
					}
			}
		}
		
		$reserved_methods_names = array( 'before', 'action_index', 'get', 'context', 'instance', 'base_comp');
		$methods_code = array();
		foreach($xml->methods as $methods)
		{
			foreach($methods->method as $method)
			{
				// Retrieve all XML tags within <attr/>
				$tag_attributes = array();
				foreach($method->attributes() as $name => $value)
				{
					$tag_attributes[$name] = $value->__toString();
				}
				// If the current tag contains a 'name' attribute
				if(array_key_exists('name', $tag_attributes))
				{
					$name = $tag_attributes['name'];
					if(in_array($name, $reserved_methods_names))
					{
						throw new Kohana_Exception('Method name :name reserved (reserved names = '.implode(', ', $reserved_methods_names).'). Pick another one.',
							array(':name' => $name));
					}
					else
					{
						// if visibility attribute is set
						$visibility = 'private';
						if(array_key_exists('visibility', $tag_attributes))
						{
							$visibility = $tag_attributes['visibility'];
						}
						
						// if args attribute is set
						$args = '';
						if(array_key_exists('args', $tag_attributes) && $tag_attributes['args'] !== NULL)
						{
							$args = '$'.implode(', $', explode(',', $tag_attributes['args']));
						}
						$methods_code[] = $visibility.' function '.$name.'('.$args.')'.chr(13).chr(9).'{'.chr(13).chr(9).chr(9).trim($method->__toString()).chr(13).chr(9).'}';
					}
				}
			}
		}

		// Write php file
		file_put_contents(
			$compiled_comp,
			'<?php'.sprintf( 
				substr(file_get_contents(MODPATH.'component/templates/classes/controller'.EXT), 5, -1),		// comp template
				$xml_comp,															                // xml comp path
				$class,																				// class name
				$extends,																			// extended from
				implode($attributes, chr(13).chr(9)),												// attributes
				implode($attributes_process, chr(13).chr(9)),										// attributes
				trim($xml->php),																	// php code
				implode($process, chr(13).chr(9).chr(9)),					                        // view variables
				implode($methods_code, chr(13).chr(9))					                        // controller methods
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
			Component::$_tree['routes']['external'] = array();

		    // Loop through comps tree 
		    foreach(array_unique(array_merge(Component::$_internal_trees, Component::$_external_trees)) as $dir)
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
		$routes = array('internal' => $routes_defaults, 'external' => $routes_defaults);
		
		// List of allowed entities' names
		$allowed_entities = array(
			I18n::language().'.'.I18n::country().'.'.I18n::channel(),
			I18n::language().'.'.I18n::country(),
			I18n::language(),
			'def',
			);

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
						$routes['external'][($controller === Component::DHANDLER ? 'catchall' : 'controller')][$tree.'/'.$directory.$controller] = Component::_route($comp, $tree, Kohana::$locale);
					}
					if(in_array($path, Component::$_internal_trees))
					{
						$routes['internal'][($controller === Component::DHANDLER ? 'catchall' : 'controller')][$tree.'/'.$directory.$controller] = Component::_route($comp, $tree);
					}
				}
				Component::$_tree['comps'][$tree][$directory][$controller]['inherit'] = Component::_inherit($comp, $comps, $path);
				Component::$_tree['cache_ids']['controller_'.str_replace('/', '_', $tree.'/'.$directory.$controller)] = filemtime($file);
			}
			
			// All other files, if they match with declared entity types
			if(preg_match('/^'.preg_quote($path, '/').'\/(.*\/)?_([a-zA-Z0-9]*)\/('.implode('|', Component::$_entities_types).')\/(.*)?\.('.implode('|', array_keys(Component::$_entities_types)).')$/', $entry, $entity))
			{
				$directory = $entity[1];
			    $controller = $entity[2];
				$entity_type = $entity[3];
				$entity_file = $entity[4].($entity_type === 'images' ? '.'.$entity[5] : '');

				// All non-defaults resources are retrieved and indexed using their file name as key under 'user' array
				$entity_path = 'user';
				$entity_key = $entity_file;
				$entity_value = array('name' => $entity_file, 'cache_id' => filemtime($file));
				
				// Entity is a 'default' resource?
				if(preg_match('/^('.implode('|', $allowed_entities).')$/', $entity_file))
				{
					// Entities other than view are stored in reversed order
					$entity_key = array_search($entity_file, ($entity_type === 'views' ? $allowed_entities : array_reverse($allowed_entities)));
					$entity_path = '';
				}
				
				// Init storage container if not yet existing
				if(!isset(Component::$_tree['comps'][$tree][$directory][$controller][$entity_type][$entity_path]))
				{
					Component::$_tree['comps'][$tree][$directory][$controller][$entity_type][$entity_path] = array();
				}
				
				// And store the entity
				Component::$_tree['comps'][$tree][$directory][$controller][$entity_type][$entity_path] += array($entity_key => $entity_value);;
			}
		}
		
		// Once all comps have been processed, go through default entities and reorder them
		// DIRTY !!! could use array_walk + recursive function maybe?
		foreach(Component::$_tree['comps'][$tree] as $directory => $controller)
		{
			foreach($controller as $name => $controller_data)
			{
				foreach($controller_data as $type => $entry)				
				{
					if(in_array($type, Component::$_entities_types))
					{
						foreach($entry as $entity_path => $entity)
						{
							if($entity_path === '')
							{
								ksort(Component::$_tree['comps'][$tree][$directory][$name][$type][$entity_path], SORT_NUMERIC);
								Component::$_tree['comps'][$tree][$directory][$name][$type][$entity_path] = array_values(Component::$_tree['comps'][$tree][$directory][$name][$type][$entity_path]);
							}
						}
					}
				}
			}
		}

		Component::$_tree['routes']['external'] += $routes['external']['controller'] + array_reverse($routes['external']['catchall']);
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