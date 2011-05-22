<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Tal Class
 * Extends PHPTAL class
 *
 * @package    PHPTAL
 * @author     Florent Bonomo
 */
class Tal_Core extends PHPTAL
{

	// View engine identifier
	const ENGINE = 'Tal';
	
	/**
	 * Set cache identifier
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
	 * Set controller name
	 *
	 * Used by context reader
	 *
	 * @return	Tal
	 * @access	public
	 */
	public function setContext($controller)
	{
	   // Create new context object
		$this->_context = new Context();
		
		// Store creator controller name
		$this->_context->controller = $controller;
		
		return $this;
	}
	
	/**
	 * TAL Constructor.
	 *
	 * @param 	string 	Template file path
	 * @param 	string 	Template data
	 * @access	public
	 */
	public function __construct($file = FALSE, $comp = NULL, $cache_id = NULL)
	{
		// Find view file
		$this->_path = Kohana::find_file('comps', $file, 'xhtml');

		// Save source filename
		$this->_compiled_file = str_replace(array('_', '.', '/'), array('', '_', '_'), $file).'_'.I18n::locale().'_'.I18n::channel();

		// Set context controller class name
		$this->setContext($comp);
		
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
		
		// Set cache identifier
		$this->setCacheId($cache_id);
		
		// Pass the translator object to the template manager
		$this->setTranslator(I18n::instance());
		
		// Enable compression for non dev env
		if(Kohana::$environment !== Kohana::DEVELOPMENT)
		{
			$this->addPreFilter(new PHPTAL_PreFilter_Compress);
		}
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
			Kohana_Exception::handler($e);

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

// PHPTAL Customs modifiers
require_once('_modifiers.php');