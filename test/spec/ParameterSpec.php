<?php

namespace spec\G\Yaml2Pimple;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ParameterSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('foo', 'bar');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Parameter');
    }
    
    public function it_is_frozen_by_default()
    {
        $this->isFrozen()->shouldReturn(true);        
    }
    
    public function it_can_be_dynamic()
    {
        $this->beConstructedWith('$foo', 'bar'); 
        $this->getParameterName()->shouldReturn('foo');
        $this->isFrozen()->shouldReturn(false);
    }
    
    public function it_will_merge_if_value_is_an_array()
    {
        $this->beConstructedWith('foo', array(1));
        $this->mergeExisting()->shouldReturn(true);
    }
}
