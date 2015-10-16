<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Definition;
use \G\Yaml2Pimple\Proxy\AspectProxyInterface;
use \G\Yaml2Pimple\Proxy\ServiceProxyInterface;
use \G\Yaml2Pimple\Handler\TagHandlerInterface;

class ServiceFactory extends AbstractServiceFactory
{
    /** @var ServiceProxyInterface $proxyFactory */
    protected $proxyFactory;

    /** @var AspectProxyInterface $aspectFactory */
    protected $aspectFactory;

    /** @var  array $tagHandlers */
    protected $tagHandlers;

    private $shared = array();
    /**
     * @param ServiceProxyInterface $proxyFactory
     * @param array                 $tagHandlers
     */
    public function __construct($proxyFactory = null, array $tagHandlers = array())
    {
        $this->proxyFactory  = $proxyFactory;
        $this->aspectFactory = null;
        $this->tagHandlers   = $tagHandlers;
    }

    public function addTagHandler($tagHandler)
    {
        $this->tagHandlers[] = $tagHandler;
    }

    /**
     * @param AspectProxyInterface $aspectFactory
     */
    public function setAspectFactory($aspectFactory)
    {
        $this->aspectFactory = $aspectFactory;
    }

    public function create(Definition $serviceConf, \Pimple $container)
    {
        $serviceName = $serviceConf->getName();

        $factoryFunction = null;

        if (!$serviceConf->isSynthetic()) {
            // we dont know how to create a synthetic service, its set later
            // the class name can be a parameter reference
            $serviceConf->setClass($this->normalize($serviceConf->getClass(), $container));

            $that          = $this;
            $aspectFactory = $this->aspectFactory;
            $shared        = $this->shared;

            // the instantiator closure function
            $factoryFunction = function ($c) use ($that, $serviceConf, $aspectFactory, &$shared) {

                $serviceName = $serviceConf->getName();

                if (!empty($shared[ $serviceName ])) {
                    return $shared[ $serviceName ];
                }

                $instance = $that->createInstance($serviceConf, $c);

                // add aspects
                if (null !== $aspectFactory && $serviceConf->hasAspects()) {
                    $instance = $that->addAspects($serviceConf->getAspects(), $instance, $c);
                }

                // add some method calls
                if ($serviceConf->hasCalls()) {
                    $instance = $that->addMethodCalls($serviceConf->getCalls(), $instance, $c);
                }

                // let another object modify this instance
                if ($serviceConf->hasConfigurators()) {
                    $instance = $that->addConfigurators($serviceConf->getConfigurators(), $instance, $c);
                }

                // if the service is a shared instance we save it for later use, this is default
                if (true === $serviceConf->isShared()) {
                    $shared[ $serviceName ] = $instance;
                }

                return $instance;
            };

            if ($serviceConf->hasTags()) {
                $tags = $serviceConf->getTags();
                /** @var TagHandlerInterface $handler */
                foreach ($this->tagHandlers as $handler) {
                    $handler->process($serviceConf, $tags, $container);
                }
            }

            // create a lazy proxy
            if (null !== $this->proxyFactory && $serviceConf->isLazy()) {
                $factoryFunction = $this->proxyFactory->createProxy($serviceConf->getClass(), $factoryFunction);
            }
        }

        $container[ $serviceName ] = $factoryFunction;

        return $container;
    }

    public function createInstance(Definition $serviceConf, $container)
    {
        // decode the argument list
        $params = (array)$this->normalize($serviceConf->getArguments(), $container);

        if ($serviceConf->hasFactory()) {
            $instance = $this->createFromFactory($serviceConf->getFactory(), $params, $container);
        } else {
            $class = new \ReflectionClass($serviceConf->getClass());
            // create the instance
            $instance = $class->newInstanceArgs($params);
        }

        return $instance;
    }

    protected function createFromFactory(array $factory = array(), $params, $container)
    {
        list($factory, $method) = $factory;
        $factory = $this->normalize($factory, $container);
        $method  = $this->normalize($method, $container);

        // let the factory create the instance
        return call_user_func_array(array($factory, $method), $params);
    }

    public function addMethodCalls(array $calls = array(), $instance, $container)
    {
        foreach ($calls as $call) {
            $method    = array_shift($call);
            $arguments = array_shift($call);
            $params    = $this->normalize($arguments, $container);
            call_user_func_array(array($instance, $method), $params);
        }

        return $instance;
    }

    public function addConfigurators(array $configs = array(), $instance, $container)
    {
        // let another object modify this instance
        foreach ($configs as $config) {
            $configurator = array_shift($config);
            $method       = array_shift($config);
            $params       = $this->normalize($config, $container);

            array_unshift($params, $instance);

            if ($instance !== $params[0]) {
                throw new \InvalidArgumentException(sprintf('Argument expected'));
            }

            call_user_func_array(array($this->normalize($configurator, $container), $method), $params);
        }

        return $instance;
    }

    public function addAspects(array $aspects = array(), $instance, $container)
    {
        $instance = $this->aspectFactory->createProxy($instance);

        foreach ($aspects as $aspect) {
            $func = function ($methodInvocation) use ($container, $aspect) {
                list($service, $method) = explode(':', $aspect['advice']);

                return call_user_func(array($container[ $service ], $method), $methodInvocation);
            };

            $instance = $this->aspectFactory->addAspect($instance, $aspect['pointcut'], $func);
        }

        return $instance;
    }
}
