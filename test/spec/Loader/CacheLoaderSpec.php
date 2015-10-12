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
    
    public function it_returns_data_from_loader($loader, \G\Yaml2Pimple\FileCache $cache)
    {
        $this->setCache($cache);
        
        $cache->setResources(Argument::any())->shouldBeCalled();
        $cache->save(Argument::type('string'), Argument::type('array'))->shouldBeCalled();
        $cache->contains(Argument::any())->shouldBeCalled()->willReturn(false);
        $cache->fetch(Argument::any())->shouldNotBeCalled();
        
        $data = array(1,2,3);
        $loader->load('test.yml')->willReturn($data);
        $loader->getResources()->willReturn(array('test.yml'));
        $this->load('test.yml')->shouldReturn($data);
    }
    
    public function it_returns_data_from_cache($loader, \G\Yaml2Pimple\FileCache $cache)
    {
        $this->setCache($cache);
        $cache->contains(Argument::any())->shouldBeCalled()->willReturn(true);
        $cache->fetch(Argument::any())->shouldBeCalled();
        
        $data = array(1,2,3);
        
        $loader->load('test.yml')->willReturn($data);
        $loader->getResources()->willReturn(array('test.yml'));
               
        $this->load('test.yml');
        $this->load('test.yml');
    }    
}
