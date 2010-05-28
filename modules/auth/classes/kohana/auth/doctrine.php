<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Doctrine ORM Auth driver.
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Kohana_Auth_Doctrine extends Auth
{
    
    /**
	 * Checks if a session is active.
	 *
	 * @param   string   role entity / name
	 * @param   array    collection of role entities / names
	 * @return  boolean
	 */
	public function logged_in($roles = NULL)
	{
		$status = FALSE;

		// Get the user from the session
		$user = $this->get_user();
		
		if(is_object($user) AND $user instanceof Entities\User)
		{
			// Everything is okay so far
			$status = TRUE;

			if(!empty($roles))
			{
				// If roles is not an array
				if(!is_array($roles))
				{
					// Convert it to an array of one role
					$roles = array($roles);
				}

				// Check each role
				foreach($roles as $role)
				{
					if(!is_object($role))
					{
						$role = ORM::load('Role')->findBy(array('name' => $role));
					}
					// If the user doesn't have the role
					if(!$user->getRoles()->contains($role))
					{
						// Set the status false and get outta here
						$status = FALSE;
						break;
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Logs a user in.
	 *
	 * @param   string   username
	 * @param   string   password
	 * @param   boolean  enable auto-login
	 * @return  boolean
	 */
	public function _login($user, $password, $remember)
	{
		if(!is_object($user))
		{
			// Load the user
			$user = $this->get_user($user);
		}
		
		// If the passwords match, perform a login
		if($user instanceof Entities\User && !$user->getRoles()->contains('roles', ORM::load('Role')->findBy(array('name' => 'blocked'))) AND $user->getPassword() === $password)
		{
			if($remember === TRUE)
			{
				// Create a new token
				$token = Auth_Doctrine::create_token();
				
				// Attach the token to the user
				$token->setUser($user);

				// Set the autologin cookie
				cookie::set('authautologin', $token->getHash(), $this->_config['lifetime']);
				
				// Attach the token to the Entity manager
				Orm::save($token);
			}

			// Finish the login
			$this->complete_login($user->getLogin());

			return TRUE;
		}

		// Login failed
		return FALSE;
	}

	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    username
	 * @return  boolean
	 */
	public function force_login($user)
	{
		if(!is_object($user))
		{
			// Load the user
			$user = $this->get_user($user);
		}

		// Mark the session as forced, to prevent users from changing account information
		$_SESSION['auth_forced'] = TRUE;

		// Run the standard completion
		$this->complete_login($user->getLogin());
	}

	/**
	 * Logs a user in, based on the authautologin cookie.
	 *
	 * @return  boolean
	 */
	public function auto_login()
	{
		if ($hash = cookie::get('authautologin'))
		{
			// Load the token and user
			$token = ORM::load('Token')->findOneBy(array('hash' => $hash));

			if ($token !== NULL AND $token->getUser() !== NULL)
			{
				if ($token->getUserAgent() === sha1(Request::$user_agent))
				{
					// Update token data
					Auth_Doctrine::create_token($token);

					// Set the autologin cookie
					cookie::set('authautologin', $token->getHash(), $this->_config['lifetime']);

					// Complete the login with the found data
					$this->complete_login($token->getUser()->getLogin());

					// Automatic login was successful
					return $token->getUser();
				}

				// Token is invalid
				Orm::instance()->remove($token);
			}
		}

		return FALSE;
	}
	
	/**
	 * Returns Current user object
	 * (cached for the request only)
	 *
	 * @param   string   username
	 * @return  Entity\User
	 */
    public function get_user($login = NULL)
	{
		// If User hasn't been fetched yet
		if($this->_user === NULL)
		{
			// If login param isn't set, try to get it from session
			if($login === NULL)
			{
				$login = parent::get_user();
			}
			// If from that point login is not null, try to fetch it from ORM
			if($login === FALSE)
			{
				// check for "remembered" login
				$this->_user = $this->auto_login();
			}
			elseif($login !== NULL)
			{
				$this->_user = ORM::load('User')->findOneBy(array('login' => $login));
			}
		}
		return $this->_user;
	}

	/**
	 * Log a user out and remove any auto-login cookies.
	 *
	 * @param   boolean  completely destroy the session
	 * @param	boolean  remove all tokens for user
	 * @return  boolean
	 */
	public function logout($destroy = FALSE, $logout_all = FALSE)
	{
		if ($hash = cookie::get('authautologin'))
		{
			// Delete the autologin cookie to prevent re-login
			cookie::delete('authautologin');
			
			// Clear the autologin token from the database
			$token = ORM::load('Token')->findOneBy(array('hash' => $hash));
			
			if ($token !== NULL AND $logout_all)
			{
				Orm::instance()->remove($token->getUser()->getTokens());
			}
			elseif ($token !== NULL)
			{
				Orm::instance()->remove($token);
			}
		}

		return parent::logout($destroy);
	}

	/**
	 * Get the stored password for a username.
	 *
	 * @param   mixed   username
	 * @return  string
	 */
	public function password($user)
	{
		if ( ! is_object($user))
		{
			// Load the user
			$user = $this->get_user($user);
		}
		if($user instanceof Entities\User)
		{
			return $user->getPassword();
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Create a new token or update passed one
	 *
	 * @param   Entities\Token   reference to the token to update
	 * @return  Entities\Token
	 */
	protected function create_token(Entities\Token &$token = NULL)
	{
		// Create a new autologin token if not passed as argument
		if($token === NULL)
		{
		 	$token = new Entities\Token;
		}

		// Set the expire time and hash of the user agent
		$token->setExpires(time() + $this->_config['lifetime']);
		$token->setUserAgent(sha1(Request::$user_agent));

		// Create a new token each time the token is saved
		while (TRUE)
		{
			// Create a random token
			$hash = text::random('alnum', 32);

			// Make sure the token does not already exist
			if(ORM::load('Token')->findOneBy(array('hash' => $hash)) === NULL)
			{
				// A unique token has been found
				$token->setHash($hash);

				break;
			}
		}
		
		return $token;
	}
	
	/**
	 * Compare password with original (hashed). Works for current (logged in) user
	 *
	 * @param   string  $password
	 * @return  boolean
	 */
	public function check_password($password)
	{
		$user = $this->get_user();

		if ($user === FALSE)
		{
			// nothing to compare
			return FALSE;
		}

		$hash = $this->hash_password($password, $this->find_salt($user->password));

		return $hash == $user->password;
	}
	
	// User object cache container
    protected $_user = NULL;

} // End Kohana_Auth_Doctrine