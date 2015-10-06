<?php

namespace test;

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Factory\ServiceFactory;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    
    public function testBuilder()
    {
        $parameterName = $this->getMockBuilder('G\Yaml2Pimple\Parameter')->setConstructorArgs(array('', ''))->getMock();
        $parameterName->expects(static::any())->method('getParameterName')->will(static::returnValue('name'));
        $parameterName->expects(static::any())->method('getParameterValue')->will(static::returnValue('Gonzalo'));
        
        $definitionApp = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('App'))->getMock();
        $definitionApp->expects(static::any())->method('getName')->will(static::returnValue('App'));
        $definitionApp->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\App'));
        $definitionApp->expects(static::any())->method('getArguments')->will(static::returnValue(array('@Proxy', '%name%')));

        $definitionProxy = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Proxy'))->getMock();
        $definitionProxy->expects(static::any())->method('getName')->will(static::returnValue('Proxy'));
        $definitionProxy->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\Proxy'));
        $definitionProxy->expects(static::any())->method('getArguments')->will(static::returnValue(array('@Curl')));

        $definitionCurl = $this->getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Curl'))->getMock();
        $definitionCurl->expects(static::any())->method('getName')->will(static::returnValue('Curl'));
        $definitionCurl->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\Curl'));
        $definitionCurl->expects(static::any())->method('getArguments')->will(static::returnValue(null));

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

        /** @var ServiceFactory $serviceFactory */
        $serviceFactory = $this->getMockBuilder('G\Yaml2Pimple\Factory\ServiceFactory')
            ->setMethods(null)
            ->setMockClassName('ServiceFactory')
            ->getMock();
        $builder->setServiceFactory($serviceFactory);
               
        $builder->buildFromArray($conf);

        static::assertInstanceOf('test\fixtures\App', $container['App']);
        static::assertInstanceOf('test\fixtures\Proxy', $container['Proxy']);
        static::assertInstanceOf('test\fixtures\Curl', $container['Curl']);

        static::assertEquals('Hello Gonzalo', $container['App']->hello());
    }
}
