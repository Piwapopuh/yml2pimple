<?php
namespace G\Yaml2Pimple;

use G\Yaml2Pimple\Factory\AbstractParameterFactory;
use G\Yaml2Pimple\Factory\AbstractServiceFactory;
use G\Yaml2Pimple\Normalizer\ChainNormalizer;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;
use G\Yaml2Pimple\Normalizer\ExpressionNormalizer;
use G\Yaml2Pimple\Factory\ServiceFactory;
use G\Yaml2Pimple\Factory\ProxyParameterFactory;
use G\Yaml2Pimple\Loader\YamlFileLoader;
use G\Yaml2Pimple\Loader\PhpFileLoader;
use G\Yaml2Pimple\Loader\AbstractLoader;
use G\Yaml2Pimple\Loader\CacheLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

class ContainerBuilder
{
    /** @var  \Pimple $container */
    private $container;

    /** @var  mixed $normalizer */
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

    /** @var  AbstractLoader $loader */
    private $loader;

    /** @var FileLocatorInterface $locator */
    private $locator;

    /** @var  ResourceCollection $resources */
    private $resources;

    /** @var CacheLoader $cacheLoader */
    private $cacheLoader;

    /** @var string $cacheDir */
    private $cacheDir;

    /** @var array[AbstractLoader] $additionalLoaders  */
    private $additionalLoaders = array();

    public function __construct(\Pimple $container, $paths = array())
    {
        $this->container = $container;
        $this->locator   = new FileLocator($paths);
        $this->resources = new ResourceCollection();
    }

    public function setLocator(FileLocatorInterface $locator)
    {
        $this->locator = $locator;

        return $this;
    }

    public function setResources(ResourceCollection $resources)
    {
        $this->resources = $resources;

        return $this;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    public function setCacheLoader($cacheLoader)
    {
        $this->cacheLoader = $cacheLoader;

        return $this;
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

    public function addLoader(AbstractLoader $loader)
    {
        $this->additionalLoaders[] = $loader;

        return $this;
    }

    /**
     * @param mixed $loader
     *
     * @return $this
     */
    public function setLoader($loader)
    {
        $this->loader = $loader;

        return $this;
    }

    public function load($file)
    {
        if (null === $this->loader) {
            $this->loader = $this->getDefaultLoader();
        }

        $conf = $this->loader->load($file);

        $this->buildFromArray($conf);
    }

    public function buildFromArray($conf)
    {
        if (null === $this->normalizer) {
            $this->normalizer = $this->getDefaultNormalizer();
        }

        if (null === $this->parameterFactory) {
            $this->parameterFactory = new ProxyParameterFactory();
        }

        $this->parameterFactory->setNormalizer($this->normalizer);

        foreach ($conf['parameters'] as $parameterConf) {
            if ($parameterConf instanceof Parameter) {
                $this->container = $this->parameterFactory->create($parameterConf, $this->container);
            }
        }

        if (null === $this->serviceFactory) {
            $this->serviceFactory = new ServiceFactory();
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

        return $this;
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

    private function getDefaultLoader()
    {
        $loaderCollection = array(
            new YamlFileLoader($this->locator, $this->resources),
            new PhpFileLoader($this->locator, $this->resources)
        );

        $loaderCollection = array_merge($loaderCollection, $this->additionalLoaders);

        $loader = new DelegatingLoader(
            new LoaderResolver($loaderCollection)
        );

        if (null !== $this->cacheDir) {
            $loader = new CacheLoader($loader, $this->resources);
            $loader->setCacheDir($this->cacheDir);
        }

        return $loader;
    }

    private function getDefaultNormalizer()
    {
        return new ChainNormalizer(
            array(
                new PimpleNormalizer(),
                new ExpressionNormalizer()
            )
        );
    }
}
