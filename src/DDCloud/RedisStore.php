<?php
namespace DDCloud;

class RedisStore implements CacheInterface {
    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;
    protected $redis;

    /**
     * Create a new Redis store.
     *
     * @return void
     */
    public function __construct($config, $prefix = '') {
        $this->prefix = $prefix;
        try {
            $this->redis = new \Redis();
            $this->redis->connect($config["host"], $config["port"], $config["timeout"]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key) {
        $value = $this->redis->get($this->prefix.$key);
        return ! is_null($value) ? $this->unserialize($value) : null;
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
        $results = [];

        $values = $this->redis->mget(array_map(function ($key) {
            return $this->prefix.$key;
        }, $keys));

        foreach ($values as $index => $value) {
            $results[$keys[$index]] = ! is_null($value) ? $this->unserialize($value) : null;
        }

        return $results;
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
        return (bool) $this->redis->setex(
            $this->prefix.$key, (int) max(1, $seconds), $this->serialize($value)
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
        $this->redis->multi();

        $manyResult = null;

        foreach ($values as $key => $value) {
            $result = $this->put($key, $value, $seconds);

            $manyResult = is_null($manyResult) ? $result : $result && $manyResult;
        }

        $this->redis->exec();

        return $manyResult ?: false;
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
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool) $this->redis->eval(
            $lua, [$this->prefix.$key, $this->serialize($value), (int) max(1, $seconds)], 1
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1) {
        return $this->redis->incrby($this->prefix.$key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function decrement($key, $value = 1) {
        return $this->redis->decrby($this->prefix.$key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return bool
     */
    public function forever($key, $value) {
        return (bool) $this->redis->set($this->prefix.$key, $this->serialize($value));
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key) {
        return (bool) $this->redis->del($this->prefix.$key);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush() {
        $this->redis->flushdb();
        return true;
    }

    /**
     * Get the Redis database instance.
     *
     */
    public function getRedis() {
        return $this->redis;
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

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value) {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value) {
        return is_numeric($value) ? $value : unserialize($value);
    }

    /**
     * 此方法解决并发穿透缓存问题
     * 缓存时间接近到期时间时,禁止使用此方法
     * 使用此方法时,必须缓存时间远大于到期时间才有效果
     *
     * @param string $key
     * @param int $dueTime 到期时间(s)
     * @param int $overTime 延长时间(s)
     * @return Array
     */
    public function ddGet($key, $dueTime=2, $overTime=5) {
        $result = [];
        $ttl_time = $this->redis->ttl($key);
        if (-1 === $ttl_time) {
            $result = $this->get($key);
        } elseif (-2 === $ttl_time) {
            $result = false;
        } elseif ($ttl_time < $dueTime) {
            $this->redis->setTimeout($key, $overTime);
            $result = false;
        } else {
            $result = $this->get($key);
        }
        return $result;
    }

    /**
     * 此方法解决并发穿透缓存问题
     * 缓存时间接近到期时间时,禁止使用此方法
     * 使用此方法时,必须缓存时间远大于到期时间才有效果
     *
     * @param string $key
     * @param int $dueTime 到期时间(s)
     * @param int $overTime 延长时间(s)
     * @return Array
     * @see ddGet function
     */
    public function ddMget($keys, $dueTime=2, $overTime=5) {
        $results = [];
        foreach($keys as $key) {
            $ttl_time = $this->redis->ttl($key);
            if (-1 === $ttl_time) {
                $results[$key] = ['d'=>$this->get($key), 'f'=>false];
            } elseif (-2 === $ttl_time) {
                $results[$key] = ['d'=>false, 'f'=>false];
            } elseif ($ttl_time < $dueTime) {
                $this->redis->setTimeout($key, $overTime);
                $results[$key] = ['d'=>$this->get($key), 'f'=>true];
            } else {
                $results[$key] = ['d'=>$this->get($key), 'f'=>false];
            }
        }
        return $results;
    }
}