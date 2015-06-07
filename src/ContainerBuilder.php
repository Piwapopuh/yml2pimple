<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;

class ContainerBuilder
{
    private $container;
	private $factory;
	
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
    }

	public function setFactory($factory)
	{
		$this->factory = $factory;	
	}
	
    public function buildFromArray($conf)
    {
			
        foreach ($conf['parameters'] as $parameterName => $parameterValue) {
            $this->container[$parameterName] = $this->decodeArgument($conf['parameters'], $parameterValue);
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
			// the classname can be a parameter reference
			$className = $serviceConf->getClass();			
			$className = $this->decodeArgument($this->container, $className);

			if ($serviceConf->isSynthetic()) 
			{	
				// we dont know how to create a synthetic service, its set later
				$this->container[$serviceName] = null;		
			}
			else {
				// the instantiator closure function			
				$instantiator = function ($c) use ($serviceConf, $serviceName, $className) {
					// decode the argument list
					$params = array();
					foreach ((array)$serviceConf->getArguments() as $argument) {
						$params[] = $this->decodeArgument($c, $argument);
					}
					
					if ($serviceConf->hasFactory())
					{
						list($factory, $method) = $serviceConf->getFactory();
						$factory = $this->decodeArgument($c, $factory);
						$method = $this->decodeArgument($c, $method);
						// let the factory create the instance
						$instance = call_user_func_array(array($factory, $method), $params);
					} else {
						$class = new \ReflectionClass($className);
						// create the instance
						$instance = $class->newInstanceArgs($params);						
					}

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
				if ($serviceConf->isLazy() && !is_null($this->factory))
				{
					$instantiator = function ($c) use ($className, $instantiator) {
						return $this->createProxy( $className, function() use ($c, $instantiator) {
							return $instantiator($c);
						});
					};					
				}
				
				/**
				* By default, each time you get a service, Pimple v1.x returns a
				* new instance of it. If you want the same instance to be returned
				* for all calls, wrap your anonymous function with the share() method
				**/
				if ( "container" == $serviceConf->getScope() )
				{
					$instantiator = $this->container->share( $instantiator );
				}
				
				$this->container[$serviceName] = $instantiator;
			}
        }
    }

    private function decodeArgument($container, $value)
    {		
        if(is_array($value)) {
			$res = array();
			foreach($value as $k => $v) {
				$res[$k] = $this->decodeArgument($container, $v);
			}
			return $res;
		}
		elseif (is_string($value)) {
			// argument references a service
            if (0 === strpos($value, '@')) {
				
				$can_return_null = false;
				$value = substr($value, 1);
				
				// argument is optional
				if (0 === strpos($value, '?')) {
					$can_return_null = true;
					$value = substr($value, 1);
				}
				// our "magic" reference to the container itself
				if ("service_container" == $value) {
					return $container;
				}
				
				// check if service is defined
				if (!isset($container[$value]))
				{
					if ($can_return_null) {
						return null;
					} else {
						throw new \Exception('undefined service ' . $value);
					}
				}
				return $container[$value];			
            } elseif (false !== strpos($value, '%')) {
                return $this->resolveString($container, $value);
            }
        }

        return $value;
    }
	
    public function resolveString($container, $value, array $resolving = array())
    {
        // we do this to deal with non string values (Boolean, integer, ...)
        // as the preg_replace_callback throw an exception when trying
        // a non-string in a parameter value
        if (preg_match('/^%([^%\s]+)%$/', $value, $match)) {
            $key = strtolower($match[1]);

            if (isset($resolving[$key])) {
                throw new \Exception('circular reference error in resolveString');
            }

            $resolving[$key] = true;

            return $container[$key];
        }		
        $self = $this;

        return preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($self, $resolving, $value, $container) {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            $key = strtolower($match[1]);
            if (isset($resolving[$key])) {
                throw new \Exception('circular reference error in resolveString');
            }

            $resolved = $container[$key];

            if (!is_string($resolved) && !is_numeric($resolved)) {
                throw new \Exception(sprintf('A string value must be composed of strings and/or numbers, but found parameter "%s" of type %s inside string value "%s".', $key, gettype($resolved), $value));
            }

            $resolved = (string) $resolved;
            $resolving[$key] = true;

            return $self->resolveString($container, $resolved, $resolving);
        }, $value);
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
