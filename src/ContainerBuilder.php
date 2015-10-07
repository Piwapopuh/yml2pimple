<?php

namespace G\Yaml2Pimple;

use G\Yaml2Pimple\Factory\AbstractParameterFactory;
use G\Yaml2Pimple\Factory\AbstractServiceFactory;
use G\Yaml2Pimple\Normalizer\PimpleNormalizer;

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

    /**
     * @param AbstractParameterFactory $parameterFactory
     */
    public function setParameterFactory(AbstractParameterFactory $parameterFactory)
    {
        $this->parameterFactory = $parameterFactory;
        $this->parameterFactory->setNormalizer($this->normalizer);
        
        return $this;
    }

	public function setServiceFactory(AbstractServiceFactory $serviceFactory)
	{
		$this->serviceFactory = $serviceFactory;	
		$this->serviceFactory->setNormalizer($this->normalizer);
        
		return $this;
	}    
    
	public function setNormalizer($normalizer)
	{
		$this->normalizer = $normalizer;
		
		return $this;
	}
	
	protected function addDefaultNormalizer()
	{
		$this->setNormalizer(new PimpleNormalizer($this->container));
        
        return $this;
	}

    /**
     * @param $serializer
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
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
            foreach($k as $key => $value) {
                $this->container[ $key ] = $value;
            }
        } else {
            $this->container[ $k ] = $v;
        }
	}

    public function load($file)
    {
        $conf = $this->loader->load($file);
        $this->buildFromArray($conf);
    }

    public function buildFromArray($conf)
    {
		if ( null === $this->normalizer ) {
			$this->addDefaultNormalizer();
		}

        foreach ($conf['parameters'] as $parameterConf)
        {
            if ($parameterConf instanceof Parameter) {
                $this->container = $this->parameterFactory->create($parameterConf, $this->container);
            }
        }	
		
        foreach ($conf['services'] as $serviceName => $serviceConf)
		{
            if ($serviceConf instanceof Definition) {
                $this->container = $this->serviceFactory->create($serviceConf, $this->container);
            }
        }
    }

    public function serialize($data)
    {
        if (is_null($this->serializer)) {
            return $data;
        }

        $data = $this->serializer->wrapData($data);
        return serialize($data);
    }

    public function unserialize($data)
    {
        if (is_null($this->serializer)) {
            return $data;
        }

        $data = unserialize($data);
        $data = $this->serializer->unwrapData($data);
        return $data;
    }
}
