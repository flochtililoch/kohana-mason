<?php defined('SYSPATH') OR die('No direct access allowed.');

use Doctrine\DBAL\Logging\SqlLogger;

/**
 * Doctrine ORM SQL Logger.
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Doctrine_Profiler implements SqlLogger
{
	public static $log;
	
    public function logSql($sql, array $params = null)
    {
        // Config defines log status
		if (Kohana::$logging === TRUE)
		{
			// Create Logger object
			if(!is_object(self::$log))
			{
				self::$log = Kohana_Log::instance();
				self::$log->attach(new Kohana_Log_File(LOGPATH . 'doctrine', array('PDO')));
			}
			
			// Add this query to the log
			self::$log->add('PDO', $sql);
			
			// And its params if present
			if ($params)
        	{
        		Kohana::$log->add('PDO', var_export($params, TRUE));
        	}
		}
    }

}	// End Doctrine_Profiler