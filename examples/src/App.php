<?php

class App
{
    private $proxy;
    private $name;
	private $dummy;

    public function __construct(Proxy $proxy, $name)
    {
        $this->proxy = $proxy;
        $this->name  = $name;
    }

	public function setDummy($dummy)
	{
		$this->dummy = $dummy;
		
		return $this;
	}
	
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	
    public function hello()
    {
        return $this->proxy->hello($this->name);
    }
	
	public function dummy()
	{
		echo $this->dummy->name;
	}
}
