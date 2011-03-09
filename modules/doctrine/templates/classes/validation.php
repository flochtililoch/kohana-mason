<?php defined('SYSPATH') or die('No direct script access.');

/**
* Validation_%1$s
*/
class Validation_%1$s
{

	public $rules = array(
		%2$s
		);
	
	public $json_rules = array(
		%3$s
		);
	
	public $filters = array(
		%4$s
		);

	public function update($data)
	{
		$dv = Validate::factory($data);

		$entity = $this;
		$callback = function ($field, $value) use (&$entity)
		{
			$m = 'set'.Ucfirst($field);
			if(method_exists($entity, $m))
			{
				$entity->{$m}($value);
			}
		};
		return $dv->process(
			array('rules' => $this->rules, 'filters' => $this->filters, 'callbacks' => array(),
			$callback
			);
	}

} // End %1$s