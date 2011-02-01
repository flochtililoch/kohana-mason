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
	
	private $_model_path = 'model/yaml';
	
	private $_ext = 'yml';
	
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
                'extends-entities', null, InputOption::VALUE_REQUIRED,
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
		$validation_path = 'model/yaml';
		$files = Kohana::list_files($validation_path);
	
		foreach($files as $file)
		{
			preg_match('/'.preg_quote($validation_path, '/').'\/([A-Za-z0-9-\.]+)\.([A-Za-z0-9-]+)\.dcm\.yml/', $file, $schema_info);

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
			
			$filename = $schema_info[1].'.'.$schema_info[2];
			$entity = $schema_info[1].'\\'.$schema_info[2];
			$class = str_replace('.', '_', $filename);
			$path = str_replace('.', '/', $filename);
			$dest_file = CACHEPATH.'classes/validation/'.$path.'.php';
			
			/**
			 * Copy/Paste from yamldriver.php // TODO : FACTORIZE!
			 */
			$filename = preg_replace('/.*'.preg_quote($this->_model_path, '/').'\/(.*)\.'.$this->_ext.'$/', '$1', $file);
			$mapping = array();			
			
			$filename = preg_replace('/.*'.preg_quote($this->_model_path, '/').'\/(.*)\.'.$this->_ext.'$/', '$1', $file);
			$mapping = array();
			foreach(Kohana::find_file('model/yaml', $filename, $this->_ext, TRUE) as $file)
			{
				$mapping = Arr::merge($mapping, \Symfony\Component\Yaml\Yaml::load($file));
			}
			
			$entity_schema = $mapping;
			$rules_code = NULL;
			$json_rules_code = NULL;
			$filters_code = NULL;
			if(array_key_exists('fields', $entity_schema[$entity]))
			{
				foreach($entity_schema[$entity]['fields'] as $field => $props)
				{
					if(array_key_exists('rules', $props))
					{
						$rules = $props['rules'];
						$r = NULL;
						$r_json = NULL;
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
							$rule = (isset($key) ? 'array(\''.$key.'\' => ' : '').'array('.implode(', ', $rs).')'.(isset($key) ? ')' : '');
							$r = '\''.$field.'\' => '.$rule;
							
							eval('$rule_compiled = '.$rule.';');
							$r_json = '\''.$field.'\' => \''.json_encode($rule_compiled).'\'';
							unset($key);
						}
						elseif(is_string($rules))
						{
							$r = '\''.$field.'\' => \''.$rules.'\'';
							$r_json = '\''.$field.'\' => \''.json_encode($rules).'\'';
						}
						if($r !== NULL)
						{
							$rules_code .= "		".$r.",\n";
							$json_rules_code .= "		".$r_json.",\n";
						}
					}
				
					if(array_key_exists('filters', $props))
					{
						$filters = $props['filters'];
						$f = NULL;
						if(is_array($filters))
						{
							$fs = array();
							foreach($filters as $name => $filter)
							{
								if(is_array($filter))
								{
									foreach($filter as $k => $v)
									{
										if(is_string($k))
										{
											$fs[] = 'array(\''.$k.'\' => array('.(is_string($v) ? '\''.$v.'\'' : $v).'))';
										}
										else
										{
											$fs[] = $v;
											$key = $name;
										}
									}
								}
								elseif(is_string($name))
								{
									$fs[] = '\''.$name.'\' => array('.(is_string($filter) ? '\''.$filter.'\'' : $filter).')';
								}
								else
								{
									$fs[] = '\''.$filter.'\' => NULL';
								}
							}
							$filter = (isset($key) ? 'array(\''.$key.'\' => ' : '').'array('.implode(', ', $fs).')'.(isset($key) ? ')' : '');
							$f = '\''.$field.'\' => '.$filter;
							
							unset($key);
						}
						elseif(is_string($filters))
						{
							$f = '\''.$field.'\' => \''.$filters.'\'';
						}
						if($r !== NULL)
						{
							$filters_code .= "		".$f.",\n";
						}
					}
				}
			}
			if($rules_code !== NULL || $filters_code !== NULL)
			{
				// Write php file
				file_put_contents(
					$dest_file,
					'<?php'.sprintf(
						substr(file_get_contents(MODPATH.'doctrine/templates/classes/validation'.EXT), 5, -1),		// validation class template
						$class,
						trim($rules_code),
						trim($json_rules_code),
						trim($filters_code)
						));

				if($input->getOption('extends-entities') !== NULL)
				{
					$entity_path = CACHEPATH.'classes/'.$path.'.php';
					$entity_class = file_get_contents($entity_path);
					file_put_contents($entity_path, preg_replace('/class ([a-zA-Z0-9_]+)( extends )?([\\a-zA-Z0-9_]+)?/','class $1 extends \\Validation_'.$class.PHP_EOL, $entity_class));
				}
				$output->write(sprintf('Processing entity\'s rules "<info>%s</info>"', $dest_file) . PHP_EOL);
			}
		}
	}
}