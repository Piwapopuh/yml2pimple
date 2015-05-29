<?php

class App
{
    private $proxy;
    private $name;

    public function __construct(Proxy $proxy, $name)
    {
        $this->proxy = $proxy;
        $this->name  = $name;
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
}
