<?php

namespace spec\G\Yaml2Pimple;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContainerBuilderSpec extends ObjectBehavior
{
    public function let(\Pimple $container)
    {
        $this->beConstructedWith($container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\ContainerBuilder');
    }
}
