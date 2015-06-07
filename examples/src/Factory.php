<?php


class Factory
{
	public function create(\Pimple $container = null)
	{
		echo "<br>creating class Proxy in Factory";
		return new Proxy($container['Curl']);
	}
}
