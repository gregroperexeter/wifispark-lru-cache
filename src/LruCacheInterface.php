<?php
namespace WifiSpark;

interface LruCacheInterface
{
	public function __construct(string $queueName, int $maxItems);

	public function get($key);

	public function set($key, $value);
}
