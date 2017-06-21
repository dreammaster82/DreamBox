<?php
namespace core{
	class Memcache{
		private $memcache, $memType = 0;

		function __construct($m = '', $params) {
			list($Core, $pr) = $params;
			$fClass = 'Memcache';
			$sClass = 'Memcache';
			if($pr){
				$fClass = 'Memcached';
			} else {
				$sClass = 'Memcached';
			}
			if(class_exists($fClass)){
				$this->memcache = new $fClass();
				if($fClass == 'Memcached'){
					$this->memType = 1;
				}
			} elseif(class_exists ($sClass)){
				$this->memcache = new $sClass();
				if($sClass == 'Memcached'){
					$this->memType = 1;
				}
			}
			if($this->memcache){
				if(!$this->memcache->addServer($Core->globalConfig['memcache_connect'], 0)){
					$Core->setError('Cache not conected', 'Memcache');
					$this->memcache = false;
				}
				if($this->memcache){
					if((int)$_REQUEST['flush_cache'] && ($Core->globalConfig['debug'] && in_array(getRealIp(), $Core->globalConfig['debug_ip']))){
						$this->memcache->flush();
						if(file_exists(CLIENT_PATH.'/files/memcache.txt')){
							unlink(CLIENT_PATH.'/files/memcache.txt');
						}
					}
					if($this->memType){
						$this->memcache->setOptions([
							\Memcached::OPT_PREFIX_KEY => $Core->globalConfig['site'].'_'
						]);
					} else {
						$this->memcache->setCompressThreshold(2000, 1);
					}
				}
			} else if($Core->globalConfig['memcache_connect'] > -1){
				$Core->setError('No Memcache', 'Memcache');
			}
		}

		function set($ind, $data, $class = 'all', $expire = 0){
			$ret = false;
			if($this->memcache && $data){
				if($this->memType){
					$ret = $this->memcache->set($ind, $data, $expire);
				} else {
					$srz = function_exists('igbinary_serialize') ? 'igbinary_serialize' : 'json_encode';
					$ret = $this->memcache->set($this->Core->globalConfig['site'].'_'.$ind, $srz($data), false, $expire);
				}
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
				if(!is_dir(CLIENT_PATH.'/files')){
					mkdir(CLIENT_PATH.'/files', 0775, true);
				}
				file_put_contents(CLIENT_PATH.'/files/memcache.txt', implode(';', $arr));
			}
			return $ret;
		}

		function get($ind){
			if($this->memcache){
				if($this->memType){
					return $this->memcache->get($ind);
				} else {
					if($js = $this->memcache->get($this->Core->globalConfig['site'].'_'.$ind)){
						if(function_exists('igbinary_unserialize')){
							return igbinary_unserialize($js);
						} else {
							return json_decode($js, true);
						}
					} else {
						return false;
					}
				}
			} else {
				return false;
			}
		}

		function delete($ind, $class = 'all'){
			if($this->memcache){
				if($this->memType){
					$d = $this->memcache->delete($ind);
				} else {
					$d = $this->memcache->delete($this->Core->globalConfig['site'].'_'.$ind);
				}
				if($d && is_file(CLIENT_PATH.'/files/memcache.txt')){
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

		function flush($class = 'all'){
			if($this->memcache){
				if(is_file(CLIENT_PATH.'/files/memcache.txt')){
					$s = file_get_contents(CLIENT_PATH.'/files/memcache.txt');
					$arr = explode(';', $s);
					foreach ($arr as $v){
						if($v){
							list($cl, $param) = explode('_', $v);
							if($cl == $class){
								if($this->memType){
									$this->memcache->delete($param);
								} else {
									$this->memcache->delete($this->Core->globalConfig['site'].'_'.$param);
								}
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
					$this->memcache->flush();
				}
			}
		}
	}
}
?>