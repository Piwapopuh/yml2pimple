<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 16.09.2015
 * Time: 13:47
 */

namespace G\Yaml2Pimple\Proxy;

interface ServiceProxyInterface
{
    public function createProxy($className, \Closure $func);
}
