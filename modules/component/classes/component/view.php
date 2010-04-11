<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's View Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_View extends Kohana_View
{

    /**
     * Engine identifier constants
     */
    const ENGINE = 1;

    /**
	 * Returns a new View object.
	 *
	 * @param   string  Controller class name
 	 * @return  View
	 * @access	public
	 * @static
	 */
	public static function load($comp)
	{
		// Get information from controller
		$path = $comp::get('_path');
		$directory = $comp::get('_directory');
		$controller = $comp::get('_name');
		$engine = $comp::get('_view_engine');
		
		// Work with controller's views only
		$views = Kohana::$tree['comps'][$path][$directory][$controller]['views'];

		// Find locale / channel informations
		$language = isset($views[I18n::language()]) ? I18n::language() : 'def';
		$country = isset($views[$language][I18n::country()]) ? I18n::country() : 'def';
		$channel = isset($views[$language][$country][I18n::channel()]) ? I18n::channel() : 'def';
		
		// Set template file path
		$file = $path.'/'.$directory.'_'.$controller.'/'.$views[$language][$country][$channel]['name'];

		// TAL view engine required ?
		if($engine === Tal::ENGINE)
	    {
	        // Instanciate Tal class
	        $view = new Tal($file, $comp);

			// Set cache identifier
			$view->setCacheId($views[$language][$country][$channel]['cache_id']);

	        // Pass the translator object to the template manager
            $view->setTranslator(I18n::instance());

	        // Return view object
    		return $view;
	    }
		// Default Kohana view engine required ?
		elseif($engine === View::ENGINE || $engine === NULL)
		{
			return new View($file, $comp::get('_process'));
		}
	}
	
	/**
	 * Sets the view filename.
	 *
	 * @throws  View_Exception
	 * @param   string  filename
	 * @return  View
	 * @access	public
	 */
	public function set_filename($file)
	{
		if (($path = Kohana::find_file('comps', $file, 'xhtml')) === FALSE)
		{
			if (($path = Kohana::find_file('views', $file)) === FALSE)
			{
				throw new Kohana_View_Exception('The requested view :file could not be found', array(
					':file' => $file,
				));
			}
		}
		
		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

}	// End Component_View