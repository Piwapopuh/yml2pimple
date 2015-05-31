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
use Pimple\Container;
use G\Yaml2Pimple\ContainerBuilder;
use G\Yaml2Pimple\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

$container = new Container();

$builder = new ContainerBuilder($container);
$locator = new FileLocator(__DIR__);
$loader = new YamlFileLoader($builder, $locator);
$loader->load('services.yml');

$app = $container['App'];
echo $app->hello();
```
