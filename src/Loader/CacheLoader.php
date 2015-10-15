<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 15.09.2015
 * Time: 15:09
 */

namespace G\Yaml2Pimple\Loader;

use G\Yaml2Pimple\FileCache;
use Symfony\Component\Config\Resource\FileResource;

class CacheLoader
{
    /** @var \G\Yaml2Pimple\Loader\AbstractLoader $loader */
    private $loader;

    /** @var \G\Yaml2Pimple\ResourceCollection $resources*/
    private $resources;

    /** @var null|string  */
    private $cacheDir;

    /** @var FileCache $cache */
    private $cache;

    /** @var array */
    private static $dependingClasses = array(
        '\G\Yaml2Pimple\Loader\CacheLoader',
        '\G\Yaml2Pimple\Parameter',
        '\G\Yaml2Pimple\Definition',
    );

    /**
     * @param             $loader
     * @param \G\Yaml2Pimple\ResourceCollection $resources
     * @param null|string $cacheDir
     */
    public function __construct($loader, $resources, $cacheDir = null)
    {
        $this->loader = $loader;
        if (null === $cacheDir) {
            $cacheDir = sys_get_temp_dir();
        }
        $this->resources = $resources;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param $dependingClasses
     */
    public static function setDependingClasses($dependingClasses)
    {
        static::$dependingClasses = $dependingClasses;
    }

    /**
     * @param FileCache $cache
     *
     * @return $this
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param mixed $cacheDir
     *
     * @return $this
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        return $this;
    }

    /**
     * @param string $resource
     *
     * @return mixed
     */
    public function load($resource)
    {
        $id = sprintf('%s/%s.php', $this->cacheDir, crc32($resource));

        if (null === $this->cache) {
            $this->cache = new FileCache();
        }
        $conf = null;
        // check the cache
        if (!$this->cache->contains($id)) {
            // not in cache, delegate to the next loader
            $conf = $this->loader->load($resource);
            // get the list of depending resources
            $this->addDependingResources();
            $this->cache->setResources($this->resources->all());
            $this->resources->clear();
        }

        if (null !== $conf) {
            $this->cache->save($id, $conf);
        }

        if (null === $conf) {
            $conf = $this->cache->fetch($id);
        }

        return $conf;
    }

    /**
     * adds some files to check for modifications
     * if these files are changed the cache must be invalidated
     */
    private function addDependingResources()
    {
        foreach (static::$dependingClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $this->resources->add(new FileResource($reflection->getFileName()));
        }
    }
}
