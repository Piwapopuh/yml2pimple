<?php

namespace G\Yaml2Pimple;


class LazyParameterFactory
{
	protected $callback;
	public function __construct($callback)
	{
		$this->callback = $callback;
	}
	public function __invoke($c)
	{
		return call_user_func($this->callback, $c);
	}
}
