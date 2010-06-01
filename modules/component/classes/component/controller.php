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
    		if(class_exists($comp) && array_key_exists('views', Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name]))
    		{
				// Work with controller's views only
				$views = Kohana::$tree['comps'][$comp::$_path][$comp::$_directory][$comp::$_name]['views'];

				// Find locale / channel informations
				$language = isset($views[I18n::language()]) ? I18n::language() : 'def';
				$country = isset($views[$language][I18n::country()]) ? I18n::country() : 'def';
				$channel = isset($views[$language][$country][I18n::channel()]) ? I18n::channel() : 'def';
				
				// Set template file path
				$file = $comp::$_path.'/'.$comp::$_directory.'_'.$comp::$_name.'/views/'.$views[$language][$country][$channel]['name'];
		
				// Set cache identifier
				$cache_id = $views[$language][$country][$channel]['cache_id'];
				
				// Find which class to use to load the view
				$view_engine = $comp::$_view_engine ? $comp::$_view_engine : Kohana::config('view.engine');
				
				// Build view object
				$this->request->response = new $view_engine($file, $comp, $cache_id);
			
				// Return view result
    			return $this->request->response;
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