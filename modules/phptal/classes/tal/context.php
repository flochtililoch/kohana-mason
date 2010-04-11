<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Class Context
 * Extends PHPTAL Context class
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Tal_Context extends PHPTAL_Context
{

	/**
     * Context getter.
     *
     * @return	mixed
	 * @access	public
	 */
    public function __get($varname)
    {
		if($varname === 'NULL')
		{
			return NULL;
		}
		
		if($varname === 'TRUE')
		{
			return TRUE;
		}
		
		if($varname === 'FALSE')
		{
			return FALSE;
		}
		
		// Get controller class name of current tal object
		$controller = $this->controller;
		
		// If var to retrieve is comp object
		if($varname === 'comp')
		{
			return $controller::instance();
		}
		
		// If var to retrieve is request comp object
		if($varname === 'request_comp')
		{
			$controller = $controller::instance()->request->parent;
			return $controller::instance();
		}
		
		// If var exists in controller context
		if($controller::context($varname) !== NULL)
		{
			return $controller::context($varname);
		}
		else
		{
			return parent::__get($varname);
		}
	}

}	// End Tal_Context