<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 05.10.2015
 * Time: 09:59
 */

namespace G\Yaml2Pimple\Handler;


class EventListenerTagHandler
{
    protected $dispatcher;
    public function __construct($dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    public function process($serviceConf, $tags, $container) {
        foreach ($tags as $tag) {
            if ($tag['name'] == 'konfigurator.event_listener') {
                $container[$this->dispatcher] = $container->extend($this->dispatcher, function ($dispatcher, $c) use ($serviceConf, $tag) {
                    $dispatcher->addListener($tag['event'], function() use ($serviceConf, $tag, $c) {
                        $service    = $serviceConf->getName();
                        $method     = $tag['method'];
                        if (isset($c[$service])) {
                            return call_user_func_array(array($c[$service], $method), func_get_args());
                        }
                    });
                    return $dispatcher;
                });
            }
        }
    }
}