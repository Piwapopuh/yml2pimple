<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 16.09.2015
 * Time: 12:58
 */

namespace G\Yaml2Pimple\Factory;

use ProxyManager\Configuration;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

class ServiceProxyAdapter implements ServiceProxyInterface
{
    private $factory;

    /**
     * ProxyManagerFactory constructor.
     */
    public function __construct($cacheDir = null)
    {
        // set a proxy cache for performance tuning
        $config = new Configuration();

        if (!is_null($cacheDir)) {
            $config->setProxiesTargetDir($cacheDir);
        }
        // then register the autoloader
        spl_autoload_register($config->getProxyAutoloader());

        $this->factory = new LazyLoadingValueHolderFactory($config);
    }

    public function createProxy($className, \Closure $func)
    {
        if (is_null($this->factory)) {
            return $func;
        }

        $factory = $this->factory;
        return function ($container) use ($factory, $className, $func) {
            return $factory->createProxy($className,
                function (&$wrappedInstance, LazyLoadingInterface $proxy) use ($container, $func) {
                    $wrappedInstance = call_user_func($func, $container);
                    $proxy->setProxyInitializer(null);
                    return true;
                }
            );
        };
    }
}