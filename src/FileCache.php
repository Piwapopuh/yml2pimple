<?php
/**
 * Created by PhpStorm.
 * User: draeger
 * Date: 15.09.2015
 * Time: 15:09
 */

namespace G\Yaml2Pimple;

use Symfony\Component\Config\ConfigCache;

class FileCache
{
    private $id;
    private $cache = null;
    private $resources = array();
    
    private function initCache($id)
    {
        if ( null == $this->cache || $this->id !== $id ) {
            $this->cache = new ConfigCache($id, true);
            $this->id = $id;
        }
    }
    
    public function setResources($resources)
    {
        $this->resources = $resources;
    }
    
    public function contains($id)
    {
        $this->initCache($id);
        return $this->cache->isFresh();
    }
    
    public function fetch($id)
    {
        $this->initCache($id);
        return require (string)$this->cache;
    }
    
    public function save($id, $data)
    {
        $this->initCache($id);
        $this->cache->write('<?php return ' . var_export($data, true) . ';', $this->resources);
    }  
    
}
