<?php
namespace core{
	abstract class CContent{
		abstract protected function parseRequest($request);

		protected $config = array(), $module, $path, $id, $errors = array();

		public $class;

		public $months = array(
			1 => 'января',
			2 => 'февраля',
			3 => 'марта',
			4 => 'апреля',
			5 => 'мая',
			6 => 'июня',
			7 => 'июля',
			8 => 'августа',
			9 => 'сентября',
			10 => 'октября',
			11 => 'ноября',
			12 => 'декабря'
		);

		function __construct($class, $module) {
			$this->module = $module;
			if($class){
				$c = explode('\\', $class);
				$this->class = end($c);
				$this->path = MODULES_PATH.'/'.$this->module;
				$this->config['files_path'] = '/files/'.strtolower($this->class);
				if($_REQUEST['req']){
					$_REQUEST = array_merge($_REQUEST, $this->parseRequest($_REQUEST['req']));
				}
				$this->getId();
			}
		}

		public function process(){
			if($_REQUEST['m'] && is_file($this->path.'/'.$_REQUEST['m'].'.php') && is_file($this->path.'/'.$_REQUEST['m'].'_viewer.php')){
				if(!class_exists($_REQUEST['m'])){
					include $this->path.'/'.$_REQUEST['m'].'.php';
					include $this->path.'/'.$_REQUEST['m'].'_viewer.php';
				}
				$class = ucfirst($_REQUEST['m'].'Viewer');
				$C = new $class();
				if(method_exists($C, $_REQUEST['action'])){
					$ret = $C->$action();
					$this->ret = array_merge($this->ret, $C->ret);
					return $ret;
				} else {
					$this->error();
				}
			} elseif($_REQUEST['m']) {
				$this->error();
			}
			$action = $_REQUEST['action'] ? $_REQUEST['action'] : 'show';
			$ret = '';
			if($action && method_exists($this,$action)){
				$ret = $this->$action();
			}
			return $ret;
		}

		function getId(){
			if(!$this->id){
				$this->id = (int)$_REQUEST[$this->config['reqId']];
			}
			return $this->id;
		}

		function setId($id){
			$this->id = (int)$id;
		}

		function setError($error = ''){
			$this->errors[] = $this->class.'_'.$error;
		}

		function getCountItems($cond = array(), $table = '', $pref = ''){
			$arr = $wCond = array();
			$pref = $pref ? $pref : $this->config['pref'];
			$table = $table ? $table : $this->config['table'];
			if($cond){
				foreach ($cond as $k => $v){
					if(is_array($v) && sizeof($v)){
						$s = sizeof($v);
						if($s > 1){
							$wCond[$k] = '(';
						}
						foreach ($v as $v1){
							if(is_array($v1)){
								if($v1['cond'] == 'IN' && is_array($v1['data'])){
									if(sizeof($v1['data']) == 1){
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].'=?';
									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].' '.$v1['cond'].'('.substr(str_repeat('?,', sizeof($v1['data'])), 0, -1).')';
									}
									$arr = array_merge($arr, array_values($v1['data']));
								} else {
									if(strpos($v1['cond'], $v1['name']) !== false){
										if($v1['is_name']){
											$wCond[$k] .= $v1['cond'];
										} else {
											$wCond[$k] .= str_replace($v1['name'], $pref.$v1['name'], $v1['cond']);
										}

									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].$v1['cond'];
									}
									if(isset($v1['data'])){
										$wCond[$k] .= '?';
										$arr[] = $v1['data'];
									}
								}
							} else {
								if(strtolower($v1) == 'or'){
									$wCond[$k] .= ' OR ';
								} else {
									$wCond[$k] .= ' AND ';
								}
							}
						}
						if($s > 1){
							$wCond[$k] .= ')';
						}
					} else {
						$wCond[$k] = $pref.$k.'=?';
						$arr[] = $v;
					}
				}
			}
			$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$table.
					($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''),
					$arr);
			return $r['cnt'];
		}

		function getItems($data = array(), $cond = array(), $order = array(), $getBy = '', $table = '', $pref = '', $limit = false){
			$arr = $wCond = array();
			$pref = $pref ? $pref : $this->config['pref'];
			$table = $table ? $table : $this->config['table'];
			$b = false;
			if($data){
				foreach ($data as $k => $v){
					$data[$k] = $pref.(is_string($k) ? $k : $v).' AS '.$v;
				}
			} else {
				$b = true;
				$data = array('*');
			}
			if($cond){
				foreach ($cond as $k => $v){
					if(is_array($v) && sizeof($v)){
						$s = sizeof($v);
						if($s > 1){
							$wCond[$k] = '(';
						}
						foreach ($v as $v1){
							if(is_array($v1)){
								if($v1['cond'] == 'IN' && is_array($v1['data'])){
									if(sizeof($v1['data']) == 1){
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].'=?';
									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].' '.$v1['cond'].'('.substr(str_repeat('?,', sizeof($v1['data'])), 0, -1).')';
									}
									$arr = array_merge($arr, array_values($v1['data']));
								} else {
									if(strpos($v1['cond'], $v1['name']) !== false){
										if($v1['is_name']){
											$wCond[$k] .= $v1['cond'];
										} else {
											$wCond[$k] .= str_replace($v1['name'], $pref.$v1['name'], $v1['cond']);
										}

									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].$v1['cond'];
									}
									if(isset($v1['data'])){
										$wCond[$k] .= '?';
										$arr[] = $v1['data'];
									}
								}
							} else {
								if(strtolower($v1) == 'or'){
									$wCond[$k] .= ' OR ';
								} else {
									$wCond[$k] .= ' AND ';
								}
							}
						}
						if($s > 1){
							$wCond[$k] .= ')';
						}
					} else {
						$wCond[$k] = $pref.$k.'=?';
						$arr[] = $v;
					}
				}
			}
			if($order){
				foreach ($order as $k => $v){
					$order[$k] = $pref.$v;
				}
			}
			$r = $this->Db->query('SELECT '.implode(',', $data).' FROM '.$table.
					($wCond ? ' WHERE '.implode(' AND ', $wCond) : '').($order ? ' ORDER BY '.implode(',', $order) : '').
					($limit ? ' LIMIT 0, '.(((int)$_REQUEST['page'] + 1) * $limit) : ''),
				$arr);
			if($getBy){
				$ret = array();
				foreach ($r as $v){
					if($b){
						$r1 = array();
						foreach ($v as $k1 => $v1){
							$r1[str_replace($pref, '', $k1)] = $v1;
						}
						$v = $r1;
					}
					$ret[$v[$getBy]] = $v;
				}
				return $ret;
			} else {
				if($b){
					foreach ($r as $k => $v){
						$r1 = array();
						foreach ($v as $k1 => $v1){
							$r1[str_replace($pref, '', $k1)] = $v1;
						}
						$v = $r1;
						$ret[$k] = $v;
					}
					$r = $ret;
				}
				return $r;
			}
		}

		function getItem($data, $cond, $table = '', $pref = ''){
			$pref = $pref ? $pref : $this->config['pref'];
			$table = $table ? $table : $this->config['table'];
			$b = false;
			if($data){
				foreach ($data as $k => $v){
					$data[$k] = $pref.(is_string($k) ? $k : $v).' AS '.$v;
				}
			} else {
				$b = true;
				$data = array('*');
			}
			if(is_array($cond)){
				foreach ($cond as $k => $v){
					if(is_array($v) && sizeof($v)){
						$s = sizeof($v);
						if($s > 1){
							$wCond[$k] = '(';
						}
						foreach ($v as $v1){
							if(is_array($v1)){
								if($v1['cond'] == 'IN' && is_array($v1['data'])){
									if(sizeof($v1['data']) == 1){
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].'=?';
									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].' '.$v1['cond'].'('.substr(str_repeat('?,', sizeof($v1['data'])), 0, -1).')';
									}
									$arr = array_merge($arr, array_values($v1['data']));
								} else {
									if(strpos($v1['cond'], $v1['name']) !== false){
										if($v1['is_name']){
											$wCond[$k] .= $v1['cond'];
										} else {
											$wCond[$k] .= str_replace($v1['name'], $pref.$v1['name'], $v1['cond']);
										}

									} else {
										$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].$v1['cond'];
									}
									if(isset($v1['data'])){
										$wCond[$k] .= '?';
										$arr[] = $v1['data'];
									}
								}
							} else {
								if(strtolower($v1) == 'or'){
									$wCond[$k] .= ' OR ';
								} else {
									$wCond[$k] .= ' AND ';
								}
							}
						}
						if($s > 1){
							$wCond[$k] .= ')';
						}
					} else {
						$wCond[$k] = $pref.$k.'=?';
						$arr[] = $v;
					}
				}
				$r = $this->Db->queryOne('SELECT '.implode(',', $data).' FROM '.$table.($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''), $arr);
			} else {
				$r = $this->Db->queryOne('SELECT '.implode(',', $data).' FROM '.$table.' WHERE '.$pref.'active=1 AND '.$pref.'id=?', array((int)$cond));
			}
			if($b){
				$r1 = array();
				foreach ($r as $k => $v){
					$r1[str_replace($pref, '', $k)] = $v;
				}
				$r = $r1;
			}
			return $r;
		}

		function showPath($data){
			if(is_array($data)){
				ob_start();
				include CLIENT_PATH.'/data/path.html';
				return ob_get_clean();
			}
		}

		function error(){
			return $this->Util->error($this->errors);
		}
	}
}
?>