<?php

namespace G\Yaml2Pimple;

class Definition
{
    private $class;

    protected $arguments;
	
	protected $calls;
	
	protected $configurators;

	protected $scope;
	
	public function __construct()
	{
		$this->calls = array();
		$this->configurators = array();
		$this->scope = 'container';
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
}
