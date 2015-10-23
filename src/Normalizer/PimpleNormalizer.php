<?php

namespace G\Yaml2Pimple\Normalizer;

class PimpleNormalizer implements NormalizerInterface
{

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
            $value           = substr($value, 1);
            // did we found a @@ => replace with @
            if (0 === strpos($value, '@')) {
                $value = str_replace('@@', '@', $value);
            } else {
                // argument is optional
                if (0 === strpos($value, '?')) {
                    $can_return_null = true;
                    $value           = substr($value, 1);
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

        if (preg_match('{^%([a-zA-Z0-9_.]+)%$}', $value, $match)) {
            $key = strtolower($match[1]);

            if (preg_match('{(\.\.)}', $key, $test)) {
                $keys = explode('..', $key);
                $element = $container;
                while ($key = array_shift($keys)) {
                    if( isset($element[$key])) {
                        $element = $element[$key];
                    } else {
                        throw new \RuntimeException('undefined key ' . $key);
                    }
                }
                return $element;
            }

            return isset($container[ $key ]) ? $container[ $key ] : $match[0];
        }


        $callback = function ($matches) use ($container) {
            if (!isset($matches[1])) {
                return '%%';
            }

            $key = strtolower($matches[1]);
            if (preg_match('{(\.\.)}', $key, $test)) {
                $keys = explode('..', $key);
                $element = $container;
                while ($key = array_shift($keys)) {
                    if( isset($element[$key])) {
                        $element = $element[$key];
                    } else {
                        throw new \RuntimeException('undefined key ' . $key);
                    }
                }
                return $element;
            }
            return isset($container[ $key ]) ? $container[ $key ] : $matches[0];
        };
        $result   = preg_replace_callback('{%%|%([a-zA-Z0-9_.]+)%}', $callback, $value, -1, $count);
        return $count ? $result : $value;
    }
}
