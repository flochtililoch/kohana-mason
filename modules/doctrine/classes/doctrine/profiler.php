<?php defined('SYSPATH') OR die('No direct access allowed.');

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Doctrine ORM SQL Logger.
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Doctrine_Profiler implements SQLLogger
{
	public static $log;
	
	/**
     * Logs a SQL statement somewhere.
     *
     * @param string $sql The SQL to be executed.
     * @param array $params The SQL parameters.
     * @param float $executionMS The microtime difference it took to execute this query.
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
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

    /**
     * Mark the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopQuery(){}


}	// End Doctrine_Profiler