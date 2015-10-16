<?php
namespace G\Yaml2Pimple;

use G\Yaml2Pimple\Factory\AbstractParameterFactory;
use G\Yaml2Pimple\Factory\AbstractServiceFactory;
use G\Yaml2Pimple\Normalizer\NormalizerInterface;
use G\Yaml2Pimple\Normalizer\ChainNormalizer;
use G\Yaml2Pimple\Normalizer\EnvironmentNormalizer;
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

    /** @var  NormalizerInterface $normalizer */
    private $normalizer;

    /** @var bool $normalizersConfigured */
    private $normalizersConfigured = false;

    /** @var AbstractServiceFactory $factory */
    private $serviceFactory;

    /** @var bool $serviceFactoryConfigured */
    private $serviceFactoryConfigured = false;

    /** @var AbstractParameterFactory $parameterFactory */
    private $parameterFactory;

    /** @var bool $parameterFactoryConfigured */
    private $parameterFactoryConfigured = false;

    /** @var LoaderResolver */
    private $resolver;

    /** @var bool $loadersConfigured */
    private $loadersConfigured = false;

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

    /**
     *
     * @param \Pimple $container
     * @param array   $paths
     */
    public function __construct(\Pimple $container, $paths = array())
    {
        $this->container  = $container;
        $this->locator    = new FileLocator($paths);
        $this->resources  = new ResourceCollection();
        $this->resolver   = new LoaderResolver();
    }

    /**
     * @param string $cacheDir
     *
     * @return $this
     */
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
     * @param callable $callable
     *
     * @return $this
     */
    public function configureParameterFactory(callable $callable)
    {
        $this->parameterFactoryConfigured = true;
        $callable($this->parameterFactory);
        return $this;
    }

    /**
     * @param AbstractParameterFactory $parameterFactory
     *
     * @return $this
     */
    public function setParameterFactory(AbstractParameterFactory $parameterFactory)
    {
        $this->parameterFactoryConfigured = true;
        $this->parameterFactory = $parameterFactory;

        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function configureServiceFactory(\Closure $callable)
    {
        $this->serviceFactoryConfigured = true;
        $callable($this->serviceFactory);
        return $this;
    }

    /**
     * @param AbstractServiceFactory $serviceFactory
     *
     * @return $this
     */
    public function setServiceFactory(AbstractServiceFactory $serviceFactory)
    {
        $this->serviceFactoryConfigured = true;
        $this->serviceFactory = $serviceFactory;
        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function configureNormalizers(callable $callable)
    {
        $this->normalizersConfigured = true;
        $callable($this->normalizer);
        return $this;
    }

    public function setNormalizer($normalizer)
    {
        $this->normalizersConfigured = true;
        $this->normalizer = $normalizer;
        return $this;
    }

    /**
     * @param callable $callable
     *
     * @return $this
     */
    public function configureLoaders(callable $callable)
    {
        $this->loadersConfigured = true;
        $this->resolver = $callable($this->resolver, $this->locator, $this->resources);
        return $this;
    }

    /**
     * @param mixed $loader
     *
     * @return $this
     */
    public function setLoader($loader)
    {
        $this->loadersConfigured = true;
        $this->loader = $loader;
        return $this;
    }

    /**
     * @param $file
     */
    public function load($file)
    {
        if (false === $this->loadersConfigured) {
            $this->loader = $this->getDefaultLoaders();
        }

        $conf = $this->loader->load($file);

        $this->buildFromArray($conf);
    }

    /**
     * @param array[Parameter|Definition] $conf
     */
    public function buildFromArray(array $conf)
    {
        if (false === $this->normalizersConfigured) {
            $this->normalizer = $this->getDefaultNormalizers();
        }

        if (false === $this->parameterFactoryConfigured) {
            $this->parameterFactory = $this->getDefaultParameterFactory();
        }

        $this->parameterFactory->setNormalizer($this->normalizer);

        if (isset($conf['parameters'])) {
            foreach ($conf['parameters'] as $parameterConf) {
                if ($parameterConf instanceof Parameter) {
                    $this->container = $this->parameterFactory->create($parameterConf, $this->container);
                }
            }
        }

        if (false === $this->serviceFactoryConfigured) {
            $this->serviceFactory = $this->getDefaultServiceFactory();
        }

        $this->serviceFactory->setNormalizer($this->normalizer);

        if (isset($conf['services'])) {
            foreach ($conf['services'] as $serviceName => $serviceConf) {
                if ($serviceConf instanceof Definition) {
                    $this->container = $this->serviceFactory->create($serviceConf, $this->container);
                }
            }
        }
    }

    /**
     * @return Loader
     */
    private function getDefaultLoaders()
    {
        $this->resolver->addLoader(new YamlFileLoader($this->locator, $this->resources));
        $this->resolver->addLoader(new PhpFileLoader($this->locator, $this->resources));

        $loader = new DelegatingLoader($this->resolver);

        if (null !== $this->cacheDir) {
            $loader = new CacheLoader($loader, $this->resources);
            $loader->setCacheDir($this->cacheDir);
        }

        return $loader;
    }

    /**
     * @return NormalizerInterface
     */
    private function getDefaultNormalizers()
    {
        return new ChainNormalizer(
            array(
                new PimpleNormalizer(),
                new ExpressionNormalizer(),
                new EnvironmentNormalizer()
            )
        );
    }

    /**
     * @return AbstractParameterFactory
     */
    private function getDefaultParameterFactory()
    {
        return new ProxyParameterFactory();
    }

    /**
     * @return AbstractServiceFactory
     */
    private function getDefaultServiceFactory()
    {
        return new ServiceFactory();
    }
}
