<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controller Class
 * Extends Kohana Controller class
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Controller extends Kohana_Controller
{

    /**
	 * Controller default action method
	 *
	 * @return	void
	 * @access	public
	 */
	public function action_index(){}
	
	/**
	 * Controller after action method
	 *
	 * @return	View factory result
	 * @access	public
	 */
	public function after()
	{
		// Get base component class name
	    $base_comp = $this->base_comp();

	    // If there's still some views to process
		if(count($base_comp::$_wrapping_chain))
	    {
    	    // Take the first component class name out of the components wrapping chain
    	    $comp = array_shift($base_comp::$_wrapping_chain);

			// Entities' path				
			$path = $comp::$_path.'/'.$comp::$_directory.'_'.$comp::$_name;

			// If the combination of assets for this type of execution has not been cached yet
			if(! (Kohana::$caching === TRUE && $assets = Kohana::cache('assets_'.$path.'_'.$comp::$_assets_cache_key)) )
			{
				// Load assets for this specific component
				$assets = Asset::load($comp);
				
				if(Kohana::$caching === TRUE)
				{	
					// Assets cache never expire
					Kohana::cache('assets_'.$path.'_'.$comp::$_assets_cache_key, $assets, 0);
				}
			}

			// Merge component's assets with main request current assets
			if(!in_array($comp::$_assets_cache_key, $comp::$_assets_pushed))
			{
				Request::$instance->assets = array_merge_recursive(Request::$instance->assets, $assets[$comp::$_assets_cache_key]);
				
				// Flag assets as pushed in the stack
				$comp::$_assets_pushed[] = $comp::$_assets_cache_key;
			}

			// If comp has attached views
			if(array_key_exists('views', Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name]))
			{
				// if $_view_file has been defined in comp, look to the file in user's views, otherwise in default views
				$view_type = $comp::$_view_file === 0 ? '' : 'user';
				
				// Work with controller's views only
				$views = Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name]['views'][$view_type][$comp::$_view_file];
				
				// Find which class to use to load the view
				$view_engine = $comp::$_view_engine ? $comp::$_view_engine : Kohana::config('view.engine');
				
				// Build view object
				$this->request->response = new $view_engine(
					$path.'/views/'.$views['name'],
					$comp,
					$views['cache_id']
					);
					
				// Return view result
    			return $this->request;
			}
    		else
    		{
    			// Call next component view processing
    			return $this->after();
    		}
    	}
	}
	
	/**
	 * Load a comp via request factory
	 *
	 * @return	Response from Request factory
	 * @access	public
	 */
	public function comp($uri)
	{
		return Request::factory($uri)->execute()->response;
	}
	
	/**
	 * Return protected access variables
	 * that defines the context of given comp
	 *
	 * @return	Array of properties
	 * @access	public
	 */
	public static function get_context($comp)
	{
		return array(
			'path'				=> $comp::$_path,
			'directory'			=> $comp::$_directory,
			'name'				=> $comp::$_name,
			'view_file'			=> $comp::$_view_file,
			'assets'			=> $comp::$_assets,
			'assets_cache_key'	=> $comp::$_assets_cache_key
			);
	}
	
}	// End Component_Controller