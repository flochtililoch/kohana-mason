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
	 * Asset config container
	 *
	 * @access	public
	 */
	public static $config = NULL;

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
	 * Load config
	 *
	 * @return	config	config object
	 * @access	public
	 */
	public static function config()
	{
		if(empty(Asset::$config))
		{
			Asset::$config = Kohana::config('asset');
		}
		return Asset::$config;
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
	
		$files = array();
		
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
						if(array_key_exists($asset, $tmp_used_entities))
						{
							$used_entities[$asset] = $tmp_used_entities[$asset];
						}
					}
				}

				// Queue default behaviour entities
				$used_entities += $entities[$type][''];

				// Include assets files
				foreach($used_entities as $entity)
				{
					// Find which CDN to use
					$cdn = property_exists($comp, 'cdn') ? Request::$instance->cdn[$comp::$cdn] : Asset::CDN($path.'/'.$type.'/'.$entity['name']);

					$files = array_replace_recursive($files, array(
						$type => array(
							$context['assets_cache_key'] => array(
								$path.'/'.$type.'/'.$entity['name'] => array(
									'host' => $cdn,
									'file' => $path.'/'.$type.'/'.$entity['name'],
									'cache_id' => $entity['cache_id']
									)))));
				}
			}
		}

		return $files;
	}
	
	/**
	 * Run packing
	 *
	 * @param	string	destination where to write packed files
	 * @return	array	paths of written packed files
	 * @access	public
	 */
	public static function pack($assets)
	{
		// Get packed files names
		$packed_files = array_fill_keys(array_keys(Asset::config()->types), NULL);
		foreach(Asset::config()->types as $type => $file_extension)
		{
			if(!isset($assets[$type]))
			{
				continue;
			}

			foreach($assets[$type] as $group => $files)
			{
				// File name is a hash of serialized entities
				$filename = sha1(serialize($files));
				$file = Asset::config()->dest.$filename.'.'.Asset::config()->types[$type];
				$cache_id = file_exists($file) ? filemtime($file) : NULL;

				$packed_files[$type][$group] = array($filename => array(
					'host' => Asset::CDN($filename),
					'file' => $filename,
					'cache_id' => $cache_id
					));
			}
		}

		$packed = array_fill_keys(array_keys(Asset::config()->types), NULL);

		// Loop through all types of files
		foreach($assets as $type => $files_groups)
		{
			$_files = array();
			$packed[$type] = array_fill_keys(array_keys($files_groups), NULL);

			// Loop through all files
			foreach($files_groups as $cache_key => $files)
			{
				foreach($files as $file)
				{
					// get current file realpath
					$file = Kohana::find_file('comps', $file['file'], Asset::config()->types[$type]);

					// get file last modification time
					$_files[$file] = filemtime($file);

					// concat file content with other same type files
					$packed[$type][$cache_key] .= file_get_contents($file);					
				}

				// Replace relative URLs into absolute if CDNs provided
				if(count(Request::instance()->cdn))
				{
					// convert relative urls into absolute urls within url() statements
					// NOTE : path specified in behavior (& -ms-behavior) property need to be relative. Therefore it should not be modified
					$packed[$type][$cache_key] = preg_replace_callback(
													'/(?<!behavior):(.*)?url\((["\'])?(.*[^"\'])(["\'])?\)/',
													create_function(
														'$matches',
														'return ":".$matches[1]."url(".$matches[2].Asset::CDN($matches[3]).$matches[3].$matches[2].")";'
														),
													$packed[$type][$cache_key]
													);

					// convert relative urls into absolute urls within src="" statements
					$packed[$type][$cache_key] = preg_replace_callback(
													'/src="(.*?)"/',
													create_function(
														'$matches',
														'return "src=\"".Asset::CDN($matches[1]).$matches[1]."\"";'
														),
													$packed[$type][$cache_key]
													);
				}

				$filename = key($packed_files[$type][$cache_key]).'.'.Asset::config()->types[$type];
				$tmp_file = Asset::config()->dest.''.$filename;
			
				// Create assets cache dir if not present
				if(!is_dir(Asset::config()->dest))
				{
					// Create directory
					mkdir(Asset::config()->dest, 0777, TRUE);

					// Set permissions (must be manually set to fix umask issues)
					chmod(Asset::config()->dest, 0777);
				}
			
				// Write content in a temporary file
				file_put_contents($tmp_file, $packed[$type][$cache_key]);
			
				// Run the packer
				//$cl = sprintf(Asset::config()->packer_cl, Asset::config()->packer_bin, Asset::config()->dest.$filename, $tmp_file);
				//system($cl, $out);
				//unlink($tmp_file);
			}
		}

		return $packed_files;
	}

}