<?php

namespace test;

use Prophecy\Argument;
use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ParameterFactory;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
    }

    private function getNormalizerStub()
    {
        $prophecy = $this->prophesize('G\Yaml2Pimple\Normalizer\PimpleNormalizer');
        return $prophecy->reveal();
    }

    private function getContainer()
    {
        return new \Pimple();
    }

    private function getConf()
    {
        $prophecy = $this->prophesize('G\Yaml2Pimple\Parameter');
        $parameter = $prophecy->reveal();

        $prophecy = $this->prophesize('G\Yaml2Pimple\Definition');
        $service = $prophecy->reveal();

        return array(
            'parameters' => array(
                'name' => $parameter
            ),
            'services'   => array(
                'App'   => $service,
                'Proxy' => $service,
                'Curl'  => $service,
            )
        );
    }

    public function testBuildFromArray()
    {
        $conf = $this->getConf();
        $container = $this->getContainer();

        $builder = new ContainerBuilder($container);
        // set the normalizers
        $normalizer = $this->getNormalizerStub();
        $builder->setNormalizer($normalizer);

        $fac1 = $this->prophesize('G\Yaml2Pimple\Factory\ParameterFactory');
        $fac1->setNormalizer($normalizer)->shouldBeCalled();
        $fac1->create($conf['parameters']['name'], $container)->willReturn($container)->shouldBeCalled();

        $parameterFactory = $fac1->reveal();

        $fac = $this->prophesize('G\Yaml2Pimple\Factory\ServiceFactory');
        $fac->setNormalizer($normalizer)->shouldBeCalled();
        $fac->create($conf['services']['App'],   $container)->willReturn($container)->shouldBeCalled();
        $fac->create($conf['services']['Proxy'], $container)->willReturn($container)->shouldBeCalled();
        $fac->create($conf['services']['Curl'],  $container)->willReturn($container)->shouldBeCalled();

        $serviceFactory = $fac->reveal();

        /** @var ParameterFactory $parameterFactory */
        $builder->setParameterFactory($parameterFactory);

        /** @var ServiceFactory $serviceFactory */
        $builder->setServiceFactory($serviceFactory);

        $builder->buildFromArray($conf);
    }
}
