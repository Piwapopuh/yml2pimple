<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 05.10.2015
 * Time: 09:59
 */

namespace G\Yaml2Pimple\Handler;

use G\Yaml2Pimple\Definition;

interface TagHandlerInterface
{
    public function process(Definition $serviceConf, array $tags, \Pimple $container);
}
