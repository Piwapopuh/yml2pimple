<?php

namespace G\Yaml2Pimple;

class Definition
{
    private $class;

    protected $arguments;
	
	protected $calls;
	
	protected $configurators;

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
		if(!is_array($this->calls))
		{
			$this->calls = array();
		}
		$this->calls[] = $call;
		
		return $this;
	}
	
	public function getCalls()
	{
		return $this->calls;
	}
	
	public function addConfigurator($config)
	{
		if(!is_array($this->configurators))
		{
			$this->configurators = array();
		}
		$this->configurators[] = $config;
		
		return $this;		
	}
	
	public function getConfigurators()
	{
		return $this->configurators;
	}
}
