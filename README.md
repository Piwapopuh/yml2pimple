2015-05-29 forked from gonzalo123/yml2pimple

Pimple/Container builder
======
[![Build Status](https://travis-ci.org/gonzalo123/yml2pimple.svg?branch=master)](https://travis-ci.org/gonzalo123/yml2pimple)

With this library we can create a pimple /silex container from this yaml file (mostly similar syntax than Symfony's Dependency Injection Container)

```
parameters:
  app_class: App
  name: Gonzalo
  deep:
    second: [1,2,3]
    third: [a,b,c]

services:
  App:
    # class names can reference parameters
    class: %app_class%
    # prototype returns a new instance each time
    scope: prototype
    # the instance is constructed lazy
    lazy: true
    arguments: [@Proxy, %name%]
    calls:
        - [setName, ['Test']]
        # this is a optional parameter
        - [setDummy, ['@?Dummy']]
    # a configurator can modify the instance
    configurator: ['@Configurator', configure]

    
  Proxy:
    class: Proxy
    lazy: true
    # the instance is created by the factory class
    factory: ['Factory', 'create']
    arguments: [@service_container]
    #arguments: [@Curl]
    
  Curl:
    class:     Curl
    lazy:  true

  Configurator:
    class:     Test

  Factory:
    class: Factory
```



```php
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
// lazy loading proxy manager factory
$builder->setFactory(new LazyLoadingValueHolderFactory($config));
$loader = new YamlFileLoader($builder, new FileLocator(__DIR__));
$loader->load('services.yml');

class Factory
{
	public function create($container = null)
	{
		echo "creating Proxy in Factory";
		return new Proxy($container['Curl']);
	}
}

$app = $container['App'];
echo $app->hello();
```
