## Ensure APC User Cache is installed on environment
http://pecl.php.net/package/APCu

## Configure in php.ini
[apcu]
extension=php_apcu.dll
apc.enabled=1
apc.shm_size=32M
apc.ttl=7200
apc.enable_cli=1
apc.serializer=php

## Run composer install
Sets up PSR-4 class autoloading and PHPUnit dependencies

## Run the PHPUnit tests (WINDOWS) from within the project directory
	.\vendor\bin\phpunit --bootstrap .\vendor\autoload.php .\tests

