<?php

namespace spec\G\Yaml2Pimple;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContainerBuilderSpec extends ObjectBehavior
{
    public function let($container)
    {
        $container->beADoubleOf('\Pimple');
        $this->beConstructedWith($container);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('G\Yaml2Pimple\ContainerBuilder');
    }

    /**
     * @param \Pimple $container
     * @param \G\Yaml2Pimple\Normalizer\PimpleNormalizer $normalizer
     * @param \G\Yaml2Pimple\Factory\ParameterFactory $parameterFactory
     * @param \G\Yaml2Pimple\Factory\ServiceFactory $serviceFactory
     * @param \G\Yaml2Pimple\Parameter $parameter
     * @param \G\Yaml2Pimple\Definition $service
     */
    public function it_can_build_the_container_from_array_of_definitions($container, $normalizer, $parameterFactory, $serviceFactory, $parameter, $service)
    {
        $conf = array(
            'parameters' => array(
                'name' => $parameter
            ),
            'services'   => array(
                'App'   => $service,
                'Proxy' => $service,
                'Curl'  => $service,
            )
        );

        $this->setNormalizer($normalizer);

        $parameterFactory->setNormalizer($normalizer)->shouldBeCalled();
        $parameterFactory->create($parameter, $container)->willReturn($container)->shouldBeCalledTimes(1);

        /** @var ParameterFactory $parameterFactory */
        $this->setParameterFactory($parameterFactory);

        $serviceFactory->setNormalizer($normalizer)->shouldBeCalled();
        $serviceFactory->create($service, $container)->willReturn($container)->shouldBeCalledTimes(3);

        /** @var ServiceFactory $serviceFactory */
        $this->setServiceFactory($serviceFactory);

        $this->buildFromArray($conf);
    }
}
