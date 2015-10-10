<?php

namespace G\Yaml2Pimple\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Yaml\Parser as YamlParser;

use G\Yaml2Pimple\Definition;
use G\Yaml2Pimple\Parameter;

class YamlFileLoader
{
    private $locator;
    private $yamlParser;
    private $container;
    private $currentDir;
    private $currentFile;

    public function __construct(FileLocatorInterface $fileLocator = null)
    {
        $this->locator = $fileLocator;
    }

    /**
     * @param $file
     * @param bool|false $isImport
     *
     * @return array
     *
     */
    public function load($file, $isImport = false)
    {
        if (!$isImport) {
            $this->container = array();
            $this->currentFile = $file;
        }

        $path = $file;
        $content = null;

        try {
            if ( null !== $this->locator ) {
                $path = $this->locator->locate($file);
            }
            $content = $this->loadFile($path);
        } catch (\InvalidArgumentException $e) {
            //
        }

        if (null === $content) {
            return array();
        }

        $this->parseImports($content, $path);
        $this->parseParameters($content);
        $this->parseDefinitions($content);

        return $this->container;
    }

    public function supports($resource)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    protected function loadFile($file)
    {
        if (!stream_is_local($file)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        return $this->validate($this->yamlParser->parse(file_get_contents($file)), $file);
    }

    private function validate($content, $file)
    {
        if (null === $content) {
            return $content;
        }

        if (!is_array($content)) {
            throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        return $content;
    }

    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        $this->setCurrentDir(dirname($file));

        foreach ($content['imports'] as $import)
        {
            $resource = $import['resource'];

            if (!$this->isAbsolutePath($resource)) {
                $resource = dirname($file) . '/' . $resource;
            }

            $this->load($resource, true);
        }
    }

    public function setCurrentDir($dir)
    {
        $this->currentDir = $dir;
    }

    private function parseParameters($content)
    {
        if (isset($content['parameters'])) {
            foreach ($content['parameters'] as $key => $value)
            {
                $param = new Parameter($key, $value);
                $param->setFile($this->currentFile);

                $this->container['parameters'][] = $param;
            }
        }
    }

    private function parseDefinitions($content)
    {
        if (!isset($content['services'])) {
            return;
        }

        foreach ($content['services'] as $id => $service) {
            $this->parseDefinition($id, $service);
        }
    }

    private function parseDefinition($id, $service)
    {
        $definition = new Definition($id);
        $definition->setFile($this->currentFile);

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

        $this->container['services'][$id] = $definition;
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file A file path
     *
     * @return bool
     */
    private function isAbsolutePath($file)
    {
        return (
            $file[0] === '/' || $file[0] === '\\'
            ||
            (strlen($file) > 3 && ctype_alpha($file[0])
                && $file[1] === ':'
                && ($file[2] === '\\' || $file[2] === '/')
            )
            ||
            null !== parse_url($file, PHP_URL_SCHEME)
        );
    }
}
