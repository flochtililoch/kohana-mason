<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Asset Manager Class
 * Load & pack assets files
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Asset
{
	/**
	 * Asset instance container
	 *
	 * @access	public
	 */
	public static $instance = NULL;
	
	/**
	 * files list to process
	 *
	 * @access	private
	 */
	private $_files = array();
	
	/**
	 * Index used to spread CDNs over resources
	 *
	 * @access	private
	 */
	private $_cdn_idx = NULL;
	
	/**
	 * Key used to retrieve cached resources and their corresponding CDN
	 *
	 * @access	private
	 */
	private $_resources_cache_key = NULL;
	
	/**
	 * Cache storing resources and their corresponding CDN
	 * Used by all components
	 *
	 * @access	private
	 */
	private $_resources = array();
	
	/**
	 * List of packed files
	 *
	 * @access	private
	 */
	private $_packed_files = array();
	
	/**
	 * Asset main instance
	 *
	 * @return	asset	Asset object
	 * @access	public
	 */
	public static function instance()
	{
		if(Asset::$instance === NULL)
		{
			Asset::$instance = new Asset();
			Asset::$instance->_config = Kohana::config('asset');
			Asset::$instance->_resources_cache_key = 'assets_ressources_'.Kohana::$environment.'_'.Kohana::$locale.'_'.Kohana::$locale.'_'.Kohana::$channel;
			Asset::$instance->_resources = Kohana::cache(Asset::$instance->_resources_cache_key);
			if(Asset::$instance->_resources === NULL)
			{
				Asset::$instance->_resources = array();
			}
		}
		return Asset::$instance;
	}
	
	/**
	 * Returns list of loaded files
	 * 
	 * @return	array
	 * @access	public
	 */
	public function files()
	{
		return $this->_files;
	}
	
	/**
	 * Returns packed filenames
	 * 
	 * @return	array
	 * @access	public
	 */
	public function packed_files()
	{
		if(empty($this->_packed_files) && !empty($this->_files))
		{
			$this->_packed_files = array_fill_keys(array_keys($this->_config->types), NULL);
			foreach($this->_config->types as $type => $file_extension)
			{
				// File name is a hash of serialized entities
				$filename = sha1(serialize($this->_files[$type]));
				$file = $this->_config->dest.$filename.'.'.$this->_config->types[$type];
				$cache_id = file_exists($file) ? filemtime($file) : NULL;
				
				$this->_packed_files[$type] = array(0 => array(
					'host' => NULL,
					'file' => $filename,
					'cache_id' => $cache_id
					));
			}
		
		}
		
		return $this->_packed_files;
	}

	/**
	 * Returns which CDN to use for a given resource
	 * CDNs can be spread among over resources to help parallel download
	 * Each resource can have just one CDN as well
	 *
	 * @param	string	resource
	 * @return	string	CDN to be used
	 * @access	public
	 */
	public static function CDN($res)
	{
		// We should have at least one CDN defined in the list
		if(!count(Request::instance()->cdn))
		{
			return FALSE;
		}
		// If this resource already has associated CDN, don't reprocess
		if(!array_key_exists($res, Asset::$instance->_resources))
		{
			// Shift to the next CDN in the list
			Asset::$instance->_cdn_idx = (Asset::$instance->_cdn_idx !== NULL && (Asset::$instance->_cdn_idx < (count(Request::instance()->cdn) - 1)) ? Asset::$instance->_cdn_idx + 1 : 0);
			
			// Cache processed resource
			Asset::$instance->_resources[$res] = Asset::$instance->_cdn_idx;
		}
		
		// Return the CDN to be used
		return Request::instance()->cdn[Asset::$instance->_resources[$res]];
	}
	
	/**
	 * Load assets for given comp
	 * 
	 * @param	string	Component class in which to look for assets files
	 * @return	Asset instance
	 * @access	public
	 */
	public function load($comp)
	{
		// Load component context
		$context = Controller::get_context($comp);
		
		// Work with controller's entities only
		$entities = Kohana::$tree['comps'][$context['path']][$context['directory']][$context['name']];
		
		// Component's path
		$path = $context['path'].'/'.$context['directory'].'_'.$context['name'];
		$this->_files = array();
		
		// Loop trough assets type
		foreach(array('scripts', 'stylesheets') as $type)
		{
			if(array_key_exists($type, $entities))
			{
				// Make sure files are sorted in the right order
				ksort($entities[$type]);
				
				// If there's user defined entities and comp need to load some
				$used_entities = array();
				if(is_array($context['assets']) && count($context['assets']) && array_key_exists('user', $entities[$type]))
				{
					// User defined entities get priority over default behviour ones
					$tmp_used_entities = array_intersect_key($entities[$type]['user'], array_flip($context['assets']));

					// Sort used entities into user's order
					foreach($context['assets'] as $asset)
					{
						if(!array_key_exists($asset, $tmp_used_entities))
						{
							throw new Kohana_Exception('Unable to find required asset: :asset',
								array(':asset' => $asset));
						}

						$used_entities[$asset] = $tmp_used_entities[$asset];
					}
				}

				// Queue default behaviour entities
				$used_entities += $entities[$type][''];

				// Include assets files
				foreach($used_entities as $entity)
				{
					// Find which CDN to use
					$cdn = property_exists($comp, 'cdn') ? Request::$instance->cdn[$comp::$cdn] : Asset::CDN($path.'/'.$type.'/'.$entity['name']);

					$this->_files[$type][$path.'/'.$type.'/'.$entity['name']] = array(
						'host' => $cdn,
						'file' => $path.'/'.$type.'/'.$entity['name'],
						'cache_id' => $entity['cache_id']
						);
				}
			}
		}

		return $this;
	}
	
	/**
	 * Run packing
	 *
	 * @param	string	destination where to write packed files
	 * @return	array	paths of written packed files
	 * @access	public
	 */
	public function pack()
	{
		$packed = array_fill_keys(array_keys($this->_config->types), NULL);
		
		// Loop through all types of files
		foreach($this->_files as $type => $files)
		{
			$_files = array();
			
			// Loop through all files
			foreach($files as $file)
			{
				// get current file realpath
				$file = Kohana::find_file('comps', $file['file'], $this->_config->types[$type]);

				// get file last modification time
				$_files[$file] = filemtime($file);
				
				// concat file content with other same type files
				$packed[$type] .= file_get_contents($file);
			}
			
			// Replace relative URLs into absolute if CDNs provided
			if(count(Request::instance()->cdn))
			{
				// convert relative urls into absolute urls within url() statements
				// NOTE : path specified in behavior (& -ms-behavior) property need to be relative. Therefore it should not be modified
				$packed[$type] = preg_replace_callback(
									'/(?<!behavior):(.*)?url\((["\'])?(.*[^"\'])(["\'])?\)/',
									create_function(
										'$matches',
										'return ":".$matches[1]."url(".$matches[2].Asset::CDN($matches[3]).$matches[3].$matches[2].")";'
										),
									$packed[$type]
									);

				// convert relative urls into absolute urls within src="" statements
				$packed[$type] = preg_replace_callback(
									'/src="(.*?)"/',
									create_function(
										'$matches',
										'return "src=\"".Asset::CDN($matches[1]).$matches[1]."\"";'
										),
									$packed[$type]
									);
			}
			
			// Get name of packed file
			$files = $this->packed_files();
			$file = $files[$type][0]['file'].'.'.$this->_config->types[$type];
			$tmp_file = $this->_config->dest.'tmp_'.$file;
			
			// Write content in a temporary file
			file_put_contents($tmp_file, $packed[$type]);
			
			// Run the packer
			$cl = sprintf($this->_config->packer_cl, $this->_config->packer_bin, $this->_config->dest.$file, $tmp_file);
			system($cl);
			
			// Remove temporary file
			unlink($tmp_file);
		}

		return $this;
	}
	
	// If non-dev env., pack assets in one single file named after the assets array md5ed
	// Each comp would then have a single file, compressed.
	// One top level caching should be then done
	// Need to think about what would be best suited for performance :
	// one single file based on the combination of all components assets ?
	// or one file per autohandler level ?
	// or a combination of both ?
	// IDEA : a component key, pack_assets, set the name of the file in which this component's
	//        and all its 'childs' assets will be packed in. If set to false, will explicitly exclude
	//		  this component assets from its parent packed file.
	//        i.e. : /foo/bar/autohandler has the key 'main'.
	//               Assets from /foo/autohandler, /autohandler and all components loaded from sub request 
	//               will be packed in file 'main'
	// How it works :
	// assets files are queued from top to bottom (/, /foo, /foo/bar ...), in the order their comps are executed
	// Before loading assets in the view, need to loop through Request::$instance->assets, find from each asset
	// its component, get the pack_assets key; if false, leave the current asset in the list,
	// if set, take the current asset content and concat it in the string name after the key value, then remove
	// the current asset from the array.
	// Then pack all strings into files

}