<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's Validate Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Validate extends Kohana_Validate
{
	public function process($rules = array(), $filters = array(), $callback_field_valid = NULL)
	{
		foreach($this as $field => $value)
		{
			if(isset($filters) && array_key_exists($field, $filters))
			{
				if(is_array($filters[$field]))
				{
					$this->filters($field, $filters[$field]);
				}
				else
				{
					$this->filter($field, $filters[$field]);
				}
			}
			if(isset($rules) && array_key_exists($field, $rules))
			{
				if(is_array($rules[$field]))
				{
					$this->rules($field, $rules[$field]);
				}
				else
				{
					$this->rule($field, $rules[$field]);
				}
			}
		}

		$data = $this->as_array();

		if($this->check())
		{
			foreach($data as $p => $v)
			{
				if(array_key_exists($p, $rules))
				{
					if(is_array($rules[$p]) && in_array('date', $rules[$p]) || $rules[$p] === 'date')
					{
						$date_format = array_key_exists($p.'_datetype', $data) ? $data[$p.'_datetype'] : I18n::DATE_TYPE;
						$time_format = array_key_exists($p.'_timetype', $data) ? $data[$p.'_timetype'] : I18n::TIME_TYPE;
						unset($data[$p.'_datetype'], $data[$p.'_timetype']);
						$v = I18n::datetime($v, $date_format, $time_format)->datetime;
					}
				}
				if(is_callable($callback_field_valid))
				{
					$callback_field_valid($p, $v);
				}
			}
			return TRUE;
		}
		else
		{
			return array(
				'errors' => $this->errors('validate'),
				'data' => $data
				);
		}
	}
}