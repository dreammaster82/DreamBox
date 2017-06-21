<?php
$CONFIG = array(
	'database' => array(
		'host' => 'localhost',
		'port' =>  3306,
		'user' => 'user',
		'pass' => 'password',
		'database' => 'db_name'
	),
	'email' => array(
		'email_system'=>'cron@test.ru',
		'from_email' => 'info@test.ru',
	),
	'is_cache' => 0,
	'site' => 'test.ru',
	'title' => '',
	'description' => '',
	'keywords' => '',
	'debug' => 1,
	'debug_ip' => array('127.0.0.1'),
	'no_apache' => 1,
	'memcache_priority' => 1, //Приоритет классов 0 - Memcache, 1 - Memcached, -1 - No memcache
	'memcache_connect' => 'memcached.sock',
	'handler_socket' => array(
		'port' => 9998,
		'port_wr' => 9999,
	)
);
?>