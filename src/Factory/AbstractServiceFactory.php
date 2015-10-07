<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Definition;

abstract class AbstractServiceFactory extends AbstractFactory
{
	abstract public function create(Definition $serviceConf, \Pimple $container);
}