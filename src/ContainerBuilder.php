<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

class ContainerBuilder
{
    private $container;
	private $normalizer;
	private $factory;
    private $lazy_paramters;
	
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
        $this->lazy_paramters = false;
    }

    public function setParametersLazy($bool = true)
    {
        $this->lazy_paramters = $bool;
        
        return $this;
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
        
		$that = $this;

        foreach ($conf['parameters'] as $parameterName => $parameterValue) {
            // normalize complex parameters lazy on access or right now?
            if ($this->lazy_paramters) {
                // we wrap our parameter in a magic proxy class with a __invoke method which is
                // called automatically on access by pimple. this way we have a chance to access
                // parameters as references which could be set later
                // the value is evaluated on every access
                $value = new LazyParameterFactory(function($c) use ($that, $parameterValue) {
                    return $that->normalize($parameterValue, $c);
                });                    
                // freeze our value on first access (as singleton)
                if (0 === strpos($parameterName, '$')) { 
                    $value = $this->container->share($value);
                    $parameterName = substr($parameterName, 1);
                }
            } else {
                // without lazy loading we ignore the optional first '$' char
                if (0 === strpos($parameterName, '$')) {
                    $parameterName = substr($parameterName, 1);
                }
                $value = $that->normalize($parameterValue, $c);
            }
            $this->container[$parameterName] = $value;			
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
			// the classname can be a parameter reference
			$className = $serviceConf->getClass();			
			$className = $this->normalize($className, $this->container);

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
						$params[] = $this->normalize($argument, $container);
					}
					
					if ($serviceConf->hasFactory())
					{
						list($factory, $method) = $serviceConf->getFactory();
						$factory = $this->normalize($factory, $container);
						$method = $this->normalize($method, $container);
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
							$params[] = $this->normalize($argument, $container);
						}
						call_user_func_array(array($instance, $method), $params);
					}
					
					// let another object modify this instance
					foreach ((array)$serviceConf->getConfigurators() as $config) {
						list($serviceName, $method) = $config;
						call_user_func_array(array($this->normalize($serviceName, $container), $method), array($instance));
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
	
    public function normalize($value, $container)
    {
        if (is_array($value)) {
            foreach($value as $k => $v) {
				$value[$k] = $this->normalize($v, $container);
			}
			return $value;
        }

        return $this->normalizer->normalize($value, $container);
    }
}
