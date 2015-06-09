<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

class ContainerBuilder
{
    private $container;
	private $normalizer;
	private $factory;
	
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
    }

	public function setNormalizer($normalizer)
	{
		$this->normalizer = $normalizer;
		
		return $this;
	}
	
	protected function addDefaultNormalizer()
	{
		$this->setNormalizer(new PimpleNormalizer($this->container));
	}
	
	public function setFactory($factory)
	{
		$this->factory = $factory;	
		
		return $this;
	}
	
    public function buildFromArray($conf)
    {
		if (is_null($this->normalizer)) {
			$this->addDefaultNormalizer();
		}
			
        foreach ($conf['parameters'] as $parameterName => $parameterValue) {
            $this->container[$parameterName] = $this->normalize($parameterValue);
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
			// the classname can be a parameter reference
			$className = $serviceConf->getClass();			
			$className = $this->normalize($className);

			if ($serviceConf->isSynthetic()) 
			{	
				// we dont know how to create a synthetic service, its set later
				$this->container[$serviceName] = null;		
			}
			else {
				// the instantiator closure function			
				$instantiator = function ($container) use ($serviceConf, $serviceName, $className) {
					// decode the argument list
					$params = array();
					foreach ((array)$serviceConf->getArguments() as $argument) {
						$params[] = $this->normalize($argument);
					}
					
					if ($serviceConf->hasFactory())
					{
						list($factory, $method) = $serviceConf->getFactory();
						$factory = $this->normalize($factory);
						$method = $this->normalize($method);
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
							$params[] = $this->normalize($argument);
						}
						call_user_func_array(array($instance, $method), $params);
					}
					
					// let another object modify this instance
					foreach ((array)$serviceConf->getConfigurators() as $config) {
						list($serviceName, $method) = $config;
						call_user_func_array(array($this->normalize($serviceName), $method), array($instance));
					}

					
					return $instance;				
				};
				
				// create a lazy proxy
				if ($serviceConf->isLazy() && !is_null($this->factory))
				{
					$instantiator = function ($container) use ($className, $instantiator) {	
						return $this->factory->createProxy($className,
							function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($instantiator) {
									$wrappedInstance = call_user_func($instantiator);
								$proxy->setProxyInitializer(null);
								return true;
							}
						);
						
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
	
    public function normalize($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'normalize'], $value);
        }

        return $this->normalizer->normalize($value);
    }
}
