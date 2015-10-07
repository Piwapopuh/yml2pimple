<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Parameter;

abstract class AbstractParameterFactory extends AbstractFactory
{
	abstract public function create(Parameter $parameterConf, \Pimple $container);
}