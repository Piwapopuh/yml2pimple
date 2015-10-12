<?php

namespace G\Yaml2Pimple;

use G\Yaml2Pimple\Factory\AbstractParameterFactory;
use G\Yaml2Pimple\Factory\AbstractServiceFactory;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ProxyParameterFactory;
use G\Yaml2Pimple\Loader\YamlFileLoader;

class ContainerBuilder
{
    private $container;

    private $normalizer;

    /**
     * @var AbstractServiceFactory $factory
     */
    private $serviceFactory;

    /**
     * @var AbstractParameterFactory $parameterFactory
     */
    private $parameterFactory;

    private $serializer;

    private $loader;

    public function __construct(\Pimple $container)
    {
        $this->container = $container;
    }

    public function addDefaultParameterFactory()
    {
        $this->parameterFactory = $this->getDefaultParameterFactory();
    }

    public function getDefaultParameterFactory()
    {
        return new ProxyParameterFactory();
    }

    /**
     * @param AbstractParameterFactory $parameterFactory
     *
     * @return $this
     */
    public function setParameterFactory(AbstractParameterFactory $parameterFactory)
    {
        $this->parameterFactory = $parameterFactory;

        return $this;
    }

    public function addDefaultServiceFactory()
    {
        $this->serviceFactory = $this->getDefaultServiceFactory();
    }

    public function getDefaultServiceFactory()
    {
        return new ServiceFactory();
    }

    public function setServiceFactory(AbstractServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;

        return $this;
    }

    public function setNormalizer($normalizer)
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    public function getDefaultNormalizer()
    {
        return new PimpleNormalizer($this->container);
    }

    protected function addDefaultNormalizer()
    {
        $this->normalizer = $this->getDefaultNormalizer();

        return $this;
    }

    public function addDefaultLoader()
    {
        $this->loader = $this->getDefaultLoader();
    }

    public function getDefaultLoader()
    {
        return new YamlFileLoader();
    }

    /**
     * @param mixed $loader
     */
    public function setLoader($loader)
    {
        $this->loader = $loader;
    }

    public function add($k, $v = null)
    {
        if (is_array($k)) {
            /** @var string $key */
            foreach ($k as $key => $value) {
                $this->container[ $key ] = $value;
            }
        } else {
            $this->container[ $k ] = $v;
        }
    }

    public function load($file)
    {
        if (null === $this->loader) {
            $this->addDefaultLoader();
        }

        $conf = $this->loader->load($file);

        $this->buildFromArray($conf);
    }

    public function buildFromArray($conf)
    {
        if (null === $this->normalizer) {
            $this->addDefaultNormalizer();
        }

        if (null === $this->parameterFactory) {
            $this->addDefaultParameterFactory();
        }

        $this->parameterFactory->setNormalizer($this->normalizer);

        foreach ($conf['parameters'] as $parameterConf) {
            if ($parameterConf instanceof Parameter) {
                $this->container = $this->parameterFactory->create($parameterConf, $this->container);
            }
        }

        if (null === $this->serviceFactory) {
            $this->addDefaultServiceFactory();
        }

        $this->serviceFactory->setNormalizer($this->normalizer);

        foreach ($conf['services'] as $serviceName => $serviceConf) {
            if ($serviceConf instanceof Definition) {
                $this->container = $this->serviceFactory->create($serviceConf, $this->container);
            }
        }
    }

    /**
     * @param $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }

    public function serialize($data)
    {
        if (null === $this->serializer) {
            return $data;
        }

        $data = $this->serializer->wrapData($data);

        return serialize($data);
    }

    public function unSerialize($data)
    {
        if (is_null($this->serializer)) {
            return $data;
        }

        $data = unserialize($data);
        $data = $this->serializer->unwrapData($data);

        return $data;
    }
}
