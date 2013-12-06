<?php
setlocale(LC_ALL, array('ru_RU.UTF-8', 'Russian_Russia.1251'));
date_default_timezone_set('Europe/Moscow');
define(CLIENT_PATH, realpath($_SERVER['DOCUMENT_ROOT']));
define(MODULES_PATH, CLIENT_PATH.'/modules');
define(ADMIN_PATH, CLIENT_PATH.'/admin');
define(VERSION, '2.1');
include_once CLIENT_PATH.'/include/config.php';

//---Core---//
class Core{

	private $errors = array();

	public $ret = array(), $cfg = array(), $globalConfig;

	const NOT_KEYS = 0, CONNECTED = 1, UTIL = 4, SCROLL = 8, MEMCACHE = 16, ALL_KEYS = 31,
			CLASS_NAME = 0, MODULE = 1, ADMIN = 2, ABSTRACT_CLASS = 3;

	function __construct($flags = false) {
		global $CONFIG;
		$this->globalConfig = $CONFIG;
		if($flags === false){
			$flags = self::ALL_KEYS;
		}
		if($flags & self::MEMCACHE){
			$this->getClass('Memcache');
		}
		if($flags & self::UTIL){
			$this->getClass('Util');
		}
		if($flags & self::SCROLL){
			$this->getClass('Scroll');
		}
		if($flags & self::CONNECTED){
			$C = $this->getClass('Db');
			$C->connect();
		}
		if($_COOKIE['sessn']){
			setSession();
		}
	}

	function getClass($class = array(), $params = false){
		static $classes = array();
		$getCont = true;
		if(is_string($class)){
			$class = array(self::CLASS_NAME => $class, self::MODULE => 'core', self::ADMIN => false);
			$getCont = false;
		}
		if(!$class[self::CLASS_NAME]){
			return false;
		}
		$cn = ($class[self::ADMIN] ? 'core\\admin' : $class[self::MODULE]).'\\'.$class[self::CLASS_NAME];
		if(!$classes[$cn]){
			if(!class_exists($cn)){
				$name = $mp = '';
				if($getCont && $class[self::CLASS_NAME] != 'CContent' && !$classes['core\CContent']){
					if($class[self::ADMIN]){
						$this->getClass(array(self::CLASS_NAME => 'CContent', self::ADMIN => true, self::ABSTRACT_CLASS => true));
					} else {
						$this->getClass(array(self::CLASS_NAME => 'CContent', self::ABSTRACT_CLASS => true, self::MODULE => 'core'));
					}
				}
				if(strpos($class[self::CLASS_NAME], 'Viewer')){
					if($class[self::CLASS_NAME] != 'CContentViewer' && !$classes['core\CContentViewer']){
						$this->getClass(array(self::CLASS_NAME => 'CContentViewer', self::ABSTRACT_CLASS => true, self::MODULE => 'core'));
					}
					$mp = str_replace('Viewer', '', $class[self::CLASS_NAME]);
					$this->getClass(array(self::CLASS_NAME => $mp, self::MODULE => $class[self::MODULE]));
					$mp = strtolower($mp);
					$name = $mp.'_viewer';
				} else {
					$mp = $name = strtolower($class[self::CLASS_NAME]);
					if($class[self::ADMIN]){
						$name .= '_admin';
					}
				}
				$path = '';
				if($class[self::MODULE] != 'core'){
					if($class[self::MODULE] == 'admin'){
						$path .= ADMIN_PATH.'/modules/'.strtolower($class[self::CLASS_NAME]);
					} else {
						$mp = $class[self::MODULE];
						$path .= MODULES_PATH.'/'.$mp;
					}
				} else {
					if($class[self::ADMIN]){
						$path .= ADMIN_PATH.'/include';
					} else {
						$path .= CLIENT_PATH.'/include';
					}
				}
				if(file_exists($path.'/'.$name.'.php')){
					include $path.'/'.$name.'.php';
				} else {
					$classes[$cn] = false;
					$this->setError('Class '.$cn.' not defined', __CLASS__);
				}
			}
			if(!isset($classes[$cn])){
				if($class[self::ABSTRACT_CLASS]){
					$classes[$cn] = true;
				} else {
					$classes[$cn] = new $cn($mp, $params);
					$classes[$cn]->Core = $this;
					if(!in_array($cn, array('core\Db', 'core\Util', 'core\Scroll', 'core\Memcache'))){
						if($classes['core\Db']){
							$classes[$cn]->Db = $classes['core\Db'];
						}
						if($classes['core\Util']){
							$classes[$cn]->Util = $classes['core\Util'];
						}
						if($classes['core\Scroll']){
							$classes[$cn]->Scroll = $classes['core\Scroll'];
						}
						if($classes['core\Memcache']){
							$classes[$cn]->Memcache = $classes['core\Memcache'];
						}
					}
				}
			}
		}
		return $classes[$cn];
	}
	
	function getVersion(){
		return VERSION;
	}
	
	function process($class = array()){
		if(is_string($class)){
			$class = array(Core::CLASS_NAME => $class, Core::MODULE => '', Core::ADMIN => false);
		}
		$class[Core::MODULE] = $class[Core::MODULE] ? $class[Core::MODULE] : $_REQUEST['module'];
		$class[Core::CLASS_NAME] = $class[Core::CLASS_NAME] ? $class[Core::CLASS_NAME] : ($class[Core::ADMIN] ? ucfirst($class[Core::MODULE]) : ucfirst($class[Core::MODULE]).'Viewer');
		if($class[Core::CLASS_NAME]){
			$C = $this->getClass($class);
			if(!$this->errors){
				$this->ret['content'] = $C->process();
				foreach ($C->ret as $k => $v){
					$this->ret[$k] = $v;
					unset($C->ret[$k]);
				}
			} else {
				$this->ret['content'] = $this->getClass('Util')->error($this->errors);
				$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = 'Ошибка 404: Запрашиваемая страница не найдена';
			}
		}
	}

	function setError($error, $class = 'all'){
		$this->errors[$class][] = $error;
	}
}

function decode_hash($hash){
	$str = str_rot13($hash);
	$i = ord(substr($str, 5, 1)) - 97;
	$str = substr_replace($str, '', 5, 1);
	$id = substr($str, $i, 12);
	return array((float)str_replace('a', 0, substr($id, -((int)ord(substr($id, 0, 1)) - 97))), substr_replace($str, '', $i, 12));
}

function encode_hash($id, $str){
	$id = str_replace(0, 'a', $id);
	$l = strlen($id);
	$id = substr($str, rand(1, 15), 11 - $l).$id;
	$i = rand(0, 25);
	$str = substr($str, 0, $i).chr($l + 97).$id.substr($str, $i);
	return str_rot13(substr($str, 0, 5).chr($i + 97).substr($str, 5));
}

function print_r1($obj){
	echo"<pre>";
	print_r($obj);
	echo"</pre>";
}

function setSession($name='sessn'){
	if(!isset($_SESSION)){
		$value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : 1;
		setcookie($name,$value,time()+60*60*24, '/');
		session_name($name);
		session_start();
	}
}

function getRealIp(){
	if (!empty($_SERVER['HTTP_CLIENT_IP'])){
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

function debug($s){
	global $CONFIG;
	if($CONFIG['debug'] && in_array(getRealIp(), $CONFIG['debug_ip'])){
		print_r1($s);
	}
}
?>