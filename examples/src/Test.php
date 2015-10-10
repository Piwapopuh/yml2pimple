<?php

use G\Yaml2Pimple\Proxy\MethodInvocation;

class Test
{
	protected $name;
	protected $else;
    
	public function __construct($name, $else = '')
	{
		$this->name = $name;
        $this->else = $else;
	}
	
    public function configure(App $class)
    {
        $class->setName($this->name . ' ' . $this->else);
    }

	public function beforeHello(MethodInvocation $methodInvocation) {
		echo "captured method " . $methodInvocation->reflection->getName();
		return $methodInvocation->proceed();
	}
}
