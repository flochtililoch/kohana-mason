<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Class Context
 * Extends PHPTAL Context class
 *
 * @package    PHPTAL
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
		// TAL helpers
		if($varname === 'NULL')	 return NULL;
		if($varname === 'TRUE')  return TRUE;
		if($varname === 'FALSE') return FALSE;
		
		// Get controller class name of current tal object
		$controller = $this->controller;
		
		while($controller)
		{
			// If var exists in controller context
			if($controller::context($varname) !== NULL)
			{
				return $controller::context($varname);
			}
			
			// If method exists in controller instance
			if(method_exists($controller::instance(), $varname))
			{
				return $controller::instance()->{$varname}();
			}
			
			// If property exists in controller instance
			if(property_exists($controller::instance(), $varname))
			{
				return $controller::instance()->{$varname};
			}

			// If nothing's found yet, look in parent's request controller
			$controller = $controller::instance()->request->parent;			
		}
		
		// Fall back to PHPTAL default context getter
		return parent::__get($varname);
	}

}	// End Tal_Context