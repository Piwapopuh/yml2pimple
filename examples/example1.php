<?php
error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED));
include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/src/App.php';
include __DIR__ . '/src/Curl.php';
include __DIR__ . '/src/Proxy.php';
include __DIR__ . '/src/Test.php';

use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

$container = new \Pimple();
$builder = new ContainerBuilder($container, new LazyLoadingValueHolderFactory());
$loader = new YamlFileLoader($builder, new FileLocator(__DIR__));
$loader->load('services.yml');

$app = $container['App'];
echo $app->hello();

$app2 = $container['App'];
echo $app2->hello();
$app2->setName('B');


echo $app->hello();
