<?php
namespace DDCloud;

class MemcachedStore implements CacheInterface {
    /**
     * The Memcached instance.
     *
     * @var \Memcached
     */
    protected $memcached;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new Memcached store.
     *
     * @param  \Memcached  $memcached
     * @param  string      $prefix
     * @return void
     */
    public function __construct($config, $prefix = '') {
        $this->setPrefix($prefix);
        $this->memcached = new \Memcached();
        if ($config["options"]) {
            $this->memcached->setOptions($config["options"]);
        }
        if ($config["servers"]) {
            $this->memcached->addServers($config["servers"]);
        }
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key) {
        $value = $this->memcached->get($this->prefix.$key);

        if ($this->memcached->getResultCode() == 0) {
            return $value;
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys) {
        $prefixedKeys = array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys);

        $values = $this->memcached->getMulti($prefixedKeys, \Memcached::GET_PRESERVE_ORDER);

        if ($this->memcached->getResultCode() != 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds) {
        return $this->memcached->set(
            $this->prefix.$key, $value, $this->calculateExpiration($seconds)
        );
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds) {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix.$key] = $value;
        }

        return $this->memcached->setMulti(
            $prefixedValues, $this->calculateExpiration($seconds)
        );
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds) {
        return $this->memcached->add(
            $this->prefix.$key, $value, $this->calculateExpiration($seconds)
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function increment($key, $value = 1) {
        return $this->memcached->increment($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int|bool
     */
    public function decrement($key, $value = 1) {
        return $this->memcached->decrement($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function forever($key, $value) {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key) {
        return $this->memcached->delete($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush() {
        return $this->memcached->flush();
    }

    /**
     * Get the underlying Memcached connection.
     *
     * @return \Memcached
     */
    public function getMemcached() {
        return $this->memcached;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix) {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }
}
