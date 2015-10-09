<?php

namespace test;

use Prophecy\Argument;
use G\Yaml2Pimple\Loader\YamlFileLoader;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $prophecy = $this->prophesize('Symfony\Component\Config\FileLocatorInterface');
        $prophecy->locate(Argument::type('string'))->willReturn(__DIR__ . '/fixtures/services.yml');
        $locator = $prophecy->reveal();

        /** @var \Symfony\Component\Config\FileLocatorInterface $locator */
        $loader = new YamlFileLoader($locator);
        $conf = $loader->load('services.yml');

        static::assertInternalType('array', $conf);

        static::assertArrayHasKey('parameters', $conf);
        static::assertCount(1, $conf['parameters']);

        $parameter = $conf['parameters'][0];

        static::assertInstanceOf('G\Yaml2Pimple\Parameter', $parameter);

        /** @var \G\Yaml2Pimple\Parameter $parameter */

        static::assertEquals('name', $parameter->getParameterName());
        static::assertEquals('Gonzalo', $parameter->getParameterValue());

        static::assertArrayHasKey('services', $conf);
        static::assertArrayHasKey('App', $conf['services']);
        static::assertArrayHasKey('Curl', $conf['services']);
        static::assertArrayHasKey('Proxy', $conf['services']);

        $app    = $conf['services']['App'];
        $curl   = $conf['services']['Curl'];
        $proxy  = $conf['services']['Proxy'];

        static::assertInstanceOf('G\Yaml2Pimple\Definition', $app);
        static::assertInstanceOf('G\Yaml2Pimple\Definition', $curl);
        static::assertInstanceOf('G\Yaml2Pimple\Definition', $proxy);

        /** @var \G\Yaml2Pimple\Definition $app */
        /** @var \G\Yaml2Pimple\Definition $curl */
        /** @var \G\Yaml2Pimple\Definition $proxy */

        static::assertEquals('\test\fixtures\App', $app->getClass());
        static::assertEquals(array('@Proxy', '%name%'), $app->getArguments());
        static::assertEquals('\test\fixtures\Curl', $curl->getClass());
        static::assertEquals(array(), $curl->getArguments());
        static::assertEquals('\test\fixtures\Proxy', $proxy->getClass());
        static::assertEquals(array('@Curl'), $proxy->getArguments());
        
    }
}
