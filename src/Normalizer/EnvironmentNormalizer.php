<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 14.10.2015
 * Time: 15:34
 */

namespace G\Yaml2Pimple\Normalizer;


class EnvironmentNormalizer implements NormalizerInterface
{
    /**
     * @param  string $value
     * @return string
     */
    public function normalize($value, $container)
    {
        if (!is_string($value)) {
            return $value;
        }
        $result = preg_replace_callback('{\$\$|\$([A-Z0-9_]+)\$}', array($this, 'callback'), $value, -1, $count);
        return $count ? $result : $value;
    }
    /**
     * @param  array $matches
     * @return mixed
     */
    protected function callback($matches)
    {
        if (!isset($matches[1])) {
            return $matches[0];
        }
        if (false !== $env = getenv($matches[1])) {
            return $env;
        };
        return $matches[0];
    }
}