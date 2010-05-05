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
    const ENGINE = 'View';

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
	
	/**
	 * Sets the initial view filename and local data. Views should almost
	 * always only be created using [View::factory].
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  view filename
	 * @param   mixed	array of variables (used by default kohana core & modules) or static class name (used by component module)
	 * @return  void
	 * @uses    View::set_filename
	 */
	public function __construct($file = NULL, $data = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if ( $data !== NULL )
		{
			// Test if view is loaded from Component controller
			if(is_string($data) && class_exists($data) && is_array($data::get('_process')))
			{
				$data = $data::get('_process');
			}

			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

}	// End Component_View