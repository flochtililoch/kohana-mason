<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Doctrine ORM EntityManager Class Extension
 *
 * @package    Component
 * @author     Florent Bonomo
 */
class Doctrine_YamlDriver extends Doctrine\ORM\Mapping\Driver\YamlDriver
{
	private $_model_path = 'model/yaml';
	
	private $_ext = 'yml';
    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
		$filename = preg_replace('/.*'.preg_quote($this->_model_path, '/').'\/(.*)\.'.$this->_ext.'$/', '$1', $file);
		$mapping = array();
		foreach(Kohana::find_file('model/yaml', $filename, $this->_ext, TRUE) as $file)
		{
			$mapping = Arr::merge($mapping, \Symfony\Component\Yaml\Yaml::load($file));
		}
		
        return $mapping;
    }

}	// End Doctrine_YamlDriver