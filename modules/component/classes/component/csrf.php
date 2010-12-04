<?php defined('SYSPATH') or die('No direct script access.');
/**
 * CSRF Token Manager
 * Forked from https://github.com/synapsestudios/kohana-csrf
 *
 * @package    Component
 * @author     Zeelot
 * @author     Florent Bonomo
 */
class Component_CSRF
{

	/**
	 * Returns the token in the session or generates a new one
	 *
	 * @param string $namespace - semi-unique name for the token (support for multiple forms)
	 * @return string
	 */
	public static function token($namespace = NULL)
	{
		$token = Session::instance()->get('csrf-token'.$namespace);

		// Generate a new token if no token is found
		if(!$token)
		{
			$token = Text::random('alnum', rand(20, 30));
			Session::instance()->set('csrf-token'.$namespace, $token);
		}

		return $token;
	}

	/**
	 * Validation rule for checking a valid token
	 *
	 * @param string $token - the token string to check for
	 * @return bool
	 */
	public static function valid($token, $namespace = NULL)
	{
		return $token === self::token($namespace);
	}

}