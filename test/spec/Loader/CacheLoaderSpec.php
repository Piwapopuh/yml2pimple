<?php

namespace spec\G\Yaml2Pimple\Loader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Config\Resource\FileResource;

class CacheLoaderSpec extends ObjectBehavior
{
    /**
     * @param \G\Yaml2Pimple\Loader\YamlFileLoader $loader
     * @param \G\Yaml2Pimple\ResourceCollection $resources
     */
    public function let($loader, $resources)
    {
        $this->beConstructedWith($loader, $resources);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Loader\CacheLoader');
    }

    /**
     * @param \G\Yaml2Pimple\Loader\YamlFileLoader $loader
     * @param \G\Yaml2Pimple\ResourceCollection $resources
     * @param \G\Yaml2Pimple\FileCache $cache
     */
    public function it_adds_cache_depending_classes_to_resources($loader, $resources, $cache)
    {
        $this->setCache($cache);

        $cache->contains(Argument::any())->shouldBeCalled()->willReturn(false);
        $cache->setResources(Argument::any())->shouldBeCalled();
        $cache->save(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $dependingClasses = array(
            '\G\Yaml2Pimple\Loader\CacheLoader',
            '\G\Yaml2Pimple\Parameter',
            '\G\Yaml2Pimple\Definition',
        );

        foreach ($dependingClasses as $class) {
            $refl = new \ReflectionClass($class);
            $testResource = new FileResource($refl->getFileName());
            $resources->add($testResource)->shouldBeCalled();
        }
        $resources->all()->shouldBeCalled();
        $resources->clear()->shouldBeCalled();

        $data = array();
        $loader->load(Argument::type('string'))->willReturn($data);
        $this->load('test.yaml')->shouldReturn($data);
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
        $this->load('test.yml')->shouldReturn($data);
    }

    public function it_returns_data_from_cache($loader, \G\Yaml2Pimple\FileCache $cache)
    {
        $this->setCache($cache);
        $cache->contains(Argument::any())->shouldBeCalled()->willReturn(true);
        $cache->fetch(Argument::any())->shouldBeCalled();

        $data = array(1,2,3);

        $loader->load('test.yml')->willReturn($data);

        $this->load('test.yml');
        $this->load('test.yml');
    }
}
