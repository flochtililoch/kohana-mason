<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Tal Class
 * Extends PHPTAL class
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Tal_Core extends PHPTAL
{

	// View engine identifier
	const ENGINE = 11;
	
	/**
	 * Set cache identifier
	 *
	 * NOTICE : useless at the moment.
	 * Would be useful for cache invalidation.
	 * Waiting for PHPTAL PHP 5.3 Namespace implementation.
	 *
	 * @return	Tal
	 * @access	public
	 */
	public function setCacheId($cache_id)
	{
	    $this->_cache_id = $cache_id;
		return $this;
	}
	
	/**
	 * TAL Constructor.
	 *
	 * @param 	string 	Template file path
	 * @param 	string 	Template data
	 * @access	public
	 */
	public function __construct($file = FALSE, $controller = NULL)
	{
		// Find view file
		$this->_path = Kohana::find_file('comps', $file, 'xhtml');

		// Save source filename
		$this->_compiled_file = str_replace(array('_', '.', '/'), array('', '_', '_'), $file).'_'.I18n::locale().'_'.I18n::channel();

		// Create new context object
		$this->_context = new Context();
		
		// Store creator controller name
		$this->_context->controller = $controller;
		
		// If cache dir doesn't exists
		if(!is_dir(CACHEPATH.'views'))
		{
			// Create directory
			mkdir(CACHEPATH.'views', 0777, TRUE);

			// Set permissions (must be manually set to fix umask issues)
			chmod(CACHEPATH.'views', 0777);
		}
		
		// Compiled view go here
		$this->setPhpCodeDestination(CACHEPATH.'views');
	}
		
	/**
	 * Magic method, returns the output of execute(). If any exceptions are
	 * thrown, the exception output will be returned instead.
	 *
	 * @return  string
	 * @access	public
	 */
	public function __toString()
	{
		try
		{
			return $this->execute();
		}
		catch (Exception $e)
		{
			// Display the exception message
			Kohana::exception_handler($e);

			return '';
		}
	}

	/**
	 * Return template function name
	 *
	 * function name is used as base for caching, so it must be unique for
     * every combination of settings that changes code in compiled template
	 * @return  string
	 * @access	public
	 */
	public function getFunctionName()
	{
		return $this->_compiled_file.'_'.$this->_cache_id;
	}
	
	/**
	 * Cache identifier
	 *
	 * @access	private
	 */
	private $_cache_id = NULL;
	
	/**
	 * Compiled file name
	 *
	 * @access	private
	 */
	private $_compiled_file = NULL;

}	// End Tal_Core


/**
 * PHPTAL modifiers
 *
 * @package    Component
 * @author     Florent Bonomo
 */
 
/**
 * Perform a sub-request
 */
function phptal_tales_comp($src, $nothrow)
{
	return 'Request::factory(\''.trim($src).'\', $ctx->controller)->execute()->response';
}

/**
 * Translate an URI
 */
function phptal_tales_uri($src, $nothrow)
{
	return '\''.I18n::instance()->uri($src).'\'';
}

/**
 * Translate an URI and make it relative to base uri
 */
function phptal_tales_base_uri($src, $nothrow)
{
	return '$_SERVER[\'REQUEST_URI\'].\'/'.I18n::instance()->uri($src).'\'';
}

/**
 * Dump an object from within the context using Kohana::debug method
 */
function phptal_tales_dump($src, $nothrow)
{
	return 'Kohana::debug('.phptal_tales($src, $nothrow).')';
}