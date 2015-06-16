<?php

namespace G\Yaml2Pimple\Normalizer;

use \Symfony\Component\PropertyAccess\PropertyAccess;

class PimpleNormalizer
{
	protected $accessor;
	
    public function __construct()
    {
		$this->accessor = PropertyAccess::createPropertyAccessor();		
    }	
    
    public function normalize($value, $container)
    {	
		if (!is_string($value)) {
			return $value;
		}

		// argument references a service
		if (0 === strpos($value, '@')) {
			
			$can_return_null = false;
			$value = substr($value, 1);
			
			// argument is optional
			if (0 === strpos($value, '?')) {
				$can_return_null = true;
				$value = substr($value, 1);
			}
			// our "magic" reference to the container itself
			if ("service_container" == $value) {
				return $container;
			}
			
			// check if service is defined
			if (!isset($container[$value]))
			{
				if ($can_return_null) {
					return null;
				} else {
					throw new \Exception('undefined service ' . $value);
				}
			}
			return $container[$value];			
		}

        if (preg_match('{^%([a-zA-Z0-9_.]+)%$}', $value, $match)) {
            $key = strtolower($match[1]);
            if (false !== strpos($key, '..')) {
                $key = '[' . str_replace('..', '][', $key) . ']';
                if ($this->accessor->isReadable($container, $key)) {
                    return $this->accessor->getValue($container, $key);
                } 
                return $match[0];
            }                
            return isset($container[$key]) ? $container[$key] : $match[0];
        }
        
        $that = $this;
		$callback = function ($matches) use ($that, $container)
		{
			if (!isset($matches[1])) {
				return '%%';
			}
            
            $key = strtolower($matches[1]);
            
            if (false !== strpos($key, '..')) {
                $key = '[' . str_replace('..', '][', $key) . ']';
                if ($that->accessor->isReadable($container, $key)) {
                    return $that->accessor->getValue($container, $key);
                } 
                return $matches[0];
            }              
			return isset($container[$key]) ? $container[$key] : $matches[0];
		};
		$result = preg_replace_callback('{%%|%([a-zA-Z0-9_.]+)%}', $callback, $value, -1, $count);

        return $count ? $result : $value;		
    }
}
