<?php
setlocale(LC_ALL, array('ru_RU.UTF-8', 'Russian_Russia.1251'));
date_default_timezone_set('Europe/Moscow');
define(CLIENT_PATH, realpath($_SERVER['DOCUMENT_ROOT']));
define(MODULES_PATH, CLIENT_PATH.'/modules');
define(ADMIN_PATH, CLIENT_PATH.'/admin');
define(VERSION, '2.1');
include_once CLIENT_PATH.'/include/config.php';


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

//---Core---//
class Core{

	private $errors = array();

	public $ret = array(), $memcache, $cfg = array(), $globalConfig;

	const NOT_KEYS = 0, CONNECTED = 1, UTIL = 4, SCROLL = 8, ALL_KEYS = 15,
			CLASS_NAME = 0, MODULE = 1, ADMIN = 2, ABSTRACT_CLASS = 3;

	function __construct($flags = false) {
		global $CONFIG;
		$this->globalConfig = $CONFIG;
		if($flags === false){
			$flags = self::CONNECTED | self::UTIL | self::SCROLL;
		}
		if(class_exists('Memcache')){
			$this->memcache = new Memcache;
			if(!$this->memcache->connect($this->globalConfig['debug'] ? '127.0.0.1' : $this->globalConfig['site'], 11211)){
				$this->ret['js_after'] .= '<script>console.log("Cache not conected!");</script>';
			} elseif((int)$_REQUEST['flush_cache']){
				$ip = $this->getRealIp();
				if($this->globalConfig['debug']){
					$bool = false;
					foreach ($this->globalConfig['debug_ip'] as $v){
						if(strpos($ip, $v) !== false){
							$bool = true;
						}
					}
					if($bool){
						$this->memcache->flush();
					}
				}
			}
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
	
	function debug($s){
		$ip = $this->getRealIp();
		if($this->globalConfig['debug']){
			$bool = false;
			foreach ($this->globalConfig['debug_ip'] as $v){
				if(strpos($ip, $v) !== false){
					$bool = true;
				}
			}
			if($bool){
				print_r1($s);
			}
		}
	}

	function getClass($class = array(), $params = false){
		static $classes = array();
		if(is_string($class)){
			$class = array(self::CLASS_NAME => $class, self::MODULE => '', self::ADMIN => false);
		}
		if(!$class[self::CLASS_NAME]){
			return false;
		}
		$cn = ($class[self::ADMIN] ? 'admin' : $class[self::MODULE]).'\\'.$class[self::CLASS_NAME];
		if(!$classes[$cn]){
			if(!class_exists($cn)){
				$name = $mp = '';
				if($class[self::CLASS_NAME] != 'CContent' && !$classes['\CContent']){
					if($class[self::ADMIN]){
						$this->getClass(array(self::CLASS_NAME => 'CContent', self::ADMIN => true, self::ABSTRACT_CLASS => true));
					} else {
						$this->getClass(array(self::CLASS_NAME => 'CContent', self::ABSTRACT_CLASS => true));
					}
				}
				if(strpos($class[self::CLASS_NAME], 'Viewer')){
					if($class[self::CLASS_NAME] != 'CContentViewer' && !$classes['\CContentViewer']){
						$this->getClass(array(self::CLASS_NAME => 'CContentViewer', self::ABSTRACT_CLASS => true));
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
				if($class[self::MODULE]){
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
					if(!in_array($cn, array('\Db', '\Util', '\Scroll'))){
						if($classes['\Db']){
							$classes[$cn]->Db = $classes['\Db'];
						}
						if($classes['\Util']){
							$classes[$cn]->Util = $classes['\Util'];
						}
						if($classes['\Scroll']){
							$classes[$cn]->Scroll = $classes['\Scroll'];
						}
					}
				}
			}
		}
		return $classes[$cn];
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

//--------Class Util ----------//
class Util{
	
	function sortArray() {
		$argv = func_get_args();
		$arguments = $argv[1];
		$array = $argv[0];
		$code = '';
		for ($c = 0; $c < count($arguments); $c += 2) {
			if (in_array($arguments[$c + 1], array("ASC", "DESC"))) {
				$code .= 'if ($a["'.$arguments[$c].'"] != $b["'.$arguments[$c].'"]) {';
				if ($arguments[$c + 1] == "ASC") {
					$code .= 'return ($a["'.$arguments[$c].'"] < $b["'.$arguments[$c].'"] ? -1 : 1); }';
				}
				else {
					$code .= 'return ($a["'.$arguments[$c].'"] < $b["'.$arguments[$c].'"] ? 1 : -1); }';
				}
			}
		}
		$code .= 'return 0;';
		$compare = create_function('$a, $b', $code);
		usort($array, $compare);
		return $array;
	}

	function subString($string, $length=1, $adding_string='...', $encode = 'UTF-8'){
		if(mb_strlen($string, $encode) <= $length){
			return $string;
		} else {
			$string = htmlspecialchars_decode($string);
			$string = mb_substr($string, 0, $length, $encode).$adding_string;
			return htmlspecialchars($string);
		}
	}
	
	function getBrouserClass()
	{
		$sBrowserClass = "";
		if (preg_match("/Opera\W*(\d+(:?\.\d+)?)/",@$_SERVER["HTTP_USER_AGENT"], $aMatch)) 
		{
			$sBrowserClass = "isOpera";
			if (@$aMatch[1] &&  $aMatch[1] < 9) 
			{
				$sBrowserClass .= " isOpera8";
			}
		} 
		elseif (preg_match("/MSIE\W*(\d+(:?\.\d+)?)/",@$_SERVER["HTTP_USER_AGENT"], $aMatch)) 
		{
			$sBrowserClass = "isIE";
			if (@$aMatch[1] &&  $aMatch[1] < 7) 
			{
				$sBrowserClass .= " isIE6";
			}
			elseif (@$aMatch[1] &&  $aMatch[1] < 8) 
			{
				$sBrowserClass .= " isIE7";
			}
			elseif (@$aMatch[1] &&  $aMatch[1] < 9) 
			{
				$sBrowserClass .= " isIE8";
			}
			elseif (@$aMatch[1] &&  $aMatch[1] < 10) 
			{
				$sBrowserClass .= " isIE9";
			}
		}
		elseif (preg_match("/Firefox\W*(\d+(:?\.\d+)?)/",@$_SERVER["HTTP_USER_AGENT"], $aMatch)) 
		{
			$sBrowserClass = "Firefox";
		}
		if ($sBrowserClass) 
		{
			$sBrowserClass = ' class="' . $sBrowserClass . '"';
		}

		return $sBrowserClass;
	}

	function checkText($text, $length)
	{
		$text = substr($text, 0, $length);
		return preg_replace("/[^\w\x7F-\xFF\s]/", " ", $text);
	}

	function error($errors){
		$out = array();
		Header("HTTP/1.0 404 Not Found");
		if(is_array($errors)){
			foreach ($errors as $k => $v){
				$out['errors'] .= $k.': '.(is_array($v) ? implode(', ', $v) : $v).'; ';
			}
		} elseif(is_string($errors)) {
			$out['errors'] = $errors;
		}
		ob_start();
		include CLIENT_PATH.'/data/error.html';
		return ob_get_clean();
	}

	function mail($from, $from_text, $emailto, $subj, $body, $type="text/html", $file=''){
		if (!$from || !$emailto) return;

		$subj = base64_encode($subj);
		$subj = '=?UTF-8?B?' . $subj .'?=';

		$from_text = base64_encode($from_text);
		$from_text = '=?UTF-8?B?' . $from_text . '?=';

		$head = "";
		$head .= "MIME-Version: 1.0\r\n";
		$head .= "Content-Type: {$type}; charset=utf-8\r\n"; 
		//$head .= "Return-Path: {$CONFIG[email_alert]}\r\n";
		$head .= "Reply-To: $from\r\n";
		$head .= "From: $from_text <{$from}>\r\n";
		$head .= "X-Mailer: PHP mailer\r\n";
		$head .= "X-Sender: $from\r\n";

		$head .= "Content-Transfer-Encoding: 8bit\n";
		mail($emailto, $subj, $body, $head, '-f '.$this->Core->globalConfig['email']['email_system']);
	}

	function getmicrotime(){
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}

	function getrealpath($path){
		if(realpath($path)){
			return str_replace('\\', '/', realpath($path));
		} 
		if(realpath(CLIENT_PATH.$path)){
			return str_replace('\\', '/', realpath(CLIENT_PATH.$path));
		}
		$real = '';
		$real_arr = array();
		if(stripos($path, '../') !== false){
			$real = str_replace('\\', '/', realpath(''));
		} else {
			$real = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
			if(stripos($path, $real) !== false){
				$real = '';
			}
		}
		$add = '';
		if($real){
			if(stripos($real, '/') === 0){
				$add = '/';
			}
			$real_arr = explode('/', $real);
		} else {
			if(stripos($path, '/') === 0){
				$add = '/';
			}
		}
		$path_arr = explode('/', $path);
		foreach ($path_arr as $value){
			if($value == '..'){
				array_pop($real_arr);
			} elseif($value != '') {
				$real_arr[] = $value;
			}
		}
		return $add.implode('/', $real_arr);
	}

	function untag($string, $encode){
		$tags = array();
		preg_match_all('/(<.*?>)/i', $string, $tags);
		if($tags){
			$pos = 0;
			foreach ($tags[0] as $key => $value){
				$pos = mb_stripos($string, $value, 0, $encode);
				$len = mb_strlen($value, $encode);
				$string = mb_substr($string, 0, $pos, $encode).mb_substr($string, $pos + $len, mb_strlen($string, $encode), $encode);
				if(stripos($value, '</') !== false){
					$type = 1;
				} elseif(stripos($value, '/>')){
					$type = -1;
				} else {
					$type = 0;
				}
				$tags[0][$key] = array($value, $pos, $type);
			}
		}
		return array('string' => $string, 'tags' => $tags);
	}

	function tag($data, $encode){
		if($data['tags']){
			$string = $data['string'];
			$length = mb_strlen($string, $encode);
			$pos = 0;
			$prev = 0;
			foreach ($data['tags'][0] as $key => $value){
				$l1 = mb_strlen($string, $encode);
				if($value[1] < $length){                       
					$pos = $value[1] + $prev;
					$string = mb_substr($string, 0, $pos, $encode).$value[0].mb_substr($string, $pos, $l1, $encode);
					$prev += mb_strlen($value[0], $encode);
				} else {
					if($value[2] == 1){
						$string .= $value[0];
					}
					break;
				}
			}
		}
		return $string;
	}

	function checkString($string, $encode, $length, $adding_string = '...'){
		$data = $this->untag($string, $encode);
		$string = subString($data['string'], $encode, $length, $adding_string);
		$string = $this->tag(array('string' => $string, 'tags' => $data['tags']), $encode);
		return $string;
	}

	function getImagesArray($path){
		$path = $this->getrealpath($path);
		$files = array();
		if(file_exists($path)){
			try{
				$dir = new RecursiveDirectoryIterator($path, FilesystemIterator::NEW_CURRENT_AND_KEY | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
				foreach ($dir as $v){
					if($v->isFile()){
						$name = $v->getFilename();
						$t = explode('_', $name);
						if(is_numeric($t[0])){
							$files[$t[0]] = $name;
						}
						ksort($files, SORT_NUMERIC);
					}
				}
			} catch(UnexpectedValueException $e){
				echo $e->getMessage();
			}
		}
		return $files;
	}

	function memcacheSet($ind, $data, $class = 'all', $flag = false, $expire = 0){
		$ret = false;
		if(isset($this->Core->memcache) && $data){
			$ret = $this->Core->memcache->set($this->Core->globalConfig['site'].'_'.$ind, json_encode($data), $flag, $expire);
			$mc = array();
			if(is_file(CLIENT_PATH.'/files/memcache.txt')){
				$s = file_get_contents(CLIENT_PATH.'/files/memcache.txt');
				$arr = explode(';', $s);
				foreach ($arr as $v){
					if($v){
						list($cl, $param) = explode('_', $v);
						$mc[$cl][$param] = 1;
					}
				}
			}
			$mc[$class][$ind] = 1;
			$arr = array();
			foreach ($mc as $k => $v){
				foreach ($v as $k1 => $v1){
					$arr[] = $k.'_'.$k1;
				}
			}
			file_put_contents(CLIENT_PATH.'/files/memcache.txt', implode(';', $arr));
		}
		return $ret;
	}

	function memcacheGet($ind){
		if(isset($this->Core->memcache)){
			$js = $this->Core->memcache->get($this->Core->globalConfig['site'].'_'.$ind);
			if($js){
				return json_decode($js, true);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function memcacheDelete($ind, $class = 'all'){
		if(isset($this->Core->memcache)){
			if($this->Core->memcache->delete($this->Core->globalConfig['site'].'_'.$ind) && is_file(CLIENT_PATH.'/files/memcache.txt')){
				$mc = array();
				$s = file_get_contents(CLIENT_PATH.'/files/memcache.txt');
				$arr = explode(';', $s);
				foreach ($arr as $v){
					if($v){
						list($cl, $param) = explode('_', $v);
						$mc[$cl][$param] = 1;
					}
				}
				unset($mc[$class][$ind]);
				$arr = array();
				foreach ($mc as $k => $v){
					foreach ($v as $k1 => $v1){
						$arr[] = $k.'_'.$k1;
					}
				}
				file_put_contents(CLIENT_PATH.'/files/memcache.txt', implode(';', $arr));
			}
		}
	}

	function memcacheFlush($class = 'all'){
		if(isset($this->Core->memcache)){
			if(is_file(CLIENT_PATH.'/files/memcache.txt')){
				$s = file_get_contents(CLIENT_PATH.'/files/memcache.txt');
				$arr = explode(';', $s);
				foreach ($arr as $v){
					if($v){
						list($cl, $param) = explode('_', $v);
						if($cl == $class){
							$this->Core->memcache->delete($this->Core->globalConfig['site'].'_'.$param);
						} else {
							$mc[$cl][$param] = 1;
						}
					}
				}
				$arr = array();
				if($mc){
					foreach ($mc as $k => $v){
						foreach ($v as $k1 => $v1){
							$arr[] = $k.'_'.$k1;
						}
					}
				}
				file_put_contents(CLIENT_PATH.'/files/memcache.txt', implode(';', $arr));
			} else {
				$this->Core->memcache->flush();
			}
		}
	}
}
//-------Class Db --------//
class Db{
	private $link;
	function connect(){
		try{
			$this->link = new PDO(
				'mysql:host='.$CONFIG['database']['host'].';dbname='.$this->Core->globalConfig['database']['database'],
				$this->Core->globalConfig['database']['user'],
				$this->Core->globalConfig['database']['pass'],
				array(
					PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
				)
			);
		} catch (PDOException $ex){
			echo'Ошибка подключения: '.$ex->getMessage();
			exit();
		}
	}

	function queryOne($sql, $attr = array(), $pdo = PDO::FETCH_ASSOC){
		try{
			if($attr){
				$r = $this->link->prepare($sql);
				$r->execute($attr); 
			} else {
				$r = $this->link->query($sql) OR DIE($this->Core->debug($this->link->errorInfo()).$this->Core->debug(debug_backtrace()));
			}
			$err = $r->errorInfo();
			if($err[1]){
				$this->Core->debug($err);
				$this->Core->debug(debug_backtrace());
			} else {
				$ret = $r->fetch($pdo);
			}
		} catch (PDOException $e){
			echo $e->getMessage();
		}
		if($ret){
			return $ret;
		} else {
			return array();
		}
	}

	function query($sql, $attr = array(), $pdo = PDO::FETCH_ASSOC){
		$ret = array();
		try{
			if($attr){
				$r = $this->link->prepare($sql);
				$r->execute($attr);
			} else {
				$r = $this->link->query($sql) OR DIE($this->Core->debug($this->link->errorInfo()).$this->Core->debug(debug_backtrace()));
			}
			$err = $r->errorInfo();
			if($err[1]){
				$this->Core->debug($err);
				$this->Core->debug(debug_backtrace());
			} else {
				$ret = $r->fetchAll($pdo);
			}
		} catch (PDOException $e){
			echo $e->getMessage();
		}
		return $ret;
	}

	function queryInsert($sql, $attr = array()){
		$id = 0;
		try{
			if($attr){
				$r = $this->link->prepare($sql);
				$r->execute($attr);
				$err = $r->errorInfo();
				if($err[1]){
					$this->Core->debug($err);
					$this->Core->debug(debug_backtrace());
				} else {
					$r->fetch();
					$id = $this->link->lastInsertId();
				}
			} else {
				$r = $this->link->exec($sql);
				if($r){
					$id = $this->link->lastInsertId();
				}
			}
		} catch (PDOException $e){
			echo $e->getMessage();
		}
		return $id;
	}

	function queryExec($sql, $attr = array()){
		$rows = 0;
		try{
			if($attr){
				$r = $this->link->prepare($sql);
				$r->execute($attr);
				$err = $r->errorInfo();
				if($err[1]){
					$this->Core->debug($err);
					$this->Core->debug(debug_backtrace());
				} else {
					$r->fetch();
					$rows = 1;
				}
			} else {
				$rows = $this->link->exec($sql);
			}

		} catch (PDOException $e){
			echo $e->getMessage();
		}
		return $rows;
	}
}

//--------Class Scoll -------//
class Scroll{
	public $limit = 12, $cnt = 20, $pagesInLine = 3, $page;

	function __construct() {
		$this->page = (int)$_REQUEST['page'];
	}

	function showPages($lnk = ''){
		if(!$this->cnt){
			return false;
		}
		/*$data = array();
		$out['iteration'] = ceil($this->cnt / $this->limit);
		if($out['iteration'] == 1){
			return false;
		}
		$first = floor($this->page / $this->pagesInLine) *  $this->pagesInLine;
		if($out['iteration'] <= $this->pagesInLine || $out['iteration'] <= ($first + $this->pagesInLine)){
			$out['end'] = $out['iteration'];
		} else {
			$out['end'] = $first + $this->pagesInLine;
		}
		$out['next'] = $first;
		if(($this->page + 1) > $this->pagesInLine) {
			$out['cur_page'] = $out['next'] - 1;
			$out['active'] = ' active';
		} else {
			$out['page'] = $out['next'];
			$out['cur_page'] = 0;
		}*/
		if($lnk){
			if(strpos($lnk, '?') === false){
				$out['href'] = $lnk.'?page=';
			} else {
				$out['href'] = $lnk.'&page=';
			}
		} else {
			$out['href'] = './?page=';
		}
		if(($this->cnt - $this->limit * ($this->page + 1)) > 0){
			ob_start();
			include CLIENT_PATH.'/data/show_newpages.html';
			return ob_get_clean();
		} else {
			return '';
		}
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
?>