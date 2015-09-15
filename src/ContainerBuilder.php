<?php

namespace G\Yaml2Pimple;

use ProxyManager\Proxy\LazyLoadingInterface;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

class ContainerBuilder
{
    private $container;
	private $normalizer;
	private $factory;
    private $lazyParameters;
    private $resources;
    private $serializer;
    private $loader;
    private $cacheDir;

    public function __construct(\Pimple $container)
    {
        $this->container 		= $container;
        $this->lazyParameters 	= false;
		$this->resources 		= array();
    }

    public function setParametersLazy($bool = true)
    {
        $this->lazyParameters = $bool;
        
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

    /**
     * @param Serializer $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $loader
     */
    public function setLoader($loader)
    {
        $this->loader = $loader;
    }
	
	public function setFactory($factory)
	{
		$this->factory = $factory;	
		
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFactory()
	{
		return $this->factory;
	}

    protected function collectByKeys($keys = array())
    {
        $data = array();
        foreach($keys as $key) {
            $data[$key] = $this->container->raw($key);
        }
        return $data;
    }

	/**
	 * @return array
	 */
	public function getResources($resource = null)
	{
		if (is_null($resource)) {
			return $this->resources;
		} else {
			return $this->collectByKeys($this->resources[$resource]);
		}
	}

	public function add($k, $v = null)
	{
		if (is_array($k)) {
            foreach($k as $key => $value) {
                $this->container[ $key ] = $value;
            }
        } else {
            $this->container[ $k ] = $v;
        }
	}

    public function load($file)
    {
        $this->loader->load($file, $this);
    }

    public function buildFromArray($conf)
    {
		if (is_null($this->normalizer)) {
			$this->addDefaultNormalizer();
		}
        
		$that = $this;

        foreach ($conf['parameters'] as $parameterConf)
        {
            $parameterName  = $parameterConf->getParameterName();
            $resource       = $parameterConf->getFile();

            // normalize complex parameters lazy on access or right now?
            if ($this->lazyParameters)
            {
                // create a lazy proxy for our parameter
                $value = $this->createParameterProxy($parameterConf);
            } else {
                $value = $this->extractParameter($parameterConf);
            }

            $this->container[$parameterName] = $value;
			// add entry per resource for caching
			$this->resources[$resource][] = $parameterName;
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
            $resource = $serviceConf->getFile();

			if ($serviceConf->isSynthetic())
			{	
				// we dont know how to create a synthetic service, its set later
				$this->container[$serviceName] = null;		
			} else
            {
                // the classname can be a parameter reference
                $className = $this->normalize($serviceConf->getClass(), $this->container);

				// the instantiator closure function			
				$factoryFunction = function ($container) use ($that, $serviceConf, $serviceName, $className)
                {
                    $instance = $that->createInstance($serviceConf, $className, $container);
					// add some method calls
					$that->addMethodCalls($serviceConf->getCalls(), $instance, $container);
					// let another object modify this instance
					$that->addConfigurators($serviceConf->getConfigurators(), $instance, $container);
					return $instance;
				};
				
				// create a lazy proxy
				if ($serviceConf->isLazy())
				{
                    $factoryFunction = $this->createProxy($className, $factoryFunction);
				}
				
				/**
				* By default, each time you get a service, Pimple v1.x returns a
				* new instance of it. If you want the same instance to be returned
				* for all calls, wrap your anonymous function with the share() method
				**/
				if ( "container" == $serviceConf->getScope() )
				{
                    $factoryFunction = $this->container->share( $factoryFunction );
				}
				
				$this->container[$serviceName] = $factoryFunction;
				// add entry per resource for caching
				$this->resources[$resource][] = $serviceName;
			}
        }
    }

    protected function createInstance(Definition $serviceConf, $className, $container)
    {
        // decode the argument list
        $params = $this->normalize($serviceConf->getArguments(), $container);

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

	protected function addMethodCalls(array $calls = array(), &$instance, $container)
	{
		foreach ($calls as $call) {
			list($method, $arguments) = $call;
			$params = $this->normalize($arguments, $container);
			call_user_func_array(array($instance, $method), $params);
		}
	}

	protected function addConfigurators(array $configs = array(), &$instance, $container)
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

	protected function createProxy($className, $func)
	{
        if (is_null($this->factory)) {
            return $func;
        }

		$that = $this;
		return function ($container) use ($that, $className, $func) {
			return $that->getFactory()->createProxy($className,
				function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($container, $func) {
					$wrappedInstance = call_user_func($func, $container);
					$proxy->setProxyInitializer(null);
					return true;
				}
			);
		};
	}

    protected function createParameterProxy(Parameter $parameterConf)
    {
        $parameterName = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        // we wrap our parameter in a magic proxy class with a __invoke method which is
        // called automatically on access by pimple. this way we have a chance to access
        // parameters as references which could be set later
        // the value is evaluated on every access
        $that = $this;
        $value = new LazyParameterFactory(function($container) use ($that, $parameterName, $parameterValue) {
            $parameterValue = $that->normalize($parameterValue, $container);
            return $parameterValue;
        }, $this->serializer);

        // merge existing data per default
        if (isset($this->container[$parameterName]) && $parameterConf->mergeExisting() ) {
            // create a wrapper function for lazy calling
            $value = $this->container->extend($parameterName, function($old, $container) use ($parameterName, $value) {
                // extract the value from our LazyParameterFactory
                if (is_object($value) && method_exists($value, '__invoke')) {
                    $value = $value($container);
                }
                // merge existing data with new
                return array_replace_recursive($old, $value);
            });
        }

        // freeze our value on first access (as singleton) this is default
        if ($parameterConf->isFrozen()) {
            $value = $this->container->share($value);
        }
        return $value;
    }

    protected function extractParameter(Parameter $parameterConf)
    {
        $parameterName = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        $value = $this->normalize($parameterValue, $this->container);

        if (isset($this->container[$parameterName]) && $parameterConf->mergeExisting() ) {
            $value = array_replace_recursive($this->container[$parameterName], $value);
        }

        return $value;
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

    public function serialize($data)
    {
        $data = $this->serializer->wrapData($data);
        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $data = $this->serializer->unwrapData($data);
        return $data;
    }
}
