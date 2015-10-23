<?php
namespace G\Yaml2Pimple\Factory;

use \G\Yaml2Pimple\Parameter;
use \G\Yaml2Pimple\Proxy\ParameterProxyAdapter;

class ProxyParameterFactory extends AbstractParameterFactory
{
    protected $proxyFactory;
    private   $frozen = array();

    public function __construct($proxyFactory = null)
    {
        $this->proxyFactory = $proxyFactory;
        if (null === $proxyFactory) {
            $this->proxyFactory = new ParameterProxyAdapter();
        }
    }

    public function create(Parameter $parameterConf, \Pimple $container)
    {
        $parameterName  = $parameterConf->getParameterName();
        $parameterValue = $parameterConf->getParameterValue();

        // we wrap our parameter in a magic proxy class with a __invoke method which is
        // called automatically on access by pimple. this way we have a chance to access
        // parameters as references which could be set later
        // the value is evaluated on every access
        $that  = $this;
        $frozen = $this->frozen;

        $old = null;
        if (isset($container[ $parameterName ])) {
            $old = $container[ $parameterName ];
        }

        $value = $this->proxyFactory->createProxy(
            function ($c) use ($that, $parameterConf, $parameterValue, $old, &$frozen) {

                $parameterName  = $parameterConf->getParameterName();

                if (!empty($frozen[ $parameterName ])) {
                    return $frozen[ $parameterName ];
                }

                if (is_array($parameterValue)) {
                    $parameterValue = $that->normalize($parameterValue, $c, $parameterName);
                    do {
                        $old = $parameterValue;
                        $parameterValue = json_decode(str_replace('@', '@@', json_encode($parameterValue)), true);
                        $parameterValue = $that->normalize(
                            $parameterValue,
                            new \Pimple(array($parameterName => $parameterValue))
                        );
                        $parameterValue = json_decode(str_replace('@@', '@', json_encode($parameterValue)), true);
                    } while($old != $parameterValue);
                } else {
                    $parameterValue = $that->normalize($parameterValue, $c);
                }

                if (null !== $old && $parameterConf->mergeExisting()) {
                    // extract the value if it is a callable
                    if (is_object($old) && is_callable($old)&& method_exists($old, '__invoke')) {
                        $old = $old($c);
                    }
                    $parameterValue = call_user_func($parameterConf->getMergeStrategy(), $old, $parameterValue);
                }

                // freeze our value on first access (as singleton) this is default
                if ($parameterConf->isFrozen()) {
                    $frozen[ $parameterName ] = $parameterValue;
                }

                return $parameterValue;
            }
        );

        $container[ $parameterName ] = $value;

        return $container;
    }
}
