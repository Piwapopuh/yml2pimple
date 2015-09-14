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
include __DIR__ . '/ClosureExporter.php';

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\Loader\YamlFileLoader;
use G\Yaml2Pimple\Normalizer\ChainNormalizer;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Normalizer\ExpressionNormalizer;
use G\Yaml2Pimple\Normalizer\PropertyAccessPimpleNormalizer;
use Symfony\Component\Config\FileLocator;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

use SuperClosure\Serializer;
use SuperClosure\SerializableClosure;
use SuperClosure\Analyzer\AstAnalyzer;

// set a proxy cache for performance tuning
$config = new ProxyManager\Configuration();
$config->setProxiesTargetDir(__DIR__ . '/cache/');

// then register the autoloader
spl_autoload_register($config->getProxyAutoloader());

$container = new \Pimple();

$normalizer = new ChainNormalizer( array(
	new PimpleNormalizer(), 
	new ExpressionNormalizer()
));

$builder = new ContainerBuilder($container);
// load parameters lazy (try setting to false)
$builder->setParametersLazy(true);
// set the normalizers 
$builder->setNormalizer($normalizer);
// lazy loading proxy manager factory
$builder->setFactory(new LazyLoadingValueHolderFactory($config));

$loader = new YamlFileLoader($builder, new FileLocator(__DIR__));

$loader->load('test.yml');

$fn = $container->raw('Configurator');

// Wrap the closure
Serializer::setExcludeFromContext('that', $builder);
$test = new Serializer(new AstAnalyzer());
$serialized = $test->serialize($fn);
//var_dump($serialized);

// Now it can be serialized
//$serialized = serialize($wrapper);
/*
$serializer = new ClosureExporter();
$serializer->setContextReferences(array('that' => &$builder));

$serialized = $serializer->export($fn);
var_dump($serialized);

$unserialized = $serializer->import($serialized);
*/
$unserialized = $test->unserialize($serialized);
var_dump($unserialized);
$c = $unserialized($container);
var_dump($c);


