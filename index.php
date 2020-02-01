<?php
require "vendor/autoload.php";

use WifiSpark\LruApcuCache;

$cache = new LruApcuCache('queue1');

$queue = $cache->showQueueInCache();
echo '<PRE>'. var_export($queue, true) . '</PRE>';

// $cache->set('itemA','data2');
// $cache->set('itemB','data2');
// $cache->set('itemC','data3');
// $cache->set('itemD','data4');
// $cache->set('itemE','data5');
// $cache->set('itemF','data6');
// $cache->set('itemG','data7');
// $cache->set('itemH','data8');
// $cache->set('itemJ','data9');
// $cache->set('itemK','data10');
// $cache->set('itemL','data11');
// $cache->set('itemM','data12');
// $cache->set('itemN','data13');

$result = $cache->get('itemK');
echo '<PRE>'. var_export($result, true) . '</PRE>';


$info = $cache->showInfo();
echo '<PRE>'. var_export($info, true) . '</PRE>';




