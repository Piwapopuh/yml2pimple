<?php
namespace G\Yaml2Pimple\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use G\Yaml2Pimple\ResourceCollection;
use G\Yaml2Pimple\Definition;
use G\Yaml2Pimple\Parameter;

abstract class AbstractLoader extends FileLoader
{
    protected $resources;

    public function __construct(FileLocatorInterface $locator, ResourceCollection $resources = null)
    {
        parent::__construct($locator);
        $this->locator   = $locator;
        $this->resources = $resources;
    }

    public function setResources(ResourceCollection $resources)
    {
        $this->resources = $resources;
    }

    public function load($resource, $type = null)
    {
        $resource = $this->locator->locate($resource);

        if (null !== $this->resources) {
            $this->resources->add(new FileResource($resource));
        }

        $ret = $this->parse($this->read($resource), $resource);

        return $ret;
    }

    protected function parse(array $content, $resource)
    {
        $inherited = array(
            'parameters' => array(),
            'services'   => array(),
        );
        if (isset($content['imports'])) {
            $imports = (array)$content['imports'];

            unset($content['imports']);

            foreach ($imports as $import) {
                $import = $import['resource'];
                $this->setCurrentDir(dirname($import));
                $inherited = array_replace_recursive($inherited, $this->import($import, null, false, $resource));
            }
        }
        if (isset($content['parameters'])) {
            foreach ($content['parameters'] as $key => $value) {
                $inherited['parameters'][] = $this->parseParameter($key, $value);
            }
        }
        if (isset($content['services'])) {
            foreach ($content['services'] as $id => $service) {
                $inherited['services'][ $id ] = $this->parseDefinition($id, $service);
            }
        }
        return $inherited;
    }

    private function parseParameter($key, $value)
    {
        $param = new Parameter($key, $value);

        return $param;
    }

    private function parseDefinition($id, $service)
    {
        $definition = new Definition($id);

        if (isset($service['synthetic'])) {
            $definition->setSynthetic($service['synthetic']);
        }

        if (isset($service['class'])) {
            $definition->setClass($service['class']);
        }

        if (isset($service['scope'])) {
            $definition->setScope($service['scope']);
        }

        if (isset($service['lazy'])) {
            $definition->setLazy($service['lazy']);
        }

        if (isset($service['arguments'])) {
            $definition->setArguments($service['arguments']);
        }

        if (isset($service['calls'])) {
            $definition->addCalls($service['calls']);
        }

        if (isset($service['configurator'])) {
            $definition->addConfigurator($service['configurator']);
        }

        if (isset($service['factory'])) {
            $definition->setFactory($service['factory']);
        }

        if (isset($service['tags'])) {
            $definition->setTags($service['tags']);
        }

        if (isset($service['aspects'])) {
            $definition->setAspects($service['aspects']);
        }

        return $definition;
    }

    abstract protected function read($resource);
}
