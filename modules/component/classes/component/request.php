<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's Request Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Request extends Kohana_Request
{

	/**
     * Request parent controller
     *
	 * @access	public
	 */
    public $parent = NULL;

	/**
     * Component's input arguments
     *
	 * @access	public
	 */
	public $args = NULL;
	
	/**
     * Request's assets container
     *
	 * @access	public
	 */
	public $assets = array(
		'scripts' => array(),
		'stylesheets' => array()
		);

	/**
	 * Creates a new request object for the given URI.
	 *
	 * @param   string  URI of the request
	 * @param   string  request creator controller class name
	 * @return  Request
	 * @access	public
	 * @static
	 */
	public static function factory($uri = TRUE, Cache $cache = NULL, $injected_routes = array(), $controller = NULL, $args = NULL)
	{
		$injected_routes += Request::$initial ? Kohana::$tree['routes']['internal'] : array();

		// Create new request instance
		$r = parent::factory($uri, $cache, $injected_routes);

		// Store the request creator controller class name
		if(class_exists($controller))
		{
			$r->parent = $controller::instance()->base_comp();
		}
		
		// Store arguments
		$r->args = $args;
		
		// Store uri segments
		$r->_params = array_key_exists('params', $r->_params) ? preg_split('/\//', $r->_params['params']) : $r->_params;
		
		// Returns request instance
		return $r;
	}
	
	/**
	 * Retrieves the next uri param and remove it from the stack
	 *
	 * @return  string
	 */
	public function shift_param()
	{
		// Return first array's element
		return array_shift($this->_params);
	}

}	// End Component_Request