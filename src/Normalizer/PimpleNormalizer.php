<?php

namespace G\Yaml2Pimple\Normalizer;

use \Symfony\Component\PropertyAccess\PropertyAccess;

class PimpleNormalizer
{
    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
	private $accessor;
	
    public function __construct()
    {
		$this->accessor = PropertyAccess::createPropertyAccessor();		
    }

    /**
     * @param $value
     * @param $container
     *
     * @return mixed|null|string
     *
     * @throws \Exception
     */
    public function normalize($value, $container)
    {	
		if (!is_string($value)) {
			return $value;
		}

		// argument references a service
		if (0 === strpos($value, '@')) {
			
			$can_return_null = false;
			$value = substr($value, 1);
            // did we found a @@ => replace with @
			if (0 === strpos($value, '@')) {
                $value = str_replace('@@', '@', $value);
            } else {
                // argument is optional
                if (0 === strpos($value, '?')) {
                    $can_return_null = true;
                    $value = substr($value, 1);
                }
                // our "magic" reference to the container itself
                if ('service_container' === strtolower($value)) {
                    return $container;
                }

                // check if service is defined
                if (!isset($container[ $value ])) {
                    if ($can_return_null) {
                        return null;
                    } else {
                        throw new \RuntimeException('undefined service ' . $value);
                    }
                }
                return $container[ $value ];
            }
		}

        $accessor = $this->accessor;
		$callback = function ($matches) use ($container, $accessor)
		{
			if (!isset($matches[1])) {
				return '%%';
			}
            
            $key = strtolower($matches[1]);
            
            if (false !== strpos($key, '..')) {
                $key = '[' . str_replace('..', '][', $key) . ']';
                if ($accessor->isReadable($container, $key)) {
                    return $accessor->getValue($container, $key);
                } 
                return $matches[0];
            }              
			return isset($container[$key]) ? $container[$key] : $matches[0];
		};
		$result = preg_replace_callback('{%%|%([a-zA-Z0-9_.]+)%}', $callback, $value, -1, $count);

        return $count ? $result : $value;
    }
}
