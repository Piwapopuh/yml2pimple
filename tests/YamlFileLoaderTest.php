<?php

namespace test;

use G\Yaml2Pimple\Loader\YamlFileLoader;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {

        $locator = $this->getMockBuilder('Symfony\Component\Config\FileLocatorInterface')->disableOriginalConstructor()->getMock();
        $locator->expects(static::any())
                ->method('locate')
                ->will(static::returnValue(__DIR__ . '/fixtures/services.yml'));

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

        static::assertEquals('App', $app->getClass());
        static::assertEquals(array('@Proxy', '%name%'), $app->getArguments());
        static::assertEquals('Curl', $curl->getClass());
        static::assertEquals(null, $curl->getArguments());
        static::assertEquals('Proxy', $proxy->getClass());
        static::assertEquals(array('@Curl'), $proxy->getArguments());
        
    }
}
