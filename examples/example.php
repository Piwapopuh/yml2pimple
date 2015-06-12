<?php
error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED));
date_default_timezone_set('Europe/Berlin');

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/src/App.php';
include __DIR__ . '/src/Curl.php';
include __DIR__ . '/src/Proxy.php';
include __DIR__ . '/src/Test.php';
include __DIR__ . '/src/Factory.php';

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\YamlFileLoader;
use G\Yaml2Pimple\Normalizer\ChainNormalizer;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Normalizer\PropertyAccessPimpleNormalizer;
use Symfony\Component\Config\FileLocator;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

// set a proxy cache for performance tuning
//$config = new \ProxyManager\Configuration();
//$config->setProxiesTargetDir(__DIR__ . '/cache/');

// then register the autoloader
//spl_autoload_register($config->getProxyAutoloader());

$container = new \Pimple();

$normalizer = new ChainNormalizer( array(
	new PimpleNormalizer($container), 
	new PropertyAccessPimpleNormalizer($container)
));

$builder = new ContainerBuilder($container);
// load parameters lazy (try setting to false)
$builder->setParametersLazy(true);
// set the normalizers 
$builder->setNormalizer($normalizer);
// lazy loading proxy manager factory
$builder->setFactory(new LazyLoadingValueHolderFactory(/*$config*/));

$loader = new YamlFileLoader($builder, new FileLocator(__DIR__));

$loader->load('services.yml');
$loader->load('services2.yml');

$app = $container['App'];
echo $app->hello();

echo $container['desc1'];
echo $container['combined'];
$container['fragment2'] = 'Test';
echo $container['combined'];

echo $container['desc2'];
$container['fragment2'] = 'world';
echo $container['combined2'];
$container['fragment2'] = 'Test';
echo $container['combined2'];