<?php

namespace G\Yaml2Pimple\Proxy;


class MethodInvocation
{

    public $reflection;

    public $object;

    public $arguments;

    public function __construct(\ReflectionMethod $reflection, $object, array $arguments)
    {
        $this->reflection = $reflection;
        $this->object     = $object;
        $this->arguments  = $arguments;
    }

    public function getThis()
    {
        return $this->object;
    }

    public function getNamedArgument($name)
    {
        foreach ($this->reflection->getParameters() as $i => $param) {
            if ($param->name !== $name) {
                continue;
            }

            if (!array_key_exists($i, $this->arguments)) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }

                throw new \RuntimeException(sprintf('There was no value given for parameter "%s".', $param->name));
            }

            return $this->arguments[ $i ];
        }

        throw new \InvalidArgumentException(sprintf('The parameter "%s" does not exist.', $name));
    }

    public function proceed()
    {
        $this->reflection->setAccessible(true);

        return $this->reflection->invokeArgs($this->object, $this->arguments);
    }

    public function __toString()
    {
        return sprintf('%s::%s', $this->reflection->class, $this->reflection->name);
    }
}
