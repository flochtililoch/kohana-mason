<?php
defined('SYSPATH') OR die('No direct access allowed.');

// %1$s
class %2$s extends %3$s
{

	// <attr/> section
	%4$s
	// --------------
	
	/**
	 * Component origin in tree
	 */
	protected static $_path = '%5$s';
	
	/**
	 * Component path
	 */
	protected static $_directory = '%6$s';
	
	/**
	 * Component name
	 */
	protected static $_name = '%7$s';
    
	/**
	 * View type
	 */
	protected static $_view_engine = %8$s;
	
	/**
	 * View variables container
	 */
	protected static $_process = NULL;
	
	/**
	 * Controller instance container
	 */
	protected static $_instance = NULL;
	
	/**
	 * Wrapping chain container
	 */
	protected static $_wrapping_chain = NULL;
	
	/**
	 * Controller default action method
	 *
	 * @return	void
	 */
	public function action_index()
	{
		// parent comp is run before its child
		parent::action_index();
		
		// <php/> section
		%9$s
		// -------------
	
		// <process/> section
		%10$s
		// -----------------
		
		// Store current comp name in base component wrapping_chain method
		$base_comp = $this->base_comp();
		$base_comp::$_wrapping_chain[] = __CLASS__;
		
		// Store controller instance
		self::$_instance = $this;
	}

	/**
	 * Return static protected property
	 *
	 * @param	string	property name (without the '$' sign)
	 * @return  mixed	property value
	 */
	public static function get($varname)
	{
		return self::$$varname;
	}
	
	/**
	 * Return variable from current context
	 *
	 * @param	string	context variable name (without the '$' sign)
	 * @return	mixed	context variable value
	 */
	public static function context($varname)
	{
		// Look for requested variable in current controller context
		if(array_key_exists($varname, self::$_process))
		{
			return self::$_process[$varname];
		}
		
		// If variable doesnt exists in current controller context, look for it in its parent
		try
		{
			$controller = self::$_instance->request->parent;
		}
		catch(Exception $e)
		{
			throw new Kohana_Request_Exception('Unable to find \':varname\' variable in context', array(':varname' => $varname));
		}

		return $controller::context($varname);

	}
	
	/**
	 * Return instance stored for this class
	 *
	 * @return  Controller	controller instance
	 */
	public static function instance()
	{
		return self::$_instance;
	}

	/**
	 * Return base comp class name
	 *  
	 * @return  string  class name
	 */
	public function base_comp()
	{
		return get_class();
	}

} // End %2$s