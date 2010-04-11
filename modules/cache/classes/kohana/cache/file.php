<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache File Driver
 * 
 * Make Use of Kohana's default cache method (fall back in case other drivers can't be used)
 * 
 * @package Cache
 * @author Sam de Freyssinet <sam@def.reyssi.net>
 * @author Florent Bonomo
 * @copyright (c) 2009 Sam de Freyssinet
 * @license ISC http://www.opensource.org/licenses/isc-license.txt
 * Permission to use, copy, modify, and/or distribute 
 * this software for any purpose with or without fee
 * is hereby granted, provided that the above copyright 
 * notice and this permission notice appear in all copies.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS 
 * ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO 
 * EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, 
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES 
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, 
 * WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER 
 * TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH 
 * THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */
class Kohana_Cache_File extends Cache{

    /**
	 * Constructor
	 *
	 * @access protected
	 */
	protected function __construct(){}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param	string	$id 
	 * @param	string	$default [Optional] Default value to return if id not found
	 * @return	mixed
	 * @access	public
	 */
	public function get($id, $default = FALSE)
	{
	   	// Cache file is a hash of the name
		$file = sha1($id).'.txt';

		// Cache directories are split by keys to prevent filesystem overload
		$dir = Kohana::$cache_dir.DIRECTORY_SEPARATOR.$file[0].$file[1].DIRECTORY_SEPARATOR;

		try
		{
			if (is_file($dir.$file))
			{
				if ((time() - filemtime($dir.$file)) < 0)
				{
					// Return the cache
					return unserialize(file_get_contents($dir.$file));
				}
				else
				{
					// Cache has expired
					unlink($dir.$file);
				}
			}

			// Cache not found
			return $default;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * @param	string	$id 
	 * @param	string	$data 
	 * @param	integer	$lifetime [Optional]
	 * @return	boolean
	 * @access	public
	 */
	public function set($id, $data, $lifetime = 0)
	{
	    if(0 === $lifetime)
	        $lifetime = time() + 315360000; // never expire = 10 years lifetime!
        
        // Cache file is a hash of the name
		$file = sha1($id).'.txt';

		// Cache directories are split by keys to prevent filesystem overload
		$dir = Kohana::$cache_dir.DIRECTORY_SEPARATOR.$file[0].$file[1].DIRECTORY_SEPARATOR;

		try
		{
			if ( ! is_dir($dir))
			{
				// Create the cache directory
				mkdir($dir, 0777, TRUE);

				// Set permissions (must be manually set to fix umask issues)
				chmod($dir, 0777);
			}

			// Write the cache
			$cached = (bool) file_put_contents($dir.$file, serialize($data));
			
			// Set lifetime
			touch($dir.$file, $lifetime);
			
			return $cached;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param	string	$id 
	 * @param	integer	$timeout [Optional]
	 * @return	boolean
	 * @access	public
	 */
	public function delete($id)
	{
		return Kohana::cache($this->sanitize_id($id), NULL, 0);
	}

	/**
	 * Delete all cache entries
	 *
	 * @return	boolean
	 * @access	public
	 */
	public function delete_all()
	{
	    if(Kohana::$is_windows === TRUE)
		{
			$cmd = 'rd %s /s /q';
		}
		else
		{
			$cmd = 'rm -rf %s';
		}
		return system(sprintf($cmd, Kohana::$cache_dir));
	}
}