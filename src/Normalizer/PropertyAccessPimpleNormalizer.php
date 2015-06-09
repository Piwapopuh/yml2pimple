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
    public function normalize($value)
    {
		if (!is_string($value)) {
			return $value;
		}
		
        if (preg_match('{^%([a-z0-9_.\[\]]+)%$}', $value, $match)) {
			if ($this->accessor->isReadable($this->pimple, $match[1])) {
				return $this->accessor->getValue($this->pimple, $match[1]);
			} 
			return $match[0];
        }

        $result = preg_replace_callback('{%%|%([a-z0-9_.\[\]]+)%}', array($this, 'callback'), $value, -1, $count);

        return $count ? $result : $value;
    }

    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return '%%';
        }
		if ($this->accessor->isReadable($this->pimple, $matches[1])) {
			return $this->accessor->getValue($this->pimple, $matches[1]);
		}
		return $matches[0];
    }
}
