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

    public function logSql($sql, array $params = null)
    {
        if (is_object(Kohana::$log))
		{
			// Add this exception to the log
			Kohana::$log->add('PDO', $sql);
			if ($params)
        	{
        		Kohana::$log->add('PDO', var_export($params, TRUE));
        	}
		}
    }

}	// End Doctrine_Profiler