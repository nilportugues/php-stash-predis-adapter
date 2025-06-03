<?php

namespace NilPortugues\Stash\Driver;

use Stash;
use Predis\Client;

class Predis extends Stash\Driver\AbstractDriver
{
    /**
     * @var Client
     */
    protected Client $predis;

    /**
     *
     * The cache of indexed keys.
     *
     * @var array
     */
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
     * @param Client $predis
     */
    public function __construct(Client $predis) {
       $this->predis = $predis;
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
        if ($this->predis instanceof Client) {
            try {
                $this->predis->disconnect();
            } catch (\Exception $e) {
                /*
                 * \Redis::close will throw a \RedisException("Redis server went away") exception if
                 * we haven't previously been able to connect to Redis or the connection has severed.
                 */
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        return unserialize($this->predis->get($this->makeKeyString($key)));
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration): bool
    {
        $store = serialize(['data' => $data, 'expiration' => $expiration]);
        if (is_null($expiration)) {
            $this->predis->set($this->makeKeyString($key), $store);
            return true;
        }

        $ttl = $expiration - time();

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
    public function clear($key = null): bool
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
    public function purge(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable(): bool
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
    protected function makeKeyString($key, bool $path = false): string
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
    public function isPersistent(): bool
    {
        return true;
    }
}
