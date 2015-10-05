<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Definition;

class ServiceFactory extends AbstractServiceFactory
{
    protected $proxyFactory;
    protected $definitionHandlers;

    public function __construct($proxyFactory = null, $definitionHandlers = array())
    {
        $this->proxyFactory = $proxyFactory;
        $this->definitionHandlers = $definitionHandlers;
    }
    
	public function create(Definition $serviceConf, \Pimple &$container)
	{
        $serviceName = $serviceConf->getName();
        
        $factoryFunction = null;
        
        if (!$serviceConf->isSynthetic())
        {
            // we dont know how to create a synthetic service, its set later
            // the classname can be a parameter reference
            $serviceConf->setClass($this->normalize($serviceConf->getClass(), $container));
            
            $that = $this;
            
            // the instantiator closure function
            $factoryFunction = function ($c) use ($that, $serviceConf)
            {
                $instance = $that->createInstance($serviceConf, $c);
                
                // add some method calls
                $that->addMethodCalls($serviceConf->getCalls(), $instance, $c);
                
                // let another object modify this instance
                $that->addConfigurators($serviceConf->getConfigurators(), $instance, $c);

                return $instance;
            };

            $tags = $serviceConf->getTags();
            foreach ($this->definitionHandlers as $handler) {
                $handler->process($serviceConf, $tags, $container);
            }

            // create a lazy proxy
            if ($serviceConf->isLazy() && !is_null($this->proxyFactory))
            {
                $factoryFunction = $this->proxyFactory->createProxy($serviceConf->getClass(), $factoryFunction);
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

    public function createInstance(Definition $serviceConf, $container)
    {
        // decode the argument list
        $params = (array)$this->normalize($serviceConf->getArguments(), $container);

        if ($serviceConf->hasFactory())
        {
            $instance = $this->createFromFactory($serviceConf->getFactory(), $params, $container);
        } else
        {
            $class = new \ReflectionClass($serviceConf->getClass());
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