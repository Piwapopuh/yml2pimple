<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Definition;

class ServiceFactory extends AbstractServiceFactory
{
    protected $proxyFactory;
    
    public function __construct($proxyFactory = null)
    {
        $this->proxyFactory = $proxyFactory;
    }
    
	public function create(Definition $serviceConf, \Pimple &$container)
	{
        $serviceName = $serviceConf->getName();
        
        $factoryFunction = null;
        
        if (!$serviceConf->isSynthetic())
        {
            // we dont know how to create a synthetic service, its set later
            // the classname can be a parameter reference
            $className = $this->normalize($serviceConf->getClass(), $container);
            
            $that = $this;
            
            // the instantiator closure function
            $factoryFunction = function ($c) use ($that, $serviceConf, $className)
            {
                $instance = $that->createInstance($serviceConf, $className, $c);
                
                // add some method calls
                $that->addMethodCalls($serviceConf->getCalls(), $instance, $c);
                
                // let another object modify this instance
                $that->addConfigurators($serviceConf->getConfigurators(), $instance, $c);
                
                return $instance;
            };

            // create a lazy proxy
            if ($serviceConf->isLazy() && !is_null($this->proxyFactory))
            {
                $factoryFunction = $this->proxyFactory->createProxy($className, $factoryFunction);
            }

            /**
             * By default, each time you get a service, Pimple v1.x returns a
             * new instance of it. If you want the same instance to be returned
             * for all calls, wrap your anonymous function with the share() method
             **/
            if ( "container" == $serviceConf->getScope() )
            {
                $factoryFunction = $container->share( $factoryFunction );
            }
        }
        $container[$serviceName] = $factoryFunction;
    }

    public function createInstance(Definition $serviceConf, $className, $container)
    {
        // decode the argument list
        $params = (array)$this->normalize($serviceConf->getArguments(), $container);

        if ($serviceConf->hasFactory())
        {
            $instance = $this->createFromFactory($serviceConf->getFactory(), $params, $container);
        } else
        {
            $class = new \ReflectionClass($className);
            // create the instance
            $instance = $class->newInstanceArgs($params);
        }
        return $instance;
    }

	protected function createFromFactory(array $factory = array(), $params, $container)
	{
		list($factory, $method) = $factory;
		$factory 	= $this->normalize($factory, $container);
		$method 	= $this->normalize($method, $container);
		// let the factory create the instance
		return call_user_func_array(array($factory, $method), $params);
	}

	public function addMethodCalls(array $calls = array(), &$instance, $container)
	{
		foreach ($calls as $call) {
			list($method, $arguments) = $call;
			$params = $this->normalize($arguments, $container);
			call_user_func_array(array($instance, $method), $params);
		}
	}

	public function addConfigurators(array $configs = array(), &$instance, $container)
	{
		// let another object modify this instance
		foreach ($configs as $config) {
			$configurator 	= array_shift($config);
			$method 		= array_shift($config);
			$params 		= $this->normalize($config, $container);
			array_unshift($params, $instance);
			call_user_func_array(array($this->normalize($configurator, $container), $method), $params);
		}
	}    
}