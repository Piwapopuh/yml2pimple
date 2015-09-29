<?php
namespace G\Yaml2Pimple\Factory;


abstract class AbstractFactory
{
    protected $normalizer;
    
	public function setNormalizer($normalizer)
	{
		$this->normalizer = $normalizer;
		
		return $this;
	}
    
    public function normalize($value, $container)
    {
        if (is_null($this->normalizer)) {
            return $value;
        }
        
        if (is_array($value)) {
            foreach($value as $k => $v) {
				$value[$k] = $this->normalize($v, $container);
			}
			return $value;
        }

        return $this->normalizer->normalize($value, $container);
    }	

}