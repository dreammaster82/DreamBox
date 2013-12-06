<?php
$CONFIG = array(
	'database' => array(
		'host' => 'localhost',
		'port' =>  3306,
		'user' => '',
		'pass' => '',
		'database' => ''
	),
	'email' => array(
		'email_system'=>'',
		'from_email' => '',
	),
	'is_cache' => 0,
	'site' => '',
	'title' => '',
	'debug' => 1,
	'debug_ip' => array('127.0.0.1'),
	'no_apache' => 1,
	'memcache_priority' => 1 //Приоритет классов 0 - Memcache, 1 - Memcached
);
?>