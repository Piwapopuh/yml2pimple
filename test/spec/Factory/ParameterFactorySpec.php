<?php

namespace spec\G\Yaml2Pimple\Factory;

use G\Yaml2Pimple\Parameter;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ParameterFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Factory\ParameterFactory');
    }
    
    function it_creates_a_parameter_and_adds_this_to_the_container(Parameter $parameterConf, \Pimple $container)
    {
        $parameterConf->getParameterName()->willReturn('foo');
        $parameterConf->getParameterValue()->willReturn(array(1,2,3));
        $parameterConf->mergeExisting()->willReturn(false);
        
        $container->offsetSet('foo', array(1,2,3))->shouldBeCalled();
        
        $container = $this->create($parameterConf, $container);
    }

    function it_creates_a_array_parameter_and_merges_existing_data(Parameter $parameterConf, \Pimple $container)
    {
        $parameterConf->getParameterName()->willReturn('db.options');
        $parameterConf->getParameterValue()->willReturn(array('host' => 'example.org'));
        $parameterConf->mergeExisting()->willReturn(true);
        
        $container->offsetExists('db.options')->willReturn(true);
        $container->offsetGet('db.options')->willReturn(array(
            'host'      => 'localhost',
            'user'      => 'root',
            'password'  => 'test'
        ));
        $container->offsetSet('db.options', array(
            'host'      => 'example.org',
            'user'      => 'root',
            'password'  => 'test'
        ))->shouldBeCalled();
        
        $container = $this->create($parameterConf, $container);
    }    
}
