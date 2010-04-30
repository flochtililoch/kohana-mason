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
	 *
	 * @access	protected
	 */
	protected static $_path = '%5$s';
	
	/**
	 * Component path
	 *
	 * @access	protected
	 */
	protected static $_directory = '%6$s';
	
	/**
	 * Component name
	 *
	 * @access	protected
	 */
	protected static $_name = '%7$s';
    
	/**
	 * View type
	 *
	 * @access	protected
	 */
	protected static $_view_engine = %8$s;
	
	/**
	 * View variables container
	 */
	protected static $_process = array();
	
	/**
	 * Controller instance container
	 *
	 * @access	protected
	 */
	protected static $_instance = NULL;
	
	/**
	 * Wrapping chain container
	 *
	 * @access	protected
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
		
		return NULL;

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