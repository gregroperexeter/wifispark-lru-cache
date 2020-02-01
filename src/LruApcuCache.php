<?php
namespace WifiSpark;

/**
* LRU cache with finite number of items
*
* This is an implementation of an LRU cache using an cross-request cache
* persisted associative array to store an ordered queue of keys of the items
* also stored in the cache.
*
* The basic premise of LRU is to keep the most recently used items at the front
* of the queue and remove the items that are least recently used from the back
* when the queue length reaches a defined maximum.
*
* This class uses PHP's APCu. Whilst understanding the limitations of APCu
* (can only be used on a single instance and not with FastCGI, i.e. Nginx and
* PHP FPM) I thought it apt for this task.  Should a solution need to be
* achieved in a cloud-based scalable architecture I would use Memcached, Redis
* DynamoDB or some other distributed key/value pair store.
*
* This class could be extended to override the protected methods for different
* cache solutions.
*
* Other nice to haves (more time available):
* - TTL expiry on items
*/

class LruApcuCache implements LruCacheInterface
{
    protected $maxItems;

    protected $queueName;

    protected $queue;

    public function __construct(string $queueName, int $maxItems = 10)
    {
        $this->maxItems = $maxItems;
        if ($queueName === null || $queueName === '') {
            throw new \exception('Please specifiy a queue name');
        }
        $this->queueName = $queueName;
        $this->queue = [];
        $this->setupQueue();
    }

    public function get($key)
    {
        //if key is item in the queue
        if (array_key_exists($key, $this->queue)) {
            $exists = $this->checkItemExistsInCache($key);
            //if item actually exists in the cache
            if ($exists) {
                //remove item and push to front of the queue.....this doesn't change the length of queue
                unset($this->queue[$key]);
                $this->queue = array($key => null) + $this->queue;

                //persist the queue to cache
                $this->storeQueueInCache();

                //retrieve item from cache and return value
                return $this->fetchItemFromCache($key);
            } else {
                return null;
            }
        } else {
            //check if item actually exists in the cache
            if ($this->checkItemExistsInCache($key)) {
                return $this->fetchItemFromCache($key);
            }
        }
    }

    public function set($key, $value): void
    {
        //if key is item in the queue
        if (array_key_exists($key, $this->queue)) {
            //remove item and push to front of the queue.....this doesn't change the length of queue
            unset($this->queue[$key]);
            $this->queue = array($key => null) + $this->queue;

            //persist the queue to cache
            $this->storeQueueInCache();

            //persist item to cache
            $this->storeItemInCache($key, $value);
        } else {
            //push item to front of the queue
            $this->queue = array($key => null) + $this->queue;

            //persist the queue to cache
            $this->storeQueueInCache();

            //persist item to cache
            $this->storeItemInCache($key, $value);

            //if length of queue exceeds maxItems
            if (count($this->queue) > $this->maxItems) {
                //get key of last item
                $lastKey = $this->getLastKey($this->queue);

                //remove last item from the queue
                array_pop($this->queue);

                //persist the queue to cache
                $this->storeQueueInCache();

                //remove last item from cache
                $this->removeItemFromCache($lastKey);
            }
        }
    }

    protected function setupQueue(): void
    {
        //attempt to fetch the queue from cache
        $queueExists = $this->checkQueueExistsInCache();
        if (!$queueExists) { //queue doesn't exist - so store new empty queue first then fetch
            $this->storeQueueInCache();
        }
        $this->fetchQueuefromCache();
    }

    protected function removeItemFromCache($key): void
    {
        \apcu_delete($key);
    }

    protected function checkItemExistsInCache($key)
    {
        return \apcu_exists($key);
    }

    protected function storeItemInCache($key, $value): void
    {
        \apcu_store($key, serialize($value));
    }

    protected function fetchItemFromCache($key)
    {
        return unserialize(\apcu_fetch($key));
    }

    protected function checkQueueExistsInCache()
    {
        return \apcu_exists($this->queueName);
    }

    protected function storeQueueInCache(): void
    {
        \apcu_store($this->queueName, serialize($this->queue));
    }

    protected function fetchQueuefromCache(): void
    {
        $this->queue = unserialize(\apcu_fetch($this->queueName));
    }

    protected function getLastKey(array $array)
    {
        if (!is_array($array) || empty($array)) {
            return null;
        }
        return array_keys($array)[count($array) - 1];
    }

    //public utility methods - used for basic testing
    public function showInfo()
    {
        return \apcu_cache_info();
    }

    public function showQueueInCache()
    {
        return unserialize(\apcu_fetch($this->queueName));
    }
}
