<?php

namespace G\Yaml2Pimple\Proxy;

use SuperClosure\SerializerInterface;

class ParameterProxyAdapter implements ParameterProxyInterface
{
    protected $serializer;

    public function __construct(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer;
    }

    public function createProxy(\Closure $func)
    {
        return new LazyParameterProxy($func, $this->serializer);
    }
}
