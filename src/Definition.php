<?php

namespace G\Yaml2Pimple;

class Definition
{
    private $class;

    protected $arguments;
	
	protected $calls;
	
	protected $configurators;

	protected $scope;
	
	protected $lazy;
	
	protected $synthetic;
	
	protected $factory;
	
	public function __construct()
	{
		$this->calls = array();
		$this->configurators = array();
		$this->scope = 'container';
		$this->lazy = false;
		$this->synthetic = false;
		$this->factory = false;
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
	
	public function addCall(array $call)
	{
		$this->calls[] = $call;
		
		return $this;
	}
	
	public function getCalls()
	{
		return $this->calls;
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
        return !empty($this->factory);
    }	
    
    public function _set($array) 
    {
        foreach($array as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public static function __set_state($array)
    {
        $obj = new Definition;
        $obj->_set($array);
        return $obj;
    }
}
