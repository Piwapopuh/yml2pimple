<?php

namespace G\Yaml2Pimple\Filter;

interface FilterInterface
{
	public function getFunc();
    
    public function filter($container, $value, $args);
}