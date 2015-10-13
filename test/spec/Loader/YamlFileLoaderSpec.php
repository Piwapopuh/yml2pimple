<?php

namespace spec\G\Yaml2Pimple\Loader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class YamlFileLoaderSpec extends ObjectBehavior
{
    /**
     * @param \Symfony\Component\Config\FileLocator $locator
     * @param \G\Yaml2Pimple\ResourceCollection $resources
     */
    public function let($locator, $resources)
    {
        $this->beConstructedWith($locator, $resources);
        $this->shouldHaveType('G\Yaml2Pimple\Loader\YamlFileLoader');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\Loader\YamlFileLoader');
    }

    public function it_loads_yaml_files($locator, $resources)
    {
        $file = __DIR__ . '/../fixtures/test.yml';
        $locator->locate(Argument::type('string'), Argument::any())->willReturn($file);
        $this->load($file)->shouldBeArray();
        $this->load($file)->shouldHaveKey('parameters');
        $this->load($file)->shouldHaveKey('services');
    }
}
