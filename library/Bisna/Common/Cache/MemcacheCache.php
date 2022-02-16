<?php

namespace Bisna\Common\Cache;

use Cache\Adapter\Memcached\MemcachedCachePool;

class MemcacheCache extends MemcachedCachePool {

    private string $_namespace;
    
    public function __construct(array $options) {
        $cache = new \Memcached();
        foreach ($options['servers'] as $server) {
            $host = $server['host'] ?? 'localhost';
            $port = $server['port'] ?? 11211;
            $cache->addServer($host, $port);
        }
        parent::__construct($cache);
    }

    public function setNamespace($namespace) {
        $this->_namespace = $namespace;
    }

    public function getNamespace() {
        return $this->_namespace;
    }
}