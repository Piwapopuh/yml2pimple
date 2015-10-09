<?php

namespace test;

use G\Yaml2Pimple\Definition;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinition()
    {
        $definition = new Definition('Bar');

        $definition->setClass('Foo');
        static::assertEquals('Foo', $definition->getClass());

        $arguments = array(1, 2, 3);
        static::assertFalse($definition->hasArguments());
        $definition->setArguments($arguments);
        static::assertTrue($definition->hasArguments());
        static::assertEquals($arguments, $definition->getArguments());

        $call = array('method', array(1,2));
        static::assertFalse($definition->hasCalls());
        $definition->addCall($call);
        static::assertTrue($definition->hasCalls());
        static::assertEquals(array($call), $definition->getCalls());
    }
}
