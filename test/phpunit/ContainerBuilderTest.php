<?php

namespace test;

use Prophecy\Argument;

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Loader\YamlFileLoader;
use G\Yaml2Pimple\Loader\CacheLoader;

use G\Yaml2Pimple\Normalizer\ChainNormalizer;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Normalizer\ExpressionNormalizer;

use Symfony\Component\Config\FileLocator;

use G\Yaml2Pimple\Proxy\ServiceProxyAdapter;
use G\Yaml2Pimple\Proxy\AspectProxyAdapter;

use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ParameterFactory;
use G\Yaml2Pimple\Factory\ProxyParameterFactory;

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        require_once(__DIR__ . '/fixtures/App.php');
        require_once(__DIR__ . '/fixtures/Proxy.php');
        require_once(__DIR__ . '/fixtures/Curl.php');
    }

    public function tearDown()
    {
    }

    public function testHasNoDebugOutput()
    {
        $container      = new \Pimple();
        $builder        = new ContainerBuilder($container);

        $ymlLoader      = new YamlFileLoader(
            new FileLocator(__DIR__ . '/fixtures')
        );

        // set the normalizers
        $builder->setNormalizer(new PimpleNormalizer());
        $parameterFactory   = new ProxyParameterFactory();
        $serviceFactory     = new ServiceFactory();

        $builder->setLoader($ymlLoader);
        $builder->setServiceFactory($serviceFactory);
        $builder->setParameterFactory($parameterFactory);
        $builder->load('services.yml');

        $app = $container['App'];
        echo $app->hello();

        $this->expectOutputString('Hello Gonzalo');
    }
}
