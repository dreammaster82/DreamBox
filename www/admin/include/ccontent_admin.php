<?php
namespace admin{
	abstract class CContent extends AdminFunctions{
		protected $config = array(), $class = __CLASS__, $module, $path, $Auth, $months = array(
			'1'=>'января',
			'2'=>'февраля',
			'3'=>'марта',
			'4'=>'апреля',
			'5'=>'мая',
			'6'=>'июня',
			'7'=>'июля',
			'8'=>'августа',
			'9'=>'сентября',
			'10'=>'октября',
			'11'=>'ноября',
			'12'=>'декабря'
		), $gData = array();

		protected $id;

		public $ret = array();

		abstract function show();

		abstract function deleteItem2();

		abstract function editItem();

		abstract function saveItem();

		abstract function showItems($items);

		abstract function adminLinks($item);

		public function __construct($class = '', $m = '') {
			$this->module = $m;
			$this->path = ((int)$_REQUEST['admin'] ? ADMIN_PATH.'/modules' : MODULES_PATH).'/'.$this->module;
			if($class){
				$this->getId();
				$c = explode('\\', $class);
				$this->class = end($c);
				$this->config['files_path'] = '/files/'.strtolower($this->module);
			}
		}

		public function process(){
			$this->Auth = $this->Core->getClass('Auth');
			$this->ret['menu'] = $this->showAdminMenu();
			if(!is_dir(CLIENT_PATH.'/data/log/')){
				mkdir(CLIENT_PATH.'/data/log/', 0775, true);
			}
			if($this->config['admin'] && !$this->Auth->user['permission'][$this->config['admin']]){
				header('location: /admin/');
				exit();
			}
			$this->ret['cont_id'] = strtolower($this->module);
			if($_REQUEST['cl']){
				if(is_object($C = $this->Core->getClass(array(\Core::CLASS_NAME => $_REQUEST['cl'], \Core::MODULE => $this->module, \Core::ADMIN => true)))){
					$action = $_REQUEST['action'] ? $_REQUEST['action'] : 'show';
					if(method_exists($C, $action)){
						error_log(date('d.m.Y H:i').'	'.$this->Auth->user['login'].'	action='.$action.'	'.($C->getId() ? 'id='.$C->id.'	' : '').($_REQUEST['what'] ? 'what='.$_REQUEST['what'].'	' : '').ucfirst($C->class).' operations'."\n", 3, CLIENT_PATH.'/data/log/log.log');
						$ret = $C->$action();
						$this->ret = array_merge($this->ret, $C->ret);
						if(!$ret){
							return $this->show();
						} else {
							$this->ret['cont_id'] = strtolower($C->class);
							return $ret;
						}
					} else {
						$this->ret['warning'] .= 'Метод '.$action.' в классе '.$C->class.' не существует';
						$ret = $C->show();
						$this->ret = array_merge($this->ret, $C->ret);
						return $ret;
					}
				} else {
					$this->ret['warning'] .= 'Класс '.$_REQUEST['cl'].' в модуле '.$this->module.' не существует';
					$_REQUEST['action'] = 'show';
				}
			}
			$action = method_exists($this, $_REQUEST['action']) ? $_REQUEST['action'] : 'show';
			error_log(date('d.m.Y H:i').'	'.$this->Auth->user['login'].'	action='.$action.'	'.($this->getId() ? 'id='.$this->id.'	' : '').($_REQUEST['what'] ? 'what='.$_REQUEST['what'].'	' : '').ucfirst($this->class).' operations'."\n", 3, CLIENT_PATH.'/data/log/log.log');
			return $this->$action();
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

		function showHideItem(){
			if((int)$_REQUEST['what']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'active=IF('.$this->config['pref'].'active=1, 0, 1) WHERE '.$this->config['pref'].'id=?', array((int)$_REQUEST['what']));
				$this->deleteCache($this->class);
			}
			return $this->show();
		}

		function deleteItem(){
			if((int)$_REQUEST['what']){
				$out['what'] = (int)$_REQUEST['what'];
				ob_start();
				include $this->path.'/data'.($_REQUEST['admin'] ? '' : '/admin').'/delete_item'.($_REQUEST['m'] ? '_'.strtolower($_REQUEST['m']) : '').'.html';
				$ret = ob_get_clean();
			} else {
				$ret = $this->show();
			}
			return $ret;
		}

		function priorityItem($isParent = 1){
			if((int)$_REQUEST['what']){
				$item = $this->getItem(array('id', 'parent_id'), (int)$_REQUEST['what']);
				if($item['id']){
					$r = $this->getItems(array('id', 'priority'), ($isParent ? array('parent_id' => $item['parent_id']) : false), array('priority'));
					if($r){
						foreach ($r as $k => $v){
							if($v['id'] == $item['id']){
								$pos = $k;
								$priority = $v['priority'];
							}
						}
						if((int)$_REQUEST['pr']){
							$newPos = $pos - 1;
						} else {
							$newPos = $pos + 1;
						}
						if($newPos >= 0 && $r[$newPos]){
							$new = $r[$newPos];
							$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'priority=? WHERE '.$this->config['pref'].'id=?',
									array($new['priority'], $item['id']));
							$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'priority=? WHERE '.$this->config['pref'].'id=?',
									array($priority, $new['id']));
						}
					}
				}
			}
			$this->deleteCache($this->class);
			return $this->show();
		}

		function moveItem2(){
			$id = $this->getId();
			$moveTo = (int)$_REQUEST['move_to'];
			$what = (int)$_REQUEST['what'];
			$returnTo = (int)$_REQUEST['return_to'];
			if($id != $moveTo){
				if($what){
					if($moveTo == $what){
						$this->ret['warning'] = 'попытка подключить самого к себе!';
						return $this->moveItem();
					} else {
						if(method_exists($this, 'getParents')){
							$parents = $this->getParents($moveTo);
							foreach ($parents as $v){
								if($v['id'] == $what){
									$this->ret['warning'] = 'попытка подключить к своей ветке!';
									return $this->moveItem();
								}
							}
						}
						$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
							'.$this->config['pref'].'parent_id=?
							WHERE '.$this->config['pref'].'id=?',
							array($moveTo, $what));
					}
				}
			} else {
				$this->ret['warning'] = 'попытка подключится к своему родителю!';
			}
			if($returnTo == 1){
				$returnTo = $what; 
			} elseif($returnTo == 2) {
				$returnTo = $id; 
			} else {
				$returnTo = $moveTo;
			}
			$id = $returnTo;
			$this->deleteCache($this->class);
			return $this->show();								
		}

		function checkAlias($alias, $id){
			$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'alias=?', array($alias));
			if($r['cnt']){
				if(!($r['cnt'] == 1 && $id)){
					list($al, $ext) = explode('.', $alias);
					$alias = $this->checkAlias($al.'2.'.$ext, $id);
				}
			}
			return $alias;
		}

		function replaceImageFromContent($files, $content, $id){
			foreach($files as $k => $v){
				if(strpos($content,'['.$k.']')){
					$img = $this->config['files_path'].'/'.$id.'/'.$v;
					if(is_file(CLIENT_PATH.$img)){
						$size = getimagesize(CLIENT_PATH.$img);
						$replaceArray = array('src['.$k.']',$img,'width['.$k.']',$size[0],'height['.$k.']',$size[1]);
						$content = $this->replaceAll($replaceArray,$content);
					}
				}
			}
			return $content;
		}

		function getPages($cnt, $lnk = ''){
			$out = array();
			$out['page'] = (int)$_REQUEST['page'];
			$out['iteration'] = ceil($cnt / $this->config['items_on_page']);
			if($out['iteration'] == 1){
				return $ret;
			}
			$first = floor($out['page'] / $this->config['pages_in_line']) *  $this->config['pages_in_line'];
			if($out['iteration'] <= $this->config['pages_in_line'] || $out['iteration'] <= ($first + $this->config['pages_in_line'])){
				$out['end'] = $out['iteration'];
			} else {
				$out['end'] = $first + $this->config['pages_in_line'];
			}
			$ret = '<div class="pages">';
			$out['i'] = $first;
			$add = array();
			if($_REQUEST['sort']){
				$add[] = '&sort='.$_REQUEST['sort'];
			}
			if($_REQUEST['find']){
				$add[] = '&find='.$_REQUEST['find'];
			}
			if($_REQUEST['no_edit']){
				$add[] = '&no_edit='.$_REQUEST['no_edit'];
			}
			if($lnk){
				$add[] = $lnk;
			}
			$out['add'] = '&'.implode('&', $add);
			ob_start();
			if(is_file($this->path.'/data/admin/get_pages.html')){
				include $this->path.'/data/admin/get_pages.html';
			} else {
				include CLIENT_PATH.'/admin/data/get_pages.html';
			}
			return ob_get_clean();
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
					($limit ? ' LIMIT '.((int)$_REQUEST['page'] * $limit).','.$limit : ''),
				$arr);
			$ret = array();
			if($getBy){
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
				$r = $this->Db->queryOne('SELECT '.implode(',', $data).' FROM '.$table.' WHERE '.$pref.'id=?', array((int)$cond));
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
			$r = $this->Db->queryOne('SELECT COUNT(*) as cnt FROM '.$table.
					($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''),
					$arr);
			return $r['cnt'];
		}
	}
}
?>
