<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 13.10.2015
 * Time: 09:52
 */
namespace G\Yaml2Pimple\Loader;

use Symfony\Component\Yaml\Parser as YamlParser;

class YamlFileLoader extends AbstractLoader
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
        $yamlParser = new YamlParser();

        return $yamlParser->parse(file_get_contents($resource));
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

}
