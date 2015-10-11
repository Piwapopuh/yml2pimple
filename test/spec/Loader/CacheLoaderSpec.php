<?php

namespace spec\G\Yaml2Pimple\Loader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CacheLoaderSpec extends ObjectBehavior
{
    public function let(\G\Yaml2Pimple\Loader\YamlFileLoader $loader)
    {
        $this->beConstructedWith($loader);
    }
    
    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Loader\CacheLoader');
    }
    
    public function it_returns_data_from_loader($loader)
    {
        $data = array(1,2,3);       
        $loader->load('test.yml')->willReturn($data);       
        $this->load('test.yml')->shouldReturn($data);
    }
}
