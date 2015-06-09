<?php

class Test
{
	protected $name;
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
    public function configure(App $class)
    {
        $class->setName($this->name);
    }
}
