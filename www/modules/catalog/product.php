<?php
namespace catalog{
	class Product extends \CContent{
		protected $config = array(
			'table' => 'product',
			'pref' => 'pr_',
			'other_table' => 'product_other',
			'other_pref' => 'po_',
			'links_table' => 'product_link',
			'links_pref' => 'pl_',
			'prod_parametr_links' => 'filter_prod_parametr_links',
			'prod_parametr_links_pref' => 'fppl_',
			'review_table' => 'reviews',
			'fimages_path' => '/files/filters',
			'items_on_page' => 8,
			'find_by_articul' => false,
			'reqId' => 'prodId'
		);

		public $serv = array(), $ret = array();

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
			if($_REQUEST['fl']){
				if(is_array($_REQUEST['fl'])){
					$this->serv['filters'] = $_REQUEST['fl'];
				} else {
					$arr = explode('p', $_REQUEST['fl']);
					$grArr = explode('f', $arr[0]);
					array_shift($grArr);
					foreach ($grArr as $v){
						$arr1 = explode('_', $v);
						$gr = array_shift($arr1);
						while($el = array_shift($arr1)){
							$this->serv['filters']['groups'][$gr][$el] = $el;
						}
					}
					if($arr[1]){
						$arr1 = explode('_', $arr[1]);
						foreach ($arr1 as $k => $v){
							if((float)$v){
								$this->serv['filters']['price'][$k] = (float)$v;
							}
						}
					}
				}
			}
		}

		function cacheForFind(){
			$cond = array();
			$cond['active'] = $this->config['pref'].'active=1';
			$cond['name'] = $this->config['pref'].'name!=""';
			$where = '';
			if($cond){
				$where = ' WHERE '.implode(' AND ', $cond);
			}
			$find = $_REQUEST['find'];
			unset($_REQUEST['find']);
			$r = $this->getItems(array('id', 'ca_id', 'name', 'img_src', 'alias', 'price'), array('active' => 1, array(array('name' => 'name', 'cond' => '!=', 'data' => ''))));
			if($r){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog'));
				$prod = array();
				foreach ($r as $v){
					$prod[$v['id']] = array('id' => $v['id'], 'name' => $v['name'], 'alias' => $v['alias'], 'img_src' => $v['img_src'], 'price' => $v['price']);
					if($C){
						$prod[$v['id']]['c_alias'] = $C->getAlias($v['ca_id']);
					}
				}
				$this->Util->memcacheSet(__FUNCTION__, $prod, $this->class);
				$name = explode(' ', $r[array_rand($r)]['name']);
				$this->Util->memcacheSet('example', mb_substr(mb_convert_case($name[0], MB_CASE_LOWER, 'UTF-8'), 0, 15, 'UTF-8'), $this->class);
			}
		}

		function parseRequest($request) {
			$ret = array();
			if($request){
				$arr = explode('/', $request);
				if(strpos($arr[sizeof($arr) - 1], '.html')){
					$req = array_pop($arr);
					$id = (float)explode('_', $req)[0];
					if($id){
						$ret['prodId'] = $id;
					} else {
						$this->setError('badRequest');
					}
				}
			}
			return $ret;
		}

		function getItem($data, $cond){
			static $item = array();
			if(is_numeric($cond) && $item['id'] != (float)$cond){
				$pref = $pref ? $pref : $this->config['pref'];
				$table = $table ? $table : $this->config['table'];
				$b = false;
				$afterDt = array();
				if($data){
					$noId = true;
					foreach ($data as $k => $v){
						if($v === 'id'){
							$noId = false;
						}
						if($v === 'price' || $k === 'price'){
							$data[$k] = $this->config['other_pref'].(is_string($k) ? $k : $v).' AS '.$v;
							continue;
						}
						if($v === 'params' || $k === 'params'){
							unset($data[$k]);
							$afterDt['params'] = $v;
							continue;
						}
						if($v === 'catalog_link' || $k === 'catalog_link'){
							unset($data[$k]);
							$afterDt['catalog_link'] = $v;
							continue;
						}
						if($v === 'links' || $k === 'links'){
							unset($data[$k]);
							$afterDt['links'] = $v;
							continue;
						}
						$data[$k] = $pref.(is_string($k) ? $k : $v).' AS '.$v;
					}
					if($noId){
						$data['id'] = $pref.'id AS id';
					}
				} else {
					$b = true;
					$data = array('*');
					$afterDt['params'] = 'params';
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
							if($k){
								$wCond[$k] = $pref.$k.'=?';
								$arr[] = $v;
							}
						}
					}
					$item = $this->Db->queryOne('SELECT '.implode(',', $data).' FROM '.$table.
						' INNER JOIN '.$this->config['other_table'].' ON('.$pref.'id='.$this->config['other_pref'].$pref.'id)'.
						($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''), $arr);
				} else {
					$item = $this->Db->queryOne('SELECT '.implode(',', $data).' FROM '.$table.
						' INNER JOIN '.$this->config['other_table'].' ON('.$pref.'id='.$this->config['other_pref'].$pref.'id)'.
						' WHERE '.$pref.'active=1 AND '.$pref.'id=?', array((int)$cond));
				}
				if($b){
					$r1 = array();
					foreach ($item as $k => $v){
						if(strpos($k, $this->config['other_pref']) !== false){
							$r1[str_replace($this->config['other_pref'], '', $k)] = $v;
						} else {
							$r1[str_replace($pref, '', $k)] = $v;
						}
					}
					$item = $r1;
					unset($r1);
				}
				if($item['id']){
					if($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog'))){
						$cArr = $C->getCatalogArray();
						$item['preffix_a'] = $cArr[$item['catalog_id']]['preffix_a'];
						$item['ca_alias'] = $C->getAlias($item['ca_id']);
						if($afterDt['catalog_link']){//Элементы каталогов привязанных к данному
						$l = $C->getLinkedItems($item['ca_id']);
							if($l){
								$l = $this->getItems(array('id', 'ca_id', 'name', 'img_src', 'is_new', 'is_action', 'alias'),
										array(array(array('name' => 'ca_id', 'cond' => 'IN', 'data' => $l))), array('order_count DESC', 'rating DESC'));
								if($l){
									foreach ($l as $v){
										if(!$item['catalog_link'][$v['ca_id']]){
											$item['catalog_link'][$v['ca_id']] = array('name' => $cArr[$v['ca_id']]['name'], 'c_alias' => $C->getAlias($v['ca_id']));
										}
										$item['catalog_link'][$v['ca_id']]['items'][$v['id']] = $v;
									}
									unset($l);
								}
							}
						}
					}
					if($afterDt['params'] && $F = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog'))){
						$item[$afterDt['params']] = $F->getParams($item['id']);
					}
					//Привязка товаров покупаемых с этим
					if($afterDt['links']){
						$r = $this->Db->query('SELECT
							'.$this->config['pref'].'id AS id,
							'.$this->config['pref'].'ca_id AS ca_id,
							'.$this->config['pref'].'name AS name,
							'.$this->config['pref'].'img_src AS img_src,
							'.$this->config['pref'].'alias AS alias,
							'.$this->config['other_pref'].'price AS price,
							'.$this->config['pref'].'sale AS sale,
							'.$this->config['pref'].'articul AS articul
							FROM '.$this->config['links_table'].'
							INNER JOIN '.$this->config['table'].' ON('.$this->config['links_pref'].'id='.$this->config['pref'].'id)
							INNER JOIN '.$this->config['other_table'].' ON('.$this->config['pref'].'id='.$this->config['other_pref'].$this->config['pref'].'id)
							WHERE '.$this->config['links_pref'].$this->config['pref'].'id=? ORDER BY '.$this->config['links_pref'].'count DESC', array($item['id']));
						if($r){
							$C = $this->Core->getClass(array('Catalog', 'catalog'));
							foreach ($r as $v){
								$item['links'][$v['id']] = $v;
								if($C){
									$item['links'][$v['id']]['ca_alias'] = $C->getAlias($v['ca_id']);
								}
							}
						}
					}
				} else {
					$this->setError('Not_Item');
				}
			}
			return $item;
		}

		function getCountItems($cond = array()){
			$arr = $wCond = array();
			$pref = $this->config['pref'];
			$table = $this->config['table'];
			$inner = false;
			$cond['active'] = 1;
			if($cond){
				if($cond['find']){
					mb_regex_encoding('UTF-8');
					$findArr = mb_split('[^А-яA-z0-9]', $cond['find']);
					foreach ($findArr as $k => $v){
						if(!$v){
							unset($findArr[$k]);
						}
					}
					if($findArr){
						unset($cond['ca_id']);
						$fArr = array();
						$fArr[] = array('name' => '(LOWER('.$pref.'name) LIKE LOWER("%'.implode('%")) OR (LOWER('.$pref.'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$fArr[] = 'OR';
						$fArr[] = array('name' => '(LOWER('.$pref.'articul) LIKE LOWER("%'.implode('%")) OR (LOWER('.$pref.'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$cond['find'] = $fArr;
						unset($fArr);
						unset($findArr);
					}
				}
				if($cond['price']){
					$cond['price'] = array(array('name' => $this->config['other_pref'].'price', 'cond' => '=', 'data' => $cond['price'], 'is_name' => true));
				}
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
				' INNER JOIN '.$this->config['other_table'].' ON('.$pref.'id='.$this->config['other_pref'].$pref.'id)'.
				($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''),
				$arr);
			return $r['cnt'];
		}

		function getMaxRating(){
			static $rating = 0, $bool = false;
			if(!$bool){
				$r = Db::queryOne('SELECT MAX('.$this->config['pref'].'rating) AS max FROM '.$this->config['table']);
				$rating = $r['max'];
				$bool = true;
			}
			return $rating;
		}

		function getMinMaxPrices($cond = array()){
			$arr = $wCond = array();
			$pref = $this->config['pref'];
			$table = $this->config['table'];
			$otable = $this->config['other_table'];
			$otPref = $this->config['other_pref'];
			$cond['active'] = 1;
			if($cond){
				if($cond['find']){
					mb_regex_encoding('UTF-8');
					$findArr = mb_split('[^А-яA-z0-9]', $cond['find']);
					foreach ($findArr as $k => $v){
						if(!$v){
							unset($findArr[$k]);
						}
					}
					if($findArr){
						unset($cond['ca_id']);
						$fArr = array();
						$fArr[] = array('name' => '(LOWER('.$this->config['pref'].'name) LIKE LOWER("%'.implode('%")) OR (LOWER('.$this->config['pref'].'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$fArr[] = 'OR';
						$fArr[] = array('name' => '(LOWER('.$this->config['pref'].'articul) LIKE LOWER("%'.implode('%")) OR (LOWER('.$this->config['pref'].'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$cond['find'] = $fArr;
						unset($fArr);
						unset($findArr);
					}
				}
				foreach ($cond as $k => $v){
					if(is_array($v) && sizeof($v)){
						$s = sizeof($v);
						if($s > 1){
							$wCond[$k] = '(';
						}
						foreach ($v as $v1){
							if(is_array($v1)){
								if($v1['cond'] == 'IN' && is_array($v1['data'])){
									$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].' '.$v1['cond'].'('.substr(str_repeat('?,', sizeof($v1['data'])), 0, -1).')';
									$arr = array_merge($arr, array_values($v1['data']));
								} else {
									$wCond[$k] .= ($v1['is_name'] ? '' : $pref).$v1['name'].$v1['cond'];
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
			$r = $this->Db->queryOne('SELECT MIN('.$this->config['other_pref'].'price) AS min, MAX('.$this->config['other_pref'].'price) AS max FROM '.$table.'
					INNER JOIN '.$otable.' ON('.$pref.'id='.$otPref.$pref.'id)'.
					($wCond ? ' WHERE '.implode(' AND ', $wCond) : ''), $arr);
			return array($r['min'], $r['max']);
		}

		function getItems($data = array(), $cond = array(), $order = array(), $limit = 0){
			$arr = $wCond = $ret = array();
			$b = false;
			$pref = $this->config['pref'];
			$table = $this->config['table'];
			if($data){
				foreach ($data as $k => $v){
					if($v === 'ca_name' || $k === 'ca_name'){
						$afterDt['ca_name'] = $v;
						$afterDt['catalog'] = 1;
						unset($data[$k]);
						continue;
					}
					if($v === 'preffix_a' || $k === 'preffix_a'){
						$afterDt['preffix_a'] = $v;
						$afterDt['catalog'] = 1;
						unset($data[$k]);
						continue;
					}
					if($v === 'ca_alias' || $k === 'ca_alias'){
						$afterDt['ca_alias'] = $v;
						$afterDt['catalog'] = 1;
						unset($data[$k]);
						continue;
					}
					if($v === 'price' || $k === 'price'){
						$data[$k] = $this->config['other_pref'].(is_string($k) ? $k : $v).' AS '.$v;
						continue;
					}
					if($v === 'params' || $k === 'params'){
						$afterDt['params'] = $v;
						unset($data[$k]);
						continue;
					}
					$data[$k] = $pref.(is_string($k) ? $k : $v).' AS '.$v;
				}
			} else {
				$b = true;
				$data = array('*');
				$afterDt['params'] = 'params';
				$afterDt['ca_name'] = 'ca_name';
			}
			$cond['active'] = 1;
			if($cond){
				if($cond['find']){
					mb_regex_encoding('UTF-8');
					$findArr = mb_split('[^А-яA-z0-9]', $cond['find']);
					foreach ($findArr as $k => $v){
						if(!$v){
							unset($findArr[$k]);
						}
					}
					if($findArr){
						unset($cond['ca_id']);
						$fArr = array();
						$fArr[] = array('name' => '(LOWER('.$pref.'name) LIKE LOWER("%'.implode('%")) OR (LOWER('.$pref.'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$fArr[] = 'OR';
						$fArr[] = array('name' => '(LOWER('.$pref.'articul) LIKE LOWER("%'.implode('%")) OR (LOWER('.$pref.'name) LIKE LOWER("%', $findArr).'%"))', 'is_name' => true);
						$cond['find'] = $fArr;
						unset($fArr);
						unset($findArr);
					}
				}
				if($cond['price']){
					$cond['price'] = array(array('name' => $this->config['other_pref'].'price', 'cond' => '=', 'data' => $cond['price'], 'is_name' => true));
				}
				if($this->serv['filters'] && $cond['ca_id'] && $F = $this->Core->getClass(array('Filters', 'catalog'))){
					if($this->serv['filters']['groups']){
						if(!$this->serv['filter_cond']){
							$fItems = array();
							foreach ($this->serv['filters']['groups'] as $v){
								foreach ($v as $v1){
									$fItems[$v1] = $v1;
								}
							}
							if($fItems){
								$r = $F->getParametrItems(array($pref.'id', 'fpg_id', 'fpi_id'),
										array('ca_id' => $cond['ca_id'], 'is_filter' => 1, array(array('name' => 'fpi_id', 'cond' => 'IN', 'data' => $fItems))));
								$it = $fi = array();
								if($r){
									foreach ($r as $v){
										$it[$v['pr_id']] = $v['pr_id'];
										$fi[$v['fpg_id']][$v['pr_id']][$v['fpi_id']] = $v['fpi_id'];
									}
									foreach ($this->serv['filters']['groups'] as $k => $v){
										foreach ($it as $k1 => $v1){
											if(!$fi[$k][$k1]){
												unset($it[$k1]);
											}
										}
									}
									if($it){
										$cond['filter'] = $this->serv['filter_cond'] = array(array('name' => 'id', 'cond' => 'IN', 'data' => $it));
									} else {
										$cond['filter'] = $this->serv['filter_cond'] = array(array('name' => 'id', 'cond' => '=', 'data' => 0));
									}
								}
							}
						} else {
							$cond['filter'] = $this->serv['filter_cond'];
						}
					}
					if($this->serv['filters']['price']){
						if($this->serv['filters']['price'][0]){
							$cond['price'][] = array('name' => $this->config['other_pref'].'price', 'cond' => '>=', 'data' => $this->serv['filters']['price'][0], 'is_name' => true);
						}
						if($this->serv['filters']['price'][1]){
							if($cond['price']){
								$cond['price'][] = 'AND';
							}
							$cond['price'][] = array('name' => $this->config['other_pref'].'price', 'cond' => '<=', 'data' => $this->serv['filters']['price'][1], 'is_name' => true);
						}
					}
				}
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
					$o = explode(' ', $v);
					$order[$k] = $pref.$v;
					if($o[0] == 'price'){
						$order[$k] = $this->config['other_pref'].$v;
					}
				}
			}
			$r = $this->Db->query('SELECT '.implode(',', $data).' FROM '.$table.
				' INNER JOIN '.$this->config['other_table'].' ON('.$pref.'id='.$this->config['other_pref'].$pref.'id)'.
				($wCond ? ' WHERE '.implode(' AND ', $wCond) : '').($order ? ' ORDER BY '.implode(',', $order) : '').
				($limit ? ' LIMIT '.((int)$_REQUEST['page'] * $limit).','.$limit : ''),
			$arr);
			$cArr = array();
			$arr = array();
			if($afterDt['catalog'] && $C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog'))){
				$cArr = $C->getCatalogArray();
			}
			foreach ($r as $v){
				$ret[$v['id']] = $v;
				if($cArr){
					if($afterDt['preffix_a']){
						$ret[$v['id']][$afterDt['preffix_a']] = $cArr[$v['ca_id']]['preffix_a'];
					}
					if($afterDt['ca_alias']){
						$ret[$v['id']][$afterDt['ca_alias']] = $C->getAlias($v['ca_id']);
					}
					if($afterDt['ca_name']){
						$ret[$v['id']][$afterDt['ca_name']] = $cArr[$v['ca_id']]['name'];
					}
				}
				$arr[$v['id']] = $v['id'];
			}
			if($arr && $afterDt['params'] && $F = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog'))){
				$r = $F->getParams($arr);
				foreach ($r as $k => $v){
					if($ret[$k]){
						$ret[$k][$afterDt['params']] = $v;
					}
				}
			}
			return $ret;
		}

		function getPath($item){
			$ret = array();
			$ret[] = array('name' => $item['name']);
			if($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog'))){
				$cat = $C->getCatalogArray();
				$pId = $item['ca_id'];
				while($pId){
					$ret[] = array('link' => '/catalog'.$C->getAlias($pId).'/', 'name' => $cat[$pId]['name']);
					$pId = $cat[$pId]['parent_id'];
				}
			}
			$ret[] = array('link' => '/catalog/', 'name' => 'Каталог продукции');
			return array_reverse($ret);
		}

		function getPNProducts(){
			$ret = array();
			if($k = $this->memcacheGet('getPNProducts')){
				$ret = $k;
			} else {
				$tData = array('id', 'ca_id' => 'catalog_id', 'name', 'img_src', 'is_new', 'is_action', 'price', 'sale', 'alias');
				$ret['action_products'] = $this->getItems($tData, array('is_action' => 1, 'active' => 1), array('posted DESC'), 3);
				$ret['new_products'] = $this->getItems($tData, array('is_new' => 1, 'active' => 1), array('posted DESC'), 3);
				$this->memcacheSet(__FUNCTION__, $ret, $this->class);
			}
			return $ret;
		}
	}
}
?>
