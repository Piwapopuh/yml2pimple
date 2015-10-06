<?php

use G\Yaml2Pimple\Loader\YamlFileLoader;

include_once __DIR__ . '/fixtures/App.php';
include_once __DIR__ . '/fixtures/Curl.php';
include_once __DIR__ . '/fixtures/Proxy.php';

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {

        $locator = $this->getMockBuilder('Symfony\Component\Config\FileLocatorInterface')->disableOriginalConstructor()->getMock();
        $locator->expects($this->any())->method('locate')->will($this->returnValue(__DIR__ . '/fixtures/services.yml'));

        $loader = new YamlFileLoader($locator);
        $conf = $loader->load('services.yml');
        
        $this->assertArrayHasKey('parameters', $conf);
        $this->assertArrayHasKey('parameters', $conf);
        $this->assertCount(1, $conf['parameters']);
        $this->assertEquals('Gonzalo', $conf['parameters'][0]->getParameterValue());

        $this->assertArrayHasKey('services', $conf);
        $this->assertArrayHasKey('App', $conf['services']);
        $this->assertArrayHasKey('Curl', $conf['services']);
        $this->assertArrayHasKey('Proxy', $conf['services']);

        $this->assertInstanceOf('G\Yaml2Pimple\Definition', $conf['services']['App']);
        $this->assertInstanceOf('G\Yaml2Pimple\Definition', $conf['services']['Curl']);
        $this->assertInstanceOf('G\Yaml2Pimple\Definition', $conf['services']['Proxy']);

        $this->assertEquals('App', $conf['services']['App']->getClass());
        $this->assertEquals(array('@Proxy', '%name%'), $conf['services']['App']->getArguments());
        $this->assertEquals('Curl', $conf['services']['Curl']->getClass());
        $this->assertEquals(null, $conf['services']['Curl']->getArguments());
        $this->assertEquals('Proxy', $conf['services']['Proxy']->getClass());
        $this->assertEquals(array('@Curl'), $conf['services']['Proxy']->getArguments());
        
    }
}
