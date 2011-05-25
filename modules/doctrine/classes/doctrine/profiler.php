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
			Kohana::$log->attach(new Log_File(LOGPATH . 'doctrine'));

			// Add this query to the log
			Kohana::$log->add(Log::DEBUG, $sql);
			
			// And its params if present
			if ($params)
        	{
        		Kohana::$log->add(Log::DEBUG, var_export($params, TRUE));
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