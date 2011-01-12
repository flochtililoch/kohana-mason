<?php
defined('SYSPATH') OR die('No direct access allowed.');

// %1$s
class %2$s extends %3$s
{

	// <attr/> section
	%4$s
	// --------------
	
	public function before()
	{
		// <attr process="true"/> section
		%5$s
		// -------------
		parent::before();
	}
	
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
		%6$s
		// -------------
	
		// <process/> section
		%7$s
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
	 * @param	boolean	if set to true, will only check if variable is set
	 * @return	mixed	context variable value or boolean is isset param set to true
	 */
	public static function context($varname, $isset = FALSE)
	{
		// Look for requested variable in current controller context
		if(array_key_exists($varname, self::$_process))
		{
			return ($isset === TRUE ? TRUE : self::$_process[$varname]);
		}
		return ($isset === TRUE ? FALSE : NULL);
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