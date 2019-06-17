<?php
namespace DDCloud;

/**
 * Class CacheManager
 */
class CacheManager {
    /**
     * cache config
     */
    protected $config;

    /**
     * The array of resolved cache stores.
     *
     * @var array
     */
    protected $stores = [
        "memcached" => null,
        "redis" => null,
    ];

    /**
     * Create a new CacheInterface manager instance.
     *
     * @return void
     */
    public function __construct($cacheConfig) {
        $this->config = $cacheConfig;
    }

    /**
     * Get a cache store instance by name, wrapped in a repository.
     *
     * @param  string|null $name
     */
    public function store($name = null) {
        $name = $name ?: $this->getDefaultDriver();
        if (!isset($this->stores[$name])) {
            return "no $name driver";
        }
        if ($this->stores[$name] == null) {
            switch ($name) {
                case "memcached":
                    return $this->createMemcachedDriver();
                    break;
                case "redis":
                    return $this->createRedisDriver();
                    break;
            }
        }
        return $this->stores[$name];
    }

    protected function createMemcachedDriver() {
        $prefix = $this->config["prefix"];
        return new MemcachedStore($this->config["memcached"], $prefix);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     */
    protected function createRedisDriver() {
        $prefix = $this->config["prefix"];
        return new RedisStore($this->config["redis"], $prefix);

    }

    public function getDefaultDriver() {
        return $this->config["default"];
    }
}