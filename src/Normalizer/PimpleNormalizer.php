<?php

namespace G\Yaml2Pimple\Normalizer;

class PimpleNormalizer
{
    private $container;

    /**
     * @param Pimple $pimple
     */
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
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
		
        if (preg_match('{^%([a-z0-9_.]+)%$}', $value, $match)) {
			$key = strtolower($match[1]);
            return isset($container[$key]) ? $container[$key] : $match[0];
        }

		$callback = function ($matches) use ($container)
		{
			if (!isset($matches[1])) {
				return '%%';
			}

			return isset($container[$matches[1]]) ? $container[$matches[1]] : $matches[0];
		};
		
		$result = preg_replace_callback('{%%|%([a-z0-9_.]+)%}', array($this, $callback), $value, -1, $count);

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

        return isset($this->container[$matches[1]]) ? $this->container[$matches[1]] : $matches[0];
    }
}