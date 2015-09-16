<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 16.09.2015
 * Time: 13:47
 */

namespace G\Yaml2Pimple\Factory;

interface LazyParameterFactory
{
    public function createProxy(\Closure $func);
}