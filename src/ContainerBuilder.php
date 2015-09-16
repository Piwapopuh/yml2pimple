<?php

namespace G\Yaml2Pimple;

use G\Yaml2Pimple\Factory\LazyParameterFactory;
use G\Yaml2Pimple\Factory\LazyServiceFactory;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
class ContainerBuilder
{
    private $container;
	private $normalizer;

    /**
     * @var LazyServiceFactory $factory
     */
    private $factory;

    /**
     * @var LazyParameterFactory $parameterFactory
     */
    private $parameterFactory;
    private $lazyParameters;
    private $resources;
    private $serializer;
    private $loader;
    private $nestedLevel;
    private $maxNestedLevel;

    public function __construct(\Pimple $container)
    {
        $this->container 		= $container;
        $this->lazyParameters 	= false;
		$this->resources 		= array();
        $this->nestedLevel      = array();
        $this->maxNestedLevel   = 50;
    }

    /**
     * @param mixed $parameterFactory
     */
    public function setParameterFactory(LazyParameterFactory $parameterFactory)
    {
        $this->parameterFactory = $parameterFactory;
    }

    public function setMaxNestedLevel($maxNestedLevel)
    {
        $this->maxNestedLevel = $maxNestedLevel;
    }

    public function getNestedLevel($name)
    {
        return isset($this->nestedLevel[$name]) ? $this->nestedLevel[$name] : 0;
    }

    public function setNestedLevel($name, $nestedLevel)
    {
        $this->nestedLevel[$name] = $nestedLevel;
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
     * @param $serializer
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
	
	public function setFactory(LazyServiceFactory $factory)
	{
		$this->factory = $factory;	
		
		return $this;
	}

    protected function collectByKeys($keys = array())
    {
        $data = array();
        foreach($keys as $key) {
            $data[$key] = $this->container->raw($key);
        }
        return $data;
    }

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
        $conf = $this->loader->load($file);
        $this->buildFromArray($conf);
    }

    public function buildFromArray($conf)
    {
		if (is_null($this->normalizer)) {
			$this->addDefaultNormalizer();
		}
        
        foreach ($conf['parameters'] as $parameterConf)
        {
            if ($parameterConf instanceof Parameter) {
                $parameterName = $parameterConf->getParameterName();
                $this->container[ $parameterName ] = $this->constructParameter($parameterConf);
                // add entry per resource for caching
                $resource = $parameterConf->getFile();
                $this->resources[ $resource ][] = $parameterName;
            }
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
            if ($serviceConf instanceof Definition) {
                $this->container[ $serviceName ] = $this->constructService($serviceConf);
                // add entry per resource for caching
                $resource = $serviceConf->getFile();
                $this->resources[ $resource ][] = $serviceName;
            }
        }
    }

    public function constructParameter(Parameter $parameterConf)
    {
        // normalize complex parameters lazy on access or right now?
        if ($this->lazyParameters && !is_null($this->parameterFactory))
        {
            // create a lazy proxy for our parameter
            $value = $this->createParameterProxy($parameterConf);
        } else {
            $value = $this->extractParameter($parameterConf);
        }
        return $value;
    }

    /**
     * @param Definition $serviceConf
     * @return \Closure|null
     */
    public function constructService(Definition $serviceConf)
    {
        $factoryFunction = null;
        if (!$serviceConf->isSynthetic())
        {
            // we dont know how to create a synthetic service, its set later
            // the classname can be a parameter reference
            $className = $this->normalize($serviceConf->getClass(), $this->container);
            $that = $this;
            // the instantiator closure function
            $factoryFunction = function ($container) use ($that, $serviceConf, $className)
            {
                $instance = $that->createInstance($serviceConf, $className, $container);
                // add some method calls
                $that->addMethodCalls($serviceConf->getCalls(), $instance, $container);
                // let another object modify this instance
                $that->addConfigurators($serviceConf->getConfigurators(), $instance, $container);
                return $instance;
            };

            // create a lazy proxy
            if ($serviceConf->isLazy() && !is_null($this->factory))
            {
                $factoryFunction = $this->factory->createProxy($className, $factoryFunction);
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
        }
        return $factoryFunction;
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

    /**
     * @param Parameter $parameterConf
     * @return \Closure|LazyParameterFactory
     */
    protected function createParameterProxy(Parameter $parameterConf)
    {
        $parameterName = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        // we wrap our parameter in a magic proxy class with a __invoke method which is
        // called automatically on access by pimple. this way we have a chance to access
        // parameters as references which could be set later
        // the value is evaluated on every access
        $that = $this;
        $value = function($container) use ($that, $parameterName, $parameterValue) {
            $parameterValue = $that->normalize($parameterValue, $container);
            return $parameterValue;
        };

        $value = $this->parameterFactory->createProxy($value);

        $nestedLevel = $this->getNestedLevel($parameterName);
        // merge existing data per default
        if ($parameterConf->mergeExisting() && $nestedLevel < $this->maxNestedLevel && isset($this->container[$parameterName]) )
        {
            // avoid too deep nested level closures
            $this->setNestedLevel($parameterName, $nestedLevel + 1);
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
        if (is_null($this->normalizer)) {
            return $value;
        }

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
        if (is_null($this->serializer)) {
            return $data;
        }

        $data = $this->serializer->wrapData($data);
        return serialize($data);
    }

    public function unserialize($data)
    {
        if (is_null($this->serializer)) {
            return $data;
        }

        $data = unserialize($data);
        $data = $this->serializer->unwrapData($data);
        return $data;
    }
}
