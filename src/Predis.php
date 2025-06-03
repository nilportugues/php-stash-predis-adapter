<?php

declare(strict_types=1);

namespace NilPortugues\Stash\Driver;

use Stash;
use Predis\Client;

class Predis extends Stash\Driver\AbstractDriver
{
    protected array $keyCache = [];

    protected array $redisArrayOptionNames = [
        "previous",
        "function",
        "distributor",
        "index",
        "autorehash",
        "pconnect",
        "retry_interval",
        "lazy_connect",
        "connect_timeout",
    ];

    /**
     * Predis constructor.
     */
    public function __construct(protected Client $predis) {
    }

    protected function setOptions(array $options = []): void
    {
        //nothing is OK :)
    }

    /**
     * Properly close the connection.
     */
    public function __destruct()
    {
        try {
            $this->predis->disconnect();
        } catch (\Exception $e) {
            /*
             * \Redis::close will throw a \RedisException("Redis server went away") exception if
             * we haven't previously been able to connect to Redis or the connection has severed.
             */
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $value = $this->predis->get($this->makeKeyString($key));
        if ($value === null) {
            return false;
        }
        return unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        $store = serialize(['data' => $data, 'expiration' => $expiration]);
        if (is_null($expiration)) {
            $this->predis->set($this->makeKeyString($key), $store);
            return true;
        }

        $ttl = (int)$expiration - time();

        // Prevent us from even passing a negative ttl'd item to redis,
        // since it will just round up to zero and cache forever.
        if ($ttl < 1) {
            return true;
        }

        $this->predis->setex($this->makeKeyString($key), $ttl, $store);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->predis->flushDB();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->predis->incr($keyString); // increment index for children items
        //$this->redis->delete($keyReal); // remove direct item.
        $this->predis->del([$keyReal]);
        $this->keyCache = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists(Client::class, true);
    }

    /**
     * Turns a key array into a key string. This includes running the indexing functions used to manage the Redis
     * hierarchical storage.
     *
     * When requested the actual path, rather than a normalized value, is returned.
     *
     * @param  array  $key
     * @param  bool   $path
     * @return string
     */
    protected function makeKeyString(array|string $key, bool $path = false): string
    {
        $key = \Stash\Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->predis->get($pathKey);
                $this->keyCache[$pathKey] = $index;
            }

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
