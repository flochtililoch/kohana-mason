<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Route Class
 * Extends Kohana Route class
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Route extends Kohana_Route
{

	/**
	 * Retrieves all named routes.
	 *
	 *     $routes = Route::all();
	 *
	 * @return  array  routes by name
	 */
	public static function all()
	{
		return Route::$_routes + Kohana::$tree['routes']['external'];
	}
	
}	// End Component_Route