<?php
error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED));

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/src/App.php';
include __DIR__ . '/src/Curl.php';
include __DIR__ . '/src/Proxy.php';
include __DIR__ . '/src/Test.php';
include __DIR__ . '/src/Factory.php';

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
use G\Yaml2Pimple\Factory\ProxyParameterFactory;

$container      = new \Pimple();
$builder        = new ContainerBuilder($container);

$ymlLoader      = new YamlFileLoader(
    new FileLocator(__DIR__)
);
$cacheLoader    = new CacheLoader($ymlLoader, __DIR__ . '/cache/');

// set the normalizers 
$builder->setNormalizer(
    new ChainNormalizer(
        array(
            new PimpleNormalizer(),
            new ExpressionNormalizer()
        )
    )
);

$parameterFactory   = new ProxyParameterFactory();
$serviceFactory     = new ServiceFactory(
    new ServiceProxyAdapter(__DIR__ . '/cache/')
);
$serviceFactory->setAspectFactory(
    new AspectProxyAdapter( __DIR__ . '/cache/')
);
// set our loader helper
$builder->setLoader($cacheLoader);
// lazy service proxy factory
$builder->setServiceFactory($serviceFactory);
// lazy parameter proxy factory
$builder->setParameterFactory($parameterFactory);

$builder->load('test.yml');

$app = $container['App'];
echo $app->hello();

