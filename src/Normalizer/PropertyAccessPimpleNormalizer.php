<?php

namespace G\Yaml2Pimple\Normalizer;

use \Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @package Tacker
 */
class PropertyAccessPimpleNormalizer
{
    protected $pimple;
	protected $accessor;
	
    /**
     * @param Pimple $pimple
     */
    public function __construct(\Pimple $pimple)
    {
        $this->pimple = $pimple;

		$this->accessor = PropertyAccess::createPropertyAccessor();		
    }

    /**
     * @param  string $value
     * @return string
     */
    public function normalize($value, $container)
    {
		if (!is_string($value)) {
			return $value;
		}
		
        if (preg_match('{^%([a-zA-Z0-9_.\[\]]+)%$}', $value, $match)) {
			if ($this->accessor->isReadable($container, $match[1])) {
				return $this->accessor->getValue($container, $match[1]);
			} 
			return $match[0];
        }

		$callback = function($matches) use ($container)
		{
			if (!isset($matches[1])) {
				return '%%';
			}
			if ($this->accessor->isReadable($container, $matches[1])) {
				return $this->accessor->getValue($container, $matches[1]);
			}
			return $matches[0];
		};		
		
        $result = preg_replace_callback('{%%|%([a-zA-Z0-9_.\[\]]+)%}', array($this, $callback), $value, -1, $count);

        return $count ? $result : $value;
    }

}
