<?php

namespace Bisna\Common\Cache;

use Cache\Adapter\PHPArray\ArrayCachePool;

class ArrayCache extends ArrayCachePool
{
    private string $_namespace;
    public function __construct(array $options)
    {
        $limit = $options['limit'] ?? null;
        parent::__construct($limit);
    }

    public function setNamespace($namespace) {
        $this->_namespace = $namespace;
    }

    public function getNamespace() {
        return $this->_namespace;
    }
}