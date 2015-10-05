<?php
error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED));
// xdebug settings
ini_set('display_errors','On');
ini_set('xdebug.var_display_max_depth', 10);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 100000);
ini_set('xdebug.collect_params', 3);
date_default_timezone_set('Europe/Berlin');

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
use G\Yaml2Pimple\Factory\ParameterFactory;
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


$then = microtime(true);
for($i = 1; $i <= 100; $i++) {
    $builder->load('test.yml');
}
$now = microtime(true);
echo  sprintf("Elapsed:  %f", ($now-$then));

var_dump($container);

$app = $container['App'];
echo $app->hello();
//var_dump($app);

/*
$fn = $container->raw('name');

// Wrap the closure
Serializer::setExcludeFromContext('that', $builder);
$test = new Serializer(new AstAnalyzer());
var_dump($test->getData($fn, true));

$serialized = $test->serialize($fn);
//var_dump($serialized);

$unserialized = $test->unserialize($serialized);
$c = $unserialized($container);
var_dump($c());
*/

