<?php defined('SYSPATH') or die('No direct script access.');

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
	Symfony\Component\Yaml\Yaml;

/**
 * Command to generate validation rules from your schema
 *
 * Allowed keys :
 * 	not_empty
 * 	regex			$expression
 * 	min_length		$length
 * 	max_length		$length
 * 	exact_length	$length
 * 	equals			$required
 * 	email			$strict = FALSE
 * 	email_domain
 * 	url
 * 	ip				$allow_private = TRUE
 * 	credit_card		$type = NULL
 * 	luhn
 * 	phone			$lengths = NULL
 * 	date
 * 	alpha			$utf8 = FALSE
 * 	alpha_numeric	$utf8 = FALSE
 * 	alpha_dash		$utf8 = FALSE
 * 	digit			$utf8 = FALSE
 * 	numeric
 * 	range			$min			$max
 * 	decimal			$places = 2		$digits = NULL)
 * 	color
 *

required( )	Returns: Boolean							Makes the element always required.
required( dependency-expression )	Returns: Boolean	Makes the element required, depending on the result of the given expression.
required( dependency-callback )	Returns: Boolean		Makes the element required, depending on the result of the given callback.
remote( options )	Returns: Boolean					Requests a resource to check the element for validity.
minlength( length )	Returns: Boolean					Makes the element require a given minimum length.
maxlength( length )	Returns: Boolean					Makes the element require a given maxmimum length.
rangelength( range )	Returns: Boolean				Makes the element require a given value range.
min( value )	Returns: Boolean						Makes the element require a given minimum.
max( value )	Returns: Boolean						Makes the element require a given maximum.
range( range )	Returns: Boolean						Makes the element require a given value range.
email( )	Returns: Boolean							Makes the element require a valid email
url( )	Returns: Boolean								Makes the element require a valid url
date( )	Returns: Boolean								Makes the element require a date.
dateISO( )	Returns: Boolean							Makes the element require a ISO date.
dateDE( )	Returns: Boolean							Makes the element require a german date.
number( )	Returns: Boolean							Makes the element require a decimal number.
numberDE( )	Returns: Boolean							Makes the element require a decimal number with german format.
digits( )	Returns: Boolean							Makes the element require digits only.
creditcard( )	Returns: Boolean						Makes the element require a creditcard number.
accept( extension )	Returns: Boolean					Makes the element require a certain file extension.
equalTo( other )	Returns: Boolean					Requires the element to be the same as another one




 * @author  Florent Bonomo
 */
class Doctrine_Validation extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
		$this
		->setName('blah:generate-validation-rules')
		->setDescription('Generate validation rules from YAML files.')
		->setDefinition(array(
			new InputOption(
                'extends-entities', null, InputOption::PARAMETER_OPTIONAL,
                'Flag to define if generator should modify existing entities classes to make them extends validation classes.', false
            )
		))
        ->setHelp(<<<EOT
Generate validation rules from YAML files.
EOT
        );
    }

	/**
	 * @see Console\Command\Command
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$validation_path = 'model/validation';
		$files = Kohana::list_files($validation_path);
		
		foreach($files as $file)
		{
			preg_match('/'.preg_quote($validation_path, '/').'\/([A-Za-z0-9-\.]+)\.([A-Za-z0-9-]+)\.yml/', $file, $schema_info);

			// Process destination directory
			if(!is_dir($destPath = CACHEPATH.'classes/validation/'.str_replace('.','/',$schema_info[1])))
			{
				mkdir($destPath, 0777, true);
			}
			$destPath = realpath($destPath);

			if(!file_exists($destPath))
			{
				throw new \InvalidArgumentException(
					sprintf("Validation classes destination directory '<info>%s</info>' does not exist.", $destPath)
				);
			}
			else if(!is_writable($destPath))
			{
				throw new \InvalidArgumentException(
					sprintf("Validation classes directory '<info>%s</info>' does not have write permissions.", $destPath)
				);
			}
			
			$file = $schema_info[1].'.'.$schema_info[2];
			$entity = $schema_info[1].'\\'.$schema_info[2];
			$class = str_replace('.', '_', $file);
			$path = str_replace('.', '/', $file);
			$dest_file = CACHEPATH.'classes/validation/'.$path.'.php';
			
			if($input->getOption('extends-entities') !== NULL)
			{
				$entity_path = CACHEPATH.'classes/'.$path.'.php';
				$entity_class = file_get_contents($entity_path);
				file_put_contents($entity_path, preg_replace('/class ([a-zA-Z0-9_]+)( extends )?([\\a-zA-Z0-9_]+)?/','class $1 extends \\Validation_'.$class.PHP_EOL, $entity_class));
			}
			
			$output->write(sprintf('Processing entity\'s rules "<info>%s</info>"', $dest_file) . PHP_EOL);

			$validation_schema = Yaml::load(Kohana::find_file('model', 'validation/'.$file, 'yml'));
			$validation_class_tpl = 
	'<?php
/**
 * Validation_%1$s
 */
class Validation_%1$s
{
	private $_validation_rules = %2$s;
	
	public function validation_rules($key = NULL, $json = FALSE)
	{
		if($key !== NULL)
		{
			$r = $this->_validation_rules[$key];
		}
		else
		{
			$r = $this->_validation_rules;
		}
		return $json === TRUE ? json_encode($r) : $r;
	}

	public function update($data)
	{
		$dv = Validate::factory($data)
%3$s;

		if($dv->check())
		{
			return TRUE;
		}
		else
		{
			return $dv->errors();
		}
	}

} // End %1$s';


			$rules_code = NULL;
			foreach($validation_schema[$entity]['rules'] as $field => $rules)
			{
				if(is_array($rules))
				{
					$rs = array();
					foreach($rules as $name => $rule)
					{
						if(is_array($rule))
						{
							foreach($rule as $k => $v)
							{
								if(is_string($k))
								{
									$rs[] = 'array(\''.$k.'\' => array('.(is_string($v) ? '\''.$v.'\'' : $v).'))';
								}
								else
								{
									$rs[] = $v;
									$key = $name;
								}
							}
						}
						elseif(is_string($name))
						{
							$rs[] = '\''.$name.'\' => array('.(is_string($rule) ? '\''.$rule.'\'' : $rule).')';
						}
						else
						{
							$rs[] = '\''.$rule.'\' => NULL';
						}
					}
					$r = '->rules'.'(\''.$field.'\', '.(isset($key) ? 'array(\''.$key.'\' => ' : '').'array('.implode(', ', $rs).'))'.(isset($key) ? ')' : '');
					unset($key);
				}
				elseif(is_string($rules))
				{
					$r = '->rule(\''.$field.'\', \''.$rules.'\')';
				}
				$rules_code .= "				".$r."\n";
			}

			// Write php file
			file_put_contents(
				$dest_file,
				sprintf( 
					$validation_class_tpl,
					$class,
					var_export($validation_schema[$entity]['rules'], TRUE),
					rtrim($rules_code)
					));
		}
	}
}