<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Kohana's Validate Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Component_Validate extends Kohana_Validate
{
	public static function form(&$_POST, $validation = array())
	{
		// All fields default to NULL
		$allowed_fields = array_key_exists('fields', $validation) ? array_map(function($n){ return null; }, array_flip($validation['fields'])) : array();
		
		// If there's a defined list of fields, filter them to only allowed ones
		if(count($allowed_fields))
		{
			// Keep data from allowed fields only
			$_POST = array_intersect_key(
								array_merge(
									$allowed_fields,
									$_POST
									),
								$allowed_fields
								);
		}

		// Validate data
		$valid = Validate::factory($_POST);
		$result = $valid->process($validation);
		
		// Save modified data
		$_POST = $valid->as_array();
		
		// Return validation result
		return $result;
	}
	
	public function process($validation, $callback_field_valid = NULL)
	{
		$types = array('rules', 'filters', 'callbacks');
		
		foreach($this as $field => $value)
		{
			foreach($types as $type)
			{
				$validation[$type] = array_key_exists($type, $validation) ? $validation[$type] : array();
				if(isset($validation[$type]) && array_key_exists($field, $validation[$type]))
				{
					if(is_array($validation[$type][$field]))
					{
						$this->{$type}($field, $validation[$type][$field]);
					}
					else
					{
						$this->{substr($type, 0, -1)}($field, $validation[$type][$field]);
					}
				}
			}
		}

		$data = $this->as_array();

		if($this->check())
		{
			foreach($data as $p => $v)
			{
				if(array_key_exists($p, $validation['rules']))
				{
					if(is_array($validation['rules'][$p]) && in_array('date', $validation['rules'][$p]) || $validation['rules'][$p] === 'date')
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