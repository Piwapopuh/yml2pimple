<?php

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ParameterFactory;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

include_once __DIR__ . '/fixtures/App.php';
include_once __DIR__ . '/fixtures/Curl.php';
include_once __DIR__ . '/fixtures/Proxy.php';

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    
    public function testBuilder()
    {
        $parameterName = $this->getMockBuilder('G\Yaml2Pimple\Parameter')->setConstructorArgs(array('', ''))->getMock();
        $parameterName->expects($this->any())->method('getParameterName')->will($this->returnValue('name'));
        $parameterName->expects($this->any())->method('getParameterValue')->will($this->returnValue('Gonzalo'));
        
        $definitionApp = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('App'))->getMock();        
        $definitionApp->expects($this->any())->method('getName')->will($this->returnValue('App'));
        $definitionApp->expects($this->any())->method('getClass')->will($this->returnValue('App'));
        $definitionApp->expects($this->any())->method('getArguments')->will($this->returnValue(array('@Proxy', '%name%')));

        $definitionProxy = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Proxy'))->getMock();
        $definitionProxy->expects($this->any())->method('getName')->will($this->returnValue('Proxy'));
        $definitionProxy->expects($this->any())->method('getClass')->will($this->returnValue('Proxy'));
        $definitionProxy->expects($this->any())->method('getArguments')->will($this->returnValue(array('@Curl')));

        $definitionCurl = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Curl'))->getMock();
        $definitionCurl->expects($this->any())->method('getName')->will($this->returnValue('Curl'));
        $definitionCurl->expects($this->any())->method('getClass')->will($this->returnValue('Curl'));
        $definitionCurl->expects($this->any())->method('getArguments')->will($this->returnValue(null));

        $conf = array(
            'parameters' => array(
                'name' => $parameterName
            ),
            'services'   => array(
                'App'   => $definitionApp,
                'Proxy' => $definitionProxy,
                'Curl'  => $definitionCurl,
            )
        );

        $container = new \Pimple();
        
        $normalizer = $this->getMockBuilder('G\Yaml2Pimple\Normalizer\PimpleNormalizer')->setMethods(null)->getMock();
        
        $builder = new ContainerBuilder($container);    
        // set the normalizers 
        $builder->setNormalizer($normalizer);
        
        $parameterFactory = $this->getMockBuilder('G\Yaml2Pimple\Factory\ParameterFactory')
            ->setMethods(null)
            ->setMockClassName('ParameterFactory')
            ->getMock();
        $builder->setParameterFactory($parameterFactory);   

        $serviceFactory = $this->getMockBuilder('G\Yaml2Pimple\Factory\ServiceFactory')
            ->setMethods(null)
            ->setMockClassName('ServiceFactory')
            ->getMock();
        $builder->setServiceFactory($serviceFactory);
               
        $builder->buildFromArray($conf);

        $this->assertInstanceOf('App', $container['App']);
        $this->assertInstanceOf('Proxy', $container['Proxy']);
        $this->assertInstanceOf('Curl', $container['Curl']);

        $this->assertEquals('Hello Gonzalo', $container['App']->hello());
    }
}
