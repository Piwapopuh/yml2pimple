<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 05.10.2015
 * Time: 09:59
 */

namespace G\Yaml2Pimple\Handler;

use G\Yaml2Pimple\Definition;

class EventListenerTagHandler implements TagHandlerInterface
{
    protected $dispatcher;
    public function __construct($dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function process(Definition $serviceConf, array $tags, \Pimple $container) {
        foreach ($tags as $tag)
        {
            if (strtolower($tag['name']) === 'konfigurator.event_listener')
            {
                $container[$this->dispatcher] = $container->extend($this->dispatcher, function ($dispatcher, $c) use ($serviceConf, $tag)
                {
                    $dispatcher->addListener($tag['event'], function() use ($serviceConf, $tag, $c)
                    {
                        $service    = $serviceConf->getName();
                        $method     = $tag['method'];
                        if (isset($c[$service])) {
                            return call_user_func_array(array($c[$service], $method), func_get_args());
                        }
                        return false;
                    });

                    return $dispatcher;

                });
            }
        }
    }
}
