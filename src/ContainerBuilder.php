<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;

class ContainerBuilder
{
    private $container;
	private $factory;
	private $conf;
	
    public function __construct(\Pimple $container, $factory)
    {
		$this->factory = $factory;
        $this->container = $container;
    }

    public function buildFromArray($conf)
    {
		$this->conf = $conf;
		
        foreach ($conf['parameters'] as $parameterName => $parameterValue) {
            $this->container[$parameterName] = $parameterValue;
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf) {
			// the instantiator closure function			
			$instantiator = function ($c) use ($serviceConf, $serviceName) {

				$class = new \ReflectionClass($serviceConf->getClass());
				$params = [];
				foreach ((array)$serviceConf->getArguments() as $argument) {
					$params[] = $p = $this->decodeArgument($c, $argument);
				}
				
				// create the instance
				$instance = $class->newInstanceArgs($params);
				
				// add some method calls
				foreach ((array)$serviceConf->getCalls() as $call) {
					list($method, $arguments) = $call;
					$params = array();
					foreach((array)$arguments as $argument) {
						$params[] = $this->decodeArgument($c, $argument);
					}
					call_user_func_array(array($instance, $method), $params);
				}
				
				// let another object modify this instance
				foreach ((array)$serviceConf->getConfigurators() as $config) {
					list($serviceName, $method) = $config;
					call_user_func_array(array($this->decodeArgument($c, $serviceName), $method), array($instance));
				}

				
				return $instance;				
			};
			
			// create a lazy proxy
			if ($serviceConf->isLazy())
			{
				$instantiator = function ($c) use ($serviceConf, $serviceName, $instantiator) {
					return $this->createProxy( $serviceConf->getClass(), function() use ($c, $serviceConf, $serviceName, $instantiator) {
						return $instantiator($c);
					});
				};					
			}
			
			/**
			* By default, each time you get a service, Pimple v1.x returns a
			* new instance of it. If you want the same instance to be returned
			* for all calls, wrap your anonymous function with the share() method
			**/
			if ( "prototype" == $serviceConf->getScope() )
			{
				$instantiator = $this->container->share( $instantiator );
			}
			
            $this->container[$serviceName] = $instantiator;
        }
    }

    private function decodeArgument($container, $value)
    {
        if (is_string($value)) {
            if (0 === strpos($value, '@')) {
				// get the definition
				$definition = $this->conf['services'][substr($value, 1)];				
				if ($definition->isLazy())
				{
					$value = $this->createProxy( $definition->getClass(), function() use ($container, $value) {
						return $container[substr($value, 1)];
					});
				} else {
					$value = $container[substr($value, 1)];			
				}
            } elseif (0 === strpos($value, '%')) {
                $value = $container[substr($value, 1, -1)];
            }
        }

        return $value;
    }
	
	private function createProxy($class, $callback)
	{
		return $this->factory->createProxy($class,
			function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($callback) {
				$wrappedInstance = call_user_func($callback);
				$proxy->setProxyInitializer(null);
				return true;
			}
		);		
	}
}
