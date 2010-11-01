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
	public static function factory($uri, $controller = NULL, $args = NULL)
	{
		// Create new request instance
		$r = new Request($uri, Kohana::$tree['routes']['internal']);
		
		// Store the request creator controller class name
		$r->parent = $controller::instance()->base_comp();
		
		// Store arguments
		$r->args = $args;
		
		// Returns request instance
		return $r;
	}
	
	/**
	 * Creates a new request object for the given URI.
	 * Throws an exception when no route can be found for the URI.
	 *
	 * @throws  Kohana_Request_Exception
	 * @param   string  URI of the request
	 * @return  void
	 * @access	public
	 * @static
	 */
	public function __construct($uri, $routes = NULL)
	{
	    // Remove trailing slashes from the URI
		$uri = trim($uri, '/');

		// Load routes
		$routes = Route::all() + ($routes !== NULL ? $routes : Kohana::$tree['routes']['external']);

		foreach ($routes as $name => $route)
		{
			if ($params = $route->matches($uri))
			{
				// Store the URI
				$this->uri = $uri;

				// Store the matching route
				$this->route = $route;

				if (isset($params['directory']))
				{
					// Controllers are in a sub-directory
					$this->directory = $params['directory'];
				}

				// Store the controller
				$this->controller = $params['controller'];

				if (isset($params['action']))
				{
					// Store the action
					$this->action = $params['action'];
				}
				else
				{
					// Use the default action
					$this->action = Route::$default_action;
				}

				// These are accessible as public vars and can be overloaded
				unset($params['controller'], $params['action'], $params['directory']);

				// Store extra params as array
				$params = array_key_exists('params', $params) ? explode('/',$params['params']) : $params;
				
				// Params cannot be changed once matched
				$this->_params = $params;

				return;
			}
		}

		// No matching route for this URI
		$this->status = 404;

		throw new Kohana_Request_Exception('Unable to find a route to match the URI: :uri',
			array(':uri' => $uri));
	}
	
	/**
	 * Retrieves the next uri param and remove it from the stack
	 *
	 * @return  string
	 */
	public function shift_param()
	{
		// Return the full array
		return array_shift($this->_params);
	}

}	// End Component_Request