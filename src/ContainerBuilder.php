<?php

namespace G\Yaml2Pimple;

use Pimple\Container;
use ProxyManager\Proxy\LazyLoadingInterface;

class ContainerBuilder
{
    private $container;
	private $factory;
	private $conf;
	
    public function __construct(Container $container, $factory)
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
            $this->container[$serviceName] = function () use ($serviceConf, $serviceName) {
                $class = new \ReflectionClass($serviceConf->getClass());
				$params = [];
				foreach ((array)$serviceConf->getArguments() as $argument) {
                    $params[] = $p = $this->decodeArgument($argument);
                }
				// create the instance
                $instance = $class->newInstanceArgs($params);
				// add some method calls
				foreach ((array)$serviceConf->getCalls() as $call) {
					list($method, $arguments) = $call;
                    $params = array();
					foreach((array)$arguments as $argument) {
						$params[] = $this->decodeArgument($argument);
					}
					call_user_func_array(array($instance, $method), $params);
                }
				// let another object modify this instance
				foreach ((array)$serviceConf->getConfigurators() as $config) {
					list($serviceName, $method) = $config;
					call_user_func_array(array($this->decodeArgument($serviceName), $method), array($instance));
                }

				
				return $instance;				
            };
        }
    }

    private function decodeArgument($value)
    {
        if (is_string($value)) {
            if (0 === strpos($value, '@')) {
				// create the instantiator closure
                $realInstantiator = function() use ($value) {
					return $this->container[substr($value, 1)];
				};
				// get the definition
				$definition = $this->conf['services'][substr($value, 1)];
				// create the lazy proxy
				$value = $this->factory->createProxy(
					$definition->getClass(),
					function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($realInstantiator) {
						$wrappedInstance = call_user_func($realInstantiator);
						$proxy->setProxyInitializer(null);
						return true;
					}
				);				
				
            } elseif (0 === strpos($value, '%')) {
                $value = $this->container[substr($value, 1, -1)];
            }
        }

        return $value;
    }
}
