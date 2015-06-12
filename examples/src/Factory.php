<?php


class Factory
{
	public function create(\Pimple $container = null)
	{
		echo "<p>creating class Proxy in Factory</p>";
		return new Proxy($container['Curl']);
	}
}
