<?php
error_reporting(E_ALL & ~(E_NOTICE | E_WARNING | E_STRICT | E_DEPRECATED));
ini_set('display_errors','On');
ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 10000);
ini_set('xdebug.max_nesting_level', 200); 
date_default_timezone_set('Europe/Berlin');

include __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/src/App.php';
include __DIR__ . '/src/Curl.php';
include __DIR__ . '/src/Proxy.php';
include __DIR__ . '/src/Test.php';

class Factory
{
	public function create($container = null)
	{
		echo "creating Proxy in Factory";
		return new Proxy($container['Curl']);
	}
}


use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

// set a proxy cache for performance tuning
$config = new \ProxyManager\Configuration();
$config->setProxiesTargetDir(__DIR__ . '/cache/');

// then register the autoloader
spl_autoload_register($config->getProxyAutoloader());

$container = new \Pimple();
$builder = new ContainerBuilder($container);
$builder->setFactory(new LazyLoadingValueHolderFactory($config));
$loader = new YamlFileLoader($builder, new FileLocator(__DIR__));
$loader->load('services.yml');

$app = $container['App'];
echo $app->hello();

var_dump($app);

$app2 = $container['App'];

var_dump($app2);
echo $app2->hello();

$app2->setName('B');

echo $app->hello();


