<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Parameter;
use \G\Yaml2Pimple\Proxy\ParameterProxyAdapter;

class ProxyParameterFactory extends AbstractParameterFactory
{
    protected $proxyFactory;
    protected $nestedLevel;
    protected $maxNestedLevel;

    public function __construct($proxyFactory = null)
    {
        $this->proxyFactory = $proxyFactory;
        if (null === $proxyFactory) {
            $this->proxyFactory = new ParameterProxyAdapter();
        }
        $this->nestedLevel      = array();
        $this->maxNestedLevel   = 50;
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
    
	public function create(Parameter $parameterConf, \Pimple $container)
	{
        $parameterName = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        // we wrap our parameter in a magic proxy class with a __invoke method which is
        // called automatically on access by pimple. this way we have a chance to access
        // parameters as references which could be set later
        // the value is evaluated on every access
        $that = $this;
        $value = $this->proxyFactory->createProxy(
            function($c) use ($that, $parameterName, $parameterValue) {
                $parameterValue = $that->normalize($parameterValue, $c);
                return $parameterValue;
            }
        );

        $nestedLevel = $this->getNestedLevel($parameterName);
        // merge existing data per default
        if ($nestedLevel < $this->maxNestedLevel && isset($container[$parameterName]) && $parameterConf->mergeExisting() )
        {
            // avoid too deep nested level closures
            $this->setNestedLevel($parameterName, $nestedLevel + 1);
            // create a wrapper function for lazy calling
            $value = $container->extend($parameterName, function($old, $c) use ($parameterName, $value) {
                // extract the value from our LazyParameterFactory
                if (is_object($value) && method_exists($value, '__invoke')) {
                    $value = $value($c);
                }
                // merge existing data with new
                return array_replace_recursive($old, $value);
            });
        }

        // freeze our value on first access (as singleton) this is default
        if ($parameterConf->isFrozen()) {
            $value = $container->share($value);
        }
        
        $container[$parameterName] = $value;
        return $container;
    }
}