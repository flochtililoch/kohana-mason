<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * I18n Base class
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_I18n
{
	const CONTENT_DOMAIN = 'content';	// reference to PO file used for content translations
	const ROUTES_DOMAIN  = 'routes';	// reference to PO file used for routes translations
	const DATE_TYPE = 'medium';
	const TIME_TYPE = 'short';
	
	public static $format_types = array(
		'none' => IntlDateFormatter::NONE,
		'full' => IntlDateFormatter::FULL,
		'long' => IntlDateFormatter::LONG,
		'medium' => IntlDateFormatter::MEDIUM,
		'short' => IntlDateFormatter::SHORT,
		'traditional' => IntlDateFormatter::TRADITIONAL,
		'gregorian' => IntlDateFormatter::GREGORIAN
	);
	
	public static $locale_valid_pattern = '/^([a-z]{2})_([A-Z]{2})$/';
	
	/**
	 * @var  string  source language: en-us, es-es, zh-cn, etc
	 */
	public static $source = 'en-us';
	
	/**
	 * @var  array	locales static cache
	 */
	public static $locales = array();
	
	/**
	 * NOTICE : defined for compatibility with default Kohana translation service
	 *
	 * @static
	 */
	public static $lang = NULL;
	
	/**
     * Main translator singleton instance
	 *
	 * @param	string	locale
	 * @return	Tal_I18	class instance
	 * @access	public
	 * @static
     */
    public static function instance($locale = NULL)
    {
		// Reset locale if passed as argument
		if($locale !== NULL)
		{
			I18n::init($locale);
		}
		
		// If instance for this locale is not set yet, create it
		if(!array_key_exists(I18n::$_locale, I18n::$_instances))
		{
			I18n::$_instances[I18n::$_locale] = new I18n();
			I18n::$_instances[I18n::$_locale]->_encoding = Kohana::$charset;
			I18n::$_instances[I18n::$_locale]->addDomain(I18n::CONTENT_DOMAIN, APPPATH.'i18n/');
		}

		// Return instance for current locale
		return I18n::$_instances[I18n::$_locale];
	}
	
	/**
	 * Get and set the target language.
	 *
	 * @param   string  locale string (e.g. fr_FR)
	 * @param   integer channel id
	 * @return  void
	 * @access	public
	 * @static
	 */
	public static function init($locale = NULL, $channel = NULL, $source = NULL)
	{
		I18n::channel($channel);
		I18n::source($source);
		
	    // If locale string is well formed
        if(preg_match(I18n::$locale_valid_pattern, $locale, $i18n))
        {
			I18n::locale($locale);
            I18n::language($i18n[1]);
            I18n::country($i18n[2]);
            
            // NOTICE : for compatibility purpose
            I18n::$lang = I18n::language().'-'.I18n::country();
        }
        else
        {
            throw new Kohana_Exception(':locale is malformed. (e.g. fr_FR)', array(
				':locale' => $locale
			));
        }
	}
	
	/**
	 * Get and set the default locale.
	 *
	 * @param   string   new locale setting
	 * @return  string
	 * @access	public
	 * @static
	 * @see  http://docs.kohanaphp.com/about.configuration
	 * @see  http://php.net/setlocale
	 */
	public static function locale($locale = NULL)
	{
		if($locale !== NULL)
		{
			// Store locale statically
			I18n::$_locale = $locale;
			
			// Add charset
			$locale .= '.utf8';
			
			// Set the default locale.
			// NOTE: Windows setlocale with UTF8 charset will fail; No need to support it then.
			setlocale(LC_ALL, $locale);
			
			// Needed for WINDOWS & MAC environements
            putenv("LANG=$locale");
            putenv("LC_ALL=$locale");
            putenv("LANGUAGE=$locale");
		}
		return I18n::$_locale;
	}
	
	/**
	 * Get and set the language.
	 *
	 * @param   string   new language setting
	 * @return  string
	 * @access	public
	 * @static
	 */
	public static function language($language = NULL)
	{
	    if($language !== NULL)
	    {
	        I18n::$_language = $language;
	    }
		return I18n::$_language;
	}
	
	/**
	 * Get and set the country.
	 *
	 * @param   string   new country setting
	 * @return  string
	 * @access	public
	 * @static
	 */
	public static function country($country = NULL)
	{
	    if($country !== NULL)
	    {
	        I18n::$_country = strtolower($country);
	    }
		return I18n::$_country;
	}
	
	/**
	 * Get and set the channel.
	 *
	 * @param   string   new channel setting
	 * @return  string
	 * @access	public
	 * @static
	 */
	public static function channel($channel = NULL)
	{
	    if($channel !== NULL)
	    {
	        I18n::$_channel = $channel;
	    }
		return I18n::$_channel;
	}
	
	/**
	 * Get and set the source language.
	 *
	 * @param   string   new source setting
	 * @return  string
	 * @access	public
	 * @static
	 */
	public static function source($source = NULL)
	{
	    if($source !== NULL)
	    {
	        I18n::$source = $source;
	    }
		return I18n::$source;
	}
	
	/**
	 * Returns translation of a string
	 * NOTICE : This method is defined for compatibility
	 * with Kohana default translation service
	 *
	 * @param   string   text to translate
	 * @return  string
	 * @access	public
	 * @static
	 */
	public static function get($string, $vars = NULL)
	{
		if($vars !== NULL)
		{
			if(is_array($vars))
			{
				foreach($vars as $k => $v)
				{
					I18n::instance()->setVar($k, $v);
				}
			}
		}
		// Return the translated string if it exists
		return I18n::instance()->translate($string);
	}
	
	public static function locales()
	{
	}
	
	/**
     * Gettext translator constructor
	 *
	 * @access	public
	 */
    public function __construct()
    {
		// Throw an error if gettext is not loaded
        if (!function_exists('gettext'))
        {
            throw new Kohana_Exception('Gettext not installed');
        }
    }
	
	/**
     * Adds translation domain (usually it's the same as name of .po file [without extension])
     * Encoding must be set before calling addDomain!
	 *
	 * @access	public
     */
    public function addDomain($domain, $path)
    {
        bindtextdomain($domain, $path);
		if ($this->_encoding)
        {
            bind_textdomain_codeset($domain, $this->_encoding);
        }
		
		// return result of useDomain method
        return $this->useDomain($domain);
    }

    /**
     * Switches to one of the domains previously set via addDomain()
     *
     * @param string $domain name of translation domain to be used.
     * @return string - old domain
	 * @access	public
	 */
    public function useDomain($domain)
    {
        $old = $this->_currentDomain;
        $this->_currentDomain = $domain;
        textdomain($domain);
        return $old;
    }

    /**
     * Used by generated PHP code. Don't use directly.
	 *
	 * @access	public
     */
    public function setVar($key, $value)
    {
        $this->_vars[$key] = $value;
    }
	
	/**
     * Translate given key.
     *
     * @param bool $htmlencode if true, output will be HTML-escaped.
	 * @access	public
     */
    public function translate($key, $htmlencode = TRUE, $origin = I18n::CONTENT_DOMAIN)
    {
        $value = gettext($key);

		if($htmlencode)
        {
            $value = htmlspecialchars($value, ENT_QUOTES);
        }
		
		// Do variable interpolation
        while(preg_match('/\${(.*?)\}/sm', $value, $m))
        {
            list($src, $var) = $m;
            if (!array_key_exists($var, $this->_vars))
            {
                throw new Kohana_Exception('Interpolation error. Translation uses ${'.$var.'}, which is not defined in the template (via i18n:name)');
            }
            $value = str_replace($src, $this->_vars[$var], $value);
        }
		
		// If DEV environment, write translation reference in a cache used by poedit
		if(Kohana::$environment === Kohana::DEVELOPMENT)
		{
			$this->_poedit(array($key), $origin);
		}
		
        return $value;
    }
    
    /**
     * Translate a uri
	 *
	 * @access	public
	 */
    public function uri($uri)
    {
        if(is_string($uri) && $uri !== '')
        {
			// Save current domain in use and switch to routes one
			$old_domain = $this->addDomain(I18n::ROUTES_DOMAIN, APPPATH.'i18n/');
			
			// Save prefix and suffix / if present
			$prefix = substr($uri,0,1) === '/' ? '/' : '';
			$suffix = substr($uri,-1) === '/' ? '/' : '';
			
			// Split uri into segments
            $segments = explode('/', trim($uri, '/'));
            $uri = array();

			// Translate each segment
            foreach($segments as $segment)
            {
                $uri[] = $this->translate($segment, TRUE, I18n::ROUTES_DOMAIN);
            }

			// Merge back segments
			$uri = $prefix.implode('/', $uri).$suffix;
			
			// Restore previously used domain
			$this->useDomain($old_domain);
        }
		
        return $uri;
    }

	/**
     * return localized datetime
     * @param	mixed 		string or timestamp representing the date to localize
	 * @param	integer		type of the output
	 *
	 * @access	public
	 * @static
	 */
	public static function datetime($input_date, $datetype = I18n::DATE_TYPE, $timetype = I18n::TIME_TYPE)
	{
		$df = new IntlDateFormatter(Kohana::$locale, I18n::$format_types[$datetype], I18n::$format_types[$timetype]);
		
		if(is_numeric($input_date))
		{
			$string = $df->format($input_date);
			$timestamp = $input_date;			
		}
		else
		{
			$string = $input_date;
			$timestamp = $df->parse($input_date);
		}
		
		$datetime = new Datetime();
		$datetime->setTimestamp($timestamp);

		return (object) array(
				'string' => $string,
				'pattern' => $df->getPattern(),
				'timestamp' => $timestamp,
				'datetime' => $datetime
				);
	}
	
	/**
     * Check wether a string is a valid locale
     * @param	string 		string representing the locale
	 * @param	boolean		enforce check by looking up value in database
	 * @return  boolean
	 *
	 * @access	public
	 * @static
	 */
	public static function valid_locale($locale, $strict = TRUE)
	{
		if(is_array($locale))
		{
			foreach($locale as $locale_code)
			{
				$locale_valid = I18n::valid_locale($locale_code);
				if($locale_valid !== TRUE)
				{
					return FALSE;
				}
			}
			return TRUE;
		}
		// If locale string is well formed
        if(preg_match(I18n::$locale_valid_pattern, $locale, $matches))
        {
			// If we need to check if the locale exists in the database
			if($strict === TRUE)
			{
				if(!array_key_exists($locale, I18n::$locales))
				{
					$_locale = ORM::load('Locale')->findBy(array('language' => $matches[1], 'country' => $matches[2]));
					I18n::$locales[$locale] = $_locale[0];
				}
				
				if(count(I18n::$locales[$locale]))
				{
					return TRUE;
				}
				return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}
	
	/**
     * Check wether a string is a valid timezone
     * @param	string 		string representing the timezone
	 * @return  boolean
	 *
	 * @access	public
	 * @static
	 */
	public static function valid_timezone($timezone)
	{
		$current_tz = date_default_timezone_get();
		$valid = date_default_timezone_set($timezone);
		date_default_timezone_set($current_tz);
		return $valid;
	}
	
	/**
     * Write string to translate
     * in a cache directory used only by poedit
     * for translations retrievals
	 *
	 * @access	protected
	 */
    protected function _poedit(Array $names, $type)
    {
        foreach($names as $name)
        {
            // Cache file is a hash of the name
    		$file = sha1($name).'.php';
    		$dir = CACHEPATH.'i18n/'.$type.'/'.$file[0].$file[1].'/';

    		// if cache directory doesn't exists
            if(!is_dir($dir))
    		{
    			// Create directory
    			mkdir($dir, 0777, TRUE);

    			// Set permissions (must be manually set to fix umask issues)
    			chmod($dir, 0777);
    		}
            file_put_contents($dir.$file, Kohana::FILE_SECURITY.chr(13).chr(13).'_(\''.$name.'\');');
        }
    }
    
    /**
	 * Instances container
	 *
	 * @access	protected
	 * @static
	 */
    protected static $_instances = array();
	
	/**
	 * Locale container
	 *
	 * @access	protected
	 * @static
	 */
	protected static $_locale = NULL;
	
	/**
	 * Language container
	 *
	 * @access	protected
	 * @static
	 */
	protected static $_language = NULL;
    
    /**
	 * Country container
	 *
	 * @access	protected
	 * @static
	 */
    protected static $_country = NULL;
    
    /**
	 * Channel container
	 *
	 * @access	protected
	 * @static
	 */
    protected static $_channel = NULL;
    
    private $_vars = array();
    private $_currentDomain;
    private $_encoding = 'UTF-8';

}	// End Component_I18n

if ( ! function_exists('__'))
{
	/**
	 * Kohana translation/internationalization function. The PHP function
	 * [strtr](http://php.net/strtr) is used for replacing parameters.
	 *
	 *    __('Welcome back, :user', array(':user' => $username));
	 *
	 * [!!] The target language is defined by [I18n::$lang].
	 * 
	 * @uses    I18n::get
	 * @param   string  text to translate
	 * @param   array   values to replace in the translated text
	 * @param   string  source language
	 * @return  string
	 */
	function __($string, array $values = NULL, $lang = 'en-us')
	{
		if ($lang !== I18n::$lang)
		{
			// The message and target languages are different
			// Get the translation for this message
			$string = I18n::get($string);
		}

		return empty($values) ? $string : strtr($string, $values);
	}
}