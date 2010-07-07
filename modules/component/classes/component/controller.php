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

    		// If comp has attached views
    		if(class_exists($comp))
    		{
				// Work with controller's entities only
				$entities = Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name];
				
				// Find which class to use to load the view
				$view_engine = $comp::$_view_engine ? $comp::$_view_engine : Kohana::config('view.engine');
				
				$path = $comp::$_path.'/'.$comp::$_directory.'_'.$comp::$_name;
				
				if(array_key_exists('views', Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name]))
				{
					// Build view object
					$this->request->response = new $view_engine(
						$path.'/views/'.$entities['views']['name'],
						$comp,
						$entities['views']['cache_id']
						);
				}
				
				// If the combination of assets for this type of execution has not been cached yet
				if(! (Kohana::$caching === TRUE && $assets = Kohana::cache('assets_'.$path)) )
				{
					// Retrieve scripts and stylesheets for this specific component
					$assets = array();
					foreach(array('scripts', 'stylesheets') as $type)
					{
						if(array_key_exists($type, $entities))
						{
							// Make sure files are sorted in the right order
							ksort($entities[$type]);
							$assets[$type][$comp::$_assets_cache_key] = array($path.'/'.$type.'/' => array_values($entities[$type]));
						}
					}
					
					// Merge component's assets with main request current assets
					Request::$instance->assets = array_merge_recursive(Request::$instance->assets, $assets);
					
					if(Kohana::$caching === TRUE)
					{
						// Assets cache never expire
						Kohana::cache('assets_'.$path, $assets, 0);
					}
				}

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
	
}	// End Component_Controller