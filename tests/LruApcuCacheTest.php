<?php
use PHPUnit\Framework\TestCase;

use WifiSpark\LruApcuCache;

class LruApcuCacheTest extends TestCase
{
	protected $cache;

	protected $queueName;

	public function setUp()
    {
        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped(
                'apc.enable_cli MUST be enabled in order to run this unit test'
            );
        }
        $this->queueName = 'testQueue';
        $this->cache = new LruApcuCache($this->queueName);
    }

    public function teardown()
    {
    	apcu_clear_cache();
    }

    public function testEmptySerializedQueueArrayPersistedInCache()
    {
    	$this->assertEquals(serialize([]), apcu_fetch($this->queueName));
    }

    public function testPersistedItemInCacheCanBeFetchedAndAlsoExistsInQueueInCache()
    {
    	$this->cache->set('item1','data1');

    	$this->assertTrue(apcu_exists('item1'));
    	$this->assertEquals('data1', $this->cache->get('item1'));
    	$this->assertTrue(array_key_exists('item1', unserialize(apcu_fetch($this->queueName))));
    }

    public function testNewlyPersistedItemGoesToTheFrontOfTheQueue()
    {
    	$this->cache->set('item1','data1');
    	$this->cache->set('item2','data2');
    	$this->cache->set('item3','data3');

    	$queue = unserialize(apcu_fetch($this->queueName));
    	reset($queue);
    	$firstKey = key($queue);
    	$this->assertEquals('item3', $firstKey);

    	$queue = unserialize(apcu_fetch($this->queueName));
    	end($queue);
    	$LastKey = key($queue);
    	$this->assertEquals('item1', $LastKey);
    }

    public function testFetchedItemGoesToTheFrontOfTheQueue()
    {
    	$this->cache->set('item1','data1');
    	$this->cache->set('item2','data2');
    	$this->cache->set('item3','data3');
    	$this->cache->get('item1');

    	$queue = unserialize(apcu_fetch($this->queueName));
    	reset($queue);
    	$firstKey = key($queue);
    	$this->assertEquals('item1', $firstKey);
   	}

   	public function testItemsExceedingMaxQueueLengthRemovedFromQueueAndRemovedFromCache()
   	{
   		$maxItems = 10;
   		$i = 1;
   		while($i <= $maxItems)
   		{
   			$this->cache->set('item'.$i, 'data'.$i);
   			$i++;
   		}
   		$queue = unserialize(apcu_fetch($this->queueName));
    	reset($queue);
    	$firstKey = key($queue);
    	$this->assertEquals('item10', $firstKey);

    	$queue = unserialize(apcu_fetch($this->queueName));
    	end($queue);
    	$LastKey = key($queue);
    	$this->assertEquals('item1', $LastKey);

    	$this->cache->set('item11', 'data11');

    	$queue = unserialize(apcu_fetch($this->queueName));
    	end($queue);
    	$LastKey = key($queue);
    	$this->assertEquals('item2', $LastKey);

    	$queue = unserialize(apcu_fetch($this->queueName));
    	$this->assertFalse(array_key_exists('item1',$queue));
    	$this->assertFalse(apcu_exists('item1'));
    	$this->assertFalse(apcu_fetch('item1'));

   	}
}