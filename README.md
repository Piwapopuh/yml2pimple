2015-05-29 forked from gonzalo123/yml2pimple

Pimple/Container builder
======

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/e5f9f4f3-c8d6-4b08-82c9-65d044cd4f06/mini.png)](https://insight.sensiolabs.com/projects/e5f9f4f3-c8d6-4b08-82c9-65d044cd4f06)

With this library we can create a pimple /silex container from this yaml file (mostly similar syntax than Symfony's Dependency Injection Container)

```
# test import
imports:
  - { resource: test2.yml}

parameters:
  app_class: App
  name: Gonzalo
  deep:
    # parameters can contain other parameters
    first: 'From the deep (%app_class%)'
    second: [1,2,3]
    third: [a,b,c]
  
  desc1: |
   <br><strong>this is a example for a lazy constructed parameter combined from
   fragments defined later, its dynamic and is evaluated every time
   its accessed</strong>
  combined: '<p>Lazy Parameter example: %fragment1% %fragment2%</p>'

  desc2: |
   <br><strong>this is a example for a lazy constructed parameter combined from
   fragments defined later, its like a singleton (the paramater name starts with an $)
   and is frozen after its first accessed</strong>
  $combined2: '<p>Lazy Parameter example2: %fragment1% %fragment2%</p>'
  
  # used by ExpressionNormalizer to evaluate expression vars
  $_normalize:
    test: @service_container

services:
  App:
    # class names can reference parameters
    class: %app_class%
    # prototype returns a new instance each time
    scope: prototype
    # the instance is constructed lazy with a proxy factory
    lazy: true
    arguments: [@Proxy, %name%]
    calls:
        - [setName, ['Test']]
        # this is a optional parameter
        - [setDummy, ['@?Dummy']]
    # a configurator can modify the instance
    configurator: ['@Configurator', configure]
	# add a aspect to method hello()
    aspects:
      - {pointcut: 'hello', advice:'Configurator:beforeHello'}

    
  Proxy:
    class: Proxy
    lazy: true
    # the instance is created by the factory class
    factory: ['Factory', 'create']
    arguments: [@service_container]
    #arguments: [@Curl]
    
  Curl:
    class: Curl
    lazy:  true

  Configurator:
    class:     Test
    # we can access elements of arrays with the symfony property access style (via normalizer)
    arguments: ['%deep..first%', '?"hallo"~" Welt "~test["name"]']

  Factory:
    class: Factory


```



```php
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

$builder->load('test.yml');

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
```
