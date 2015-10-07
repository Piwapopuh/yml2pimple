<?php

namespace test;

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ParameterFactory;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    private function getNormalizerStub()
    {
        return static::getMockBuilder('G\Yaml2Pimple\Normalizer\PimpleNormalizer')
            ->setMethods(null)
            ->getMock();
    }

    private function getContainer()
    {
        return new \Pimple();
    }

    private function getConf()
    {
        $parameterName = static::getMockBuilder('G\Yaml2Pimple\Parameter')->setConstructorArgs(array('', ''))->getMock();
        $parameterName->expects(static::any())->method('getParameterName')->will(static::returnValue('name'));
        $parameterName->expects(static::any())->method('getParameterValue')->will(static::returnValue('Gonzalo'));

        $definitionApp = static::getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('App'))->getMock();
        $definitionApp->expects(static::any())->method('getName')->will(static::returnValue('App'));
        $definitionApp->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\App'));
        $definitionApp->expects(static::any())->method('getArguments')->will(static::returnValue(array(null, '%name%')));

        $definitionProxy = static::getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Proxy'))->getMock();
        $definitionProxy->expects(static::any())->method('getName')->will(static::returnValue('Proxy'));
        $definitionProxy->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\Proxy'));
        $definitionProxy->expects(static::any())->method('getArguments')->will(static::returnValue(array('@Curl')));

        $definitionCurl = static::getMockBuilder('G\Yaml2Pimple\Definition')->setConstructorArgs(array('Curl'))->getMock();
        $definitionCurl->expects(static::any())->method('getName')->will(static::returnValue('Curl'));
        $definitionCurl->expects(static::any())->method('getClass')->will(static::returnValue('test\fixtures\Curl'));
        $definitionCurl->expects(static::any())->method('getArguments')->will(static::returnValue(null));

        return array(
            'parameters' => array(
                'name' => $parameterName
            ),
            'services'   => array(
                'App'   => $definitionApp,
                'Proxy' => $definitionProxy,
                'Curl'  => $definitionCurl,
            )
        );
    }

    public function testSetParameterFactory()
    {
        $builder = new ContainerBuilder($this->getContainer());

        $normalizer = $this->getNormalizerStub();

        // set the normalizers
        $builder->setNormalizer($normalizer);
        
        $parameterFactory = static::getMockBuilder('G\Yaml2Pimple\Factory\ParameterFactory')
            ->setMethods(array('setNormalizer'))
            ->getMock();

        $parameterFactory->expects(static::once())
            ->method('setNormalizer')
            ->with(static::identicalTo($normalizer));

        /** @var ParameterFactory $parameterFactory */
        $builder->setParameterFactory($parameterFactory);
    }

    public function testSetServiceFactory()
    {
        $builder = new ContainerBuilder($this->getContainer());

        $normalizer = $this->getNormalizerStub();

        // set the normalizers
        $builder->setNormalizer($normalizer);

        $serviceFactory = static::getMockBuilder('G\Yaml2Pimple\Factory\ServiceFactory')
            ->setMethods(array('setNormalizer'))
            ->getMock();

        $serviceFactory->expects(static::once())
            ->method('setNormalizer')
            ->with(static::identicalTo($normalizer));

        /** @var ServiceFactory $serviceFactory */
        $builder->setServiceFactory($serviceFactory);
    }

    public function testLoad()
    {
        $conf = $this->getConf();

        $loader = static::getMockBuilder('G\Yaml2Pimple\Loader\YamlFileLoader')
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $loader->expects(static::once())
            ->method('load')
            ->with(static::equalTo('test.yml'))
            ->will(static::returnValue($conf));

        $builder = static::getMockBuilder('G\Yaml2Pimple\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('buildFromArray'))
            ->getMock();
        
        $builder->expects(static::once())
            ->method('buildFromArray')
            ->with(static::identicalTo($conf));

        /** @var ContainerBuilder $builder */
        $builder->setLoader($loader);
        $builder->load('test.yml');
    }

    public function testBuildFromArray()
    {
        $conf = $this->getConf();
        $container = $this->getContainer();

        $builder = new ContainerBuilder($container);
        // set the normalizers
        $normalizer = $this->getNormalizerStub();
        $builder->setNormalizer($normalizer);

        $parameterFactory = static::getMockBuilder('G\Yaml2Pimple\Factory\ParameterFactory')
            ->setMethods(array('create'))
            ->getMock();

        $parameterFactory->expects(static::once())
            ->method('create')
            ->with(static::identicalTo($conf['parameters']['name']), static::identicalTo($container))
            ->will(static::returnArgument(1));

        $serviceFactory = static::getMockBuilder('G\Yaml2Pimple\Factory\ServiceFactory')
            ->setMethods(array('create'))
            ->getMock();

        $serviceFactory->expects(static::exactly(3))
            ->method('create')
            ->withConsecutive(
                array(static::identicalTo($conf['services']['App']), static::identicalTo($container)),
                array(static::identicalTo($conf['services']['Proxy']), static::identicalTo($container)),
                array(static::identicalTo($conf['services']['Curl']), static::identicalTo($container))
            )
            ->will(static::returnArgument(1));

        /** @var ParameterFactory $parameterFactory */
        $builder->setParameterFactory($parameterFactory);

        /** @var ServiceFactory $serviceFactory */
        $builder->setServiceFactory($serviceFactory);

        $builder->buildFromArray($conf);
    }
}
