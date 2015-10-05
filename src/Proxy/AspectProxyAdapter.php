<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 16.09.2015
 * Time: 12:58
 */

namespace G\Yaml2Pimple\Proxy;

use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory ;
use CG\Proxy\MethodInvocation;

class AspectProxyAdapter implements AspectProxyInterface
{
    private $factory;
    private $cache;
    /**
     * ProxyManagerFactory constructor.
     */
    public function __construct($cacheDir = null)
    {
        $this->cache = array();

        // set a proxy cache for performance tuning
        $config = new Configuration();

        if (!is_null($cacheDir)) {
            $config->setProxiesTargetDir($cacheDir);
        }
        // then register the autoloader
        spl_autoload_register($config->getProxyAutoloader());

        $this->factory = new AccessInterceptorValueHolderFactory ($config);
    }

    public function createProxy($instance) {
        if (is_null($this->factory)) {
            return $instance;
        }

        $factory = $this->factory;
        $proxy = $factory->createProxy($instance);
        $oid = spl_object_hash($proxy);

        $reflection = new \ReflectionClass($instance);
        $methods = $reflection->getMethods();

        $this->cache[$oid] = $methods;

        return $proxy;
    }

    public function addAspect($proxy, $methodPattern, \Closure $interceptor)
    {
        if (is_null($this->factory)) {
            return $proxy;
        }

        $oid = spl_object_hash($proxy);

        if (!isset($this->cache[$oid])) {
            return $proxy;
        }

        $methods = (array)$this->cache[$oid];

        foreach ($methods as $reflectionMethod) {
            if (preg_match('/' . $methodPattern . '/', $reflectionMethod->getName())) {
                $proxy->setMethodPrefixInterceptor($reflectionMethod->getName(), function ($proxy, $object, $method, $params, &$returnEarly) use ($interceptor, $reflectionMethod) {
                    $methodInvocation = new MethodInvocation($reflectionMethod, $object, $params, array());
                    $returnEarly = true;
                    return call_user_func($interceptor, $methodInvocation);
                });
            }
        }

        return $proxy;
    }
}