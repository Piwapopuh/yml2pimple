<?php

namespace test;

use G\Yaml2Pimple\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinition()
    {
        $definition = new Definition('Bar');
        $definition->setClass('Foo');
        $definition->setArguments(array(1, 2, 3));

        static::assertEquals('Foo', $definition->getClass());
        static::assertEquals(array(1, 2, 3), $definition->getArguments());
    }
}
