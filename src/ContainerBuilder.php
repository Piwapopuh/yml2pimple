<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Filter\FilterInterface;

class ContainerBuilder
{
    private $container;
	private $normalizer;
	private $factory;
    private $lazy_paramters;
	private $filters;
    
    public function __construct(\Pimple $container)
    {
        $this->container = $container;
        $this->lazy_paramters = false;
        $this->filters = array();
    }

    public function addFilter(FilterInterface $filter)
    {
        $this->filters[$filter->getFunc()] = $filter;
        return $this;
    }
    
    public function parseFilters(&$value, $container)
    {
        $temp = explode('|', $value);
        $value = array_shift($temp);
        $filters = array();
        foreach($temp as $p) {
            if (preg_match('{^([a-z0-9_]+)(\(.*?\))?$}', $p, $match)) {
                $args = preg_split("/[\s,]+/", $match[2]);
                $args = $this->normalize($args, $container);
                $filters[] = array(trim($match[1]), $args);
            }
        }
        return $filters;
    }
    
    public function filter($key, $value, $filters, $container)
    {  
        if (is_array($value)) {
            $res = array();
            foreach($value as $k => $v) {
                $_filters = $this->parseFilters($k, $container);
                $v = $this->filter($k, $v, $_filters, $container);
                $res[$k] = $v;
            }
            $value = $res;    
        }        
        
        foreach((array)$filters as $filter) 
        {
            list($func, $args) = $filter;
            if (isset($this->filters[$func])) {
                $value = $this->filters[$func]->filter($container, $key, $value, $args);
            }
        }         
        return $value;
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
        
        return $this;
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
            $merge = false;
            if (is_array($parameterValue)) {
                $merge = true;
            }
            
            $freeze = true;
            // freeze our value on first access (as singleton)
            if (0 === strpos($parameterName, '$')) { 
                $parameterName = substr($parameterName, 1);
                $freeze = false;
            }
            
            // normalize complex parameters lazy on access or right now?
            if ($this->lazy_paramters)
            {            
                $filters = $this->parseFilters($parameterName, $this->container);
                // we wrap our parameter in a magic proxy class with a __invoke method which is
                // called automatically on access by pimple. this way we have a chance to access
                // parameters as references which could be set later
                // the value is evaluated on every access
                $value = new LazyParameterFactory(function($c) use ($that, $parameterName, $parameterValue, $filters) {
                    $parameterValue = $that->normalize($parameterValue, $c);
                    $parameterValue = $that->filter($parameterName, $parameterValue, $filters, $c);
                    return $parameterValue;
                });
                
                // merge existing data per default
                if (isset($this->container[$parameterName]) && $merge ) {
                    $value = $this->container->extend($parameterName, function($old, $c) use ($parameterName, $value) {
                        
                        if (is_object($value) && method_exists($value, '__invoke')) {
                            $value = $value($c);
                        }
                        
                        return array_replace_recursive($old, $value);
                    });
                } 
                
                // freeze our value on first access (as singleton) this is default
                if ($freeze) {
                    $value = $this->container->share($value);
                }
            } else {
                // without lazy loading we ignore the optional first '$' char
                if (0 === strpos($parameterName, '$')) {
                    $parameterName = substr($parameterName, 1);
                }
                
                $filters = $that->parseFilters($parameterName, $that->container);
                
                $value = $that->normalize($parameterValue, $that->container);
                $value = $that->filter($parameterName, $value, $filters, $that->container);
                
                if (isset($this->container[$parameterName]) && $merge ) {
                    $value = array_replace_recursive($this->container[$parameterName], $value);
                }                
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
				$instantiator = function ($container) use ($that, $serviceConf, $serviceName, $className) {
					// decode the argument list
					$params = array();
					foreach ((array)$serviceConf->getArguments() as $argument) {
						$params[] = $that->normalize($argument, $container);
					}
					
					if ($serviceConf->hasFactory())
					{
						list($factory, $method) = $serviceConf->getFactory();
						$factory = $that->normalize($factory, $container);
						$method = $that->normalize($method, $container);
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
							$params[] = $that->normalize($argument, $container);
						}
						call_user_func_array(array($instance, $method), $params);
					}
					
					// let another object modify this instance
					foreach ((array)$serviceConf->getConfigurators() as $config) {
						list($serviceName, $method) = $config;
						call_user_func_array(array($that->normalize($serviceName, $container), $method), array($instance));
					}

					return $instance;				
				};
				
				// create a lazy proxy
				if ($serviceConf->isLazy() && !is_null($this->factory))
				{
					$instantiator = function ($container) use ($className, $instantiator) {	
						return $this->factory->createProxy($className,
							function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($container, $instantiator) {
									$wrappedInstance = call_user_func($instantiator, $container);
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
