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
	 * Load assets for given comp and returns an array of paths
	 * 
	 * @return	
	 * @access	public
	 * @static
	 */
	public static function load($comp)
	{
		$context = Controller::get_context($comp);
		
		// Work with controller's entities only
		$entities = Kohana::$tree['comps'][$context['path']][$context['directory']][$context['name']];
		
		// Component's path
		$path = $context['path'].'/'.$context['directory'].'_'.$context['name'];

		// Retrieve scripts and stylesheets for this specific component
		$assets = array($context['assets_cache_key'] => array());
		
		// Find which CDN to use
		$cdn_key = property_exists($comp, 'cdn') ? $comp::$cdn : key(Request::$instance->cdn);
		
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
					$assets[$context['assets_cache_key']][$type][$path.'/'.$type.'/'.$entity['name']] = array(
						'host' => Request::$instance->cdn[$cdn_key],
						'file' => $path.'/'.$type.'/'.$entity['name'],
						'cache_id' => $entity['cache_id']
						);
				}
			}
		}
		
		return $assets;
	}
	
	
	public function pack($files)
	{
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
}