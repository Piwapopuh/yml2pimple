<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 14.10.2015
 * Time: 15:36
 */

namespace G\Yaml2Pimple\Normalizer;

interface NormalizerInterface
{
    public function normalize($value, $container);
}