<?php

namespace spec\G\Yaml2Pimple\Factory;

use G\Yaml2Pimple\Definition;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServiceFactorySpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Factory\ServiceFactory');
    }
}
