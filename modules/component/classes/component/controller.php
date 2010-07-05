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
				
				// Cache key is used to retrieve list of assets to load for this component
				$cache_key = $this->_cache_key !== NULL ? $this->_cache_key : $path;
				
				// If the combination of assets for this type of execution has not been cached yet
				if(!(property_exists($this, '_assets') && is_array($this->_assets) && array_key_exists($cache_key, $this->_assets)))
				{
					// Store scripts and stylesheets in main request for separate loading
					foreach(array('scripts', 'stylesheets') as $type)
					{
						if(array_key_exists($type, $entities))
						{
							// Make sure files are sorted in the right order
							ksort($entities[$type]);
							Request::$instance->{$type}[] = array($path.'/'.$type.'/' => array_values($entities[$type]));
						
							// Cache assets content in a file named after the SHA of the assets array
							// Set in the controller class file (in the cache) a new private property
							// an associative array having the sha of the assets as a key
							// and an array of sha's of parametres as value
							// Next Requests : 
							// do not loop thru assets, just sha the parametres and do something with it to get the wanted file...damn it!...
							// 
						}
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