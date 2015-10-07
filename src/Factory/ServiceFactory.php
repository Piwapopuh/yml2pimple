<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Definition;

class ServiceFactory extends AbstractServiceFactory
{
    protected $proxyFactory;
    protected $aspectFactory;
    protected $tagHandlers;

    public function __construct($proxyFactory = null, $tagHandler = array())
    {
        $this->proxyFactory     = $proxyFactory;
        $this->aspectFactory    = null;
        $this->tagHandlers      = $tagHandler;
    }

    public function addTagHandler($tagHandler) {
        $this->tagHandlers[] = $tagHandler;
    }

    /**
     * @param mixed $aspectFactory
     */
    public function setAspectFactory($aspectFactory)
    {
        $this->aspectFactory = $aspectFactory;
    }

	public function create(Definition $serviceConf, \Pimple $container)
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

                $that->addAspects($serviceConf->getAspects(), $instance, $c);

                // add some method calls
                $that->addMethodCalls($serviceConf->getCalls(), $instance, $c);
                
                // let another object modify this instance
                $that->addConfigurators($serviceConf->getConfigurators(), $instance, $c);

                return $instance;
            };

            $tags = $serviceConf->getTags();
            foreach ($this->tagHandlers as $handler) {
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
        return $container;
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
            echo ">>".$serviceConf->getClass()."<<";
            $class = new \ReflectionClass($serviceConf->getClass());
            // create the instance
            $instance = $class->newInstanceArgs($params);
        }
        return $instance;
    }

	protected function createFromFactory($factory = array(), $params, $container)
	{
        if (!is_array($factory)) {
            return;
        }        
		list($factory, $method) = $factory;
		$factory 	= $this->normalize($factory, $container);
		$method 	= $this->normalize($method, $container);
		// let the factory create the instance
		return call_user_func_array(array($factory, $method), $params);
	}

	public function addMethodCalls($calls = array(), &$instance, $container)
	{
        if (!is_array($calls)) {
            return;
        }    
		foreach ($calls as $call) {
			list($method, $arguments) = $call;
			$params = $this->normalize($arguments, $container);
			call_user_func_array(array($instance, $method), $params);
		}
	}

	public function addConfigurators($configs = array(), &$instance, $container)
	{
        if (!is_array($configs)) {
            return;
        }
		// let another object modify this instance
		foreach ($configs as $config) {
			$configurator 	= array_shift($config);
			$method 		= array_shift($config);
			$params 		= $this->normalize($config, $container);
			array_unshift($params, $instance);
			call_user_func_array(array($this->normalize($configurator, $container), $method), $params);
		}
	}

    public function addAspects($aspects = array(), &$instance, $container)
    {
        if (!is_array($aspects)) {
            return;
        }
        foreach ($aspects as $aspect) {
            $instance = $this->aspectFactory->createProxy($instance);
            $instance = $this->aspectFactory->addAspect($instance, $aspect['pointcut'], function($methodInvocation) use ($container, $aspect) {
                list($service, $method) = explode(":", $aspect['advice']);
                return call_user_func(array($container[$service], $method), $methodInvocation);
            });
        }
    }
}