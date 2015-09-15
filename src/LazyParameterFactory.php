<?php

namespace G\Yaml2Pimple;

use SuperClosure\SerializerInterface;

class LazyParameterFactory
{
	protected $callback;
    protected $frozen;
    protected $serializer;

	public function __construct($callback, SerializerInterface $serializer = null)
	{
		$this->callback     = $callback;
        $this->frozen       = false;
        $this->serializer   = $serializer;
	}

	public function __invoke($c)
	{
		return call_user_func($this->callback, $c);
	}

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param boolean $frozen
     */
    public function setFrozen($frozen)
    {
        $this->frozen = $frozen;
    }

    public function __sleep()
    {
        $this->frozen = $this->serializer->wrapData($this->callback);
        return array('frozen', 'serializer');
    }

    public function __wakeup()
    {
        $this->callback = $this->serializer->unwrapData($this->frozen);
    }

}
