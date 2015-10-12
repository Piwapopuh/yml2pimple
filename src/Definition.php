<?php

namespace G\Yaml2Pimple;

class Definition
{
    protected $name;

    protected $class;

    protected $arguments = array();

    protected $calls = array();

    protected $configurators = array();

    protected $scope = 'container';

    protected $lazy = false;

    protected $synthetic = false;

    protected $factory = array();

    protected $file;

    protected $tags = array();

    protected $aspects = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getAspects()
    {
        return $this->aspects;
    }

    /**
     * @param mixed $aspects
     */
    public function setAspects($aspects)
    {
        $this->aspects = $aspects;
    }

    public function hasAspects()
    {
        return is_array($this->aspects) && count($this->aspects) > 0;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function hasTags()
    {
        return is_array($this->tags) && count($this->tags) > 0;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function hasArguments()
    {
        return is_array($this->arguments) && count($this->arguments) > 0;
    }

    public function addCalls(array $calls)
    {
        $this->calls = $calls;

        return $this;
    }

    public function addCall(array $call)
    {
        $this->calls[] = $call;

        return $this;
    }

    public function getCalls()
    {
        return $this->calls;
    }

    public function hasCalls()
    {
        return is_array($this->calls) && count($this->calls) > 0;
    }

    public function addConfigurator($config)
    {
        $this->configurators[] = $config;

        return $this;
    }

    public function getConfigurators()
    {
        return $this->configurators;
    }

    public function hasConfigurators()
    {
        return is_array($this->configurators) && count($this->configurators) > 0;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setLazy($lazy)
    {
        $this->lazy = $lazy;

        return $this;
    }

    public function isLazy()
    {
        return $this->lazy;
    }

    public function setSynthetic($synthetic)
    {
        $this->synthetic = $synthetic;

        return $this;
    }

    public function isSynthetic()
    {
        return $this->synthetic;
    }

    public function setFactory($factory)
    {
        $this->factory = $factory;

        return $this;
    }

    public function getFactory()
    {
        return $this->factory;
    }

    public function hasFactory()
    {
        return is_array($this->factory) && count($this->factory) > 0;
    }

    public function initialize($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }
    }

    public static function __set_state($array)
    {
        $obj = new Definition($array['name']);
        $obj->initialize($array);

        return $obj;
    }
}
