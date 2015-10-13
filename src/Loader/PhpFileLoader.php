<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 13.10.2015
 * Time: 09:52
 */
namespace G\Yaml2Pimple\Loader;

class PhpFileLoader extends AbstractLoader
{
    /**
     * @param  $resource
     *
     * @return array
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    protected function read($resource)
    {
        $params = include $resource;

        return array('parameters' => $params);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }

}
