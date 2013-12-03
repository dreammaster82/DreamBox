<?php
namespace admin{
	class Product extends CContent{
		protected $config = array(
			'table' => 'product',
			'pref' => 'pr_',
			'other_table' => 'product_other',
			'other_pref' => 'po_',
			'link_table' => 'product_link',
			'link_pref' => 'pl_',
			'billing_table' => 'billing',
			'bpref' => '',
			'items_on_page' => 20,
			'pages_in_line' => 25,
			'reqId' => 'prodId'
		);

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
			$this->config['files_path'] = '/files/product';
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			$add = '';
			if((int)$_REQUEST['page']){
				$addArr[] = 'page='.(int)$_REQUEST['page'];
			}
			if($_REQUEST['find']){
				$addArr[] = 'find='.$_REQUEST['find'];
			}
			if((int)$_REQUEST['sort']){
				$addArr[] = 'sort='.$_REQUEST['sort'];
			}
			if((int)$_REQUEST['window']){
				$addArr[] = 'window='.$_REQUEST['window'];
			}
			if($addArr){
				$add .= '&'.implode('&', $addArr);
			}
			if((int)$_REQUEST['window']){
				$ret[] = array('type' => 'button', 'name' => 'relate', 'text' => ' >> ', 'onclick' => 'window.location.href=\'?cl=Product&id=3&prodId='.$this->id.'&action=relateItem&what='.$item['id'].$add.'\'');
			} else {
				$ret[] = array('type' => 'link', 'link' => '?cl=Product&id='.$item['ca_id'].'&action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?cl=Product&id='.$item['ca_id'].'&action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?cl=Product&id='.$item['ca_id'].'&action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
			}
			return $ret;
		}

		function deleteItems($catId){
			$r = $this->getItem(array('id'), array('ca_id' => $catId));
			if($r){
				foreach ($r as $v){
					$this->deleteItem2($v['id']);
				}
			}
		}

		function deleteItem2($id = 0){
			$id = $id ? $id : (int)$_REQUEST['what'];
			if($id){
				$files = $this->Util->getImagesArray($this->config['files_admin'].'/'.$id);
				foreach ($files as $k => $v){
					$this->deleteImage($this->config['files_admin'].'/'.$id.'/'.$v);
				}
				if(is_dir($this->config['files_admin'].'/'.$id)){
					rmdir($this->config['files_admin'].'/'.$id);
				}
				$r = $this->Db->queryOne('SELECT ca_id FROM '.$this->config['table'].' WHERE id=?', array($id));
				if($F = $this->Core->getClass(array('Filters', 'catalog', true))){
					$F->clearFilterCache($r['ca_id']);
					$F->saveOptions(false, $id, false);
				}
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($id));
				$this->Db->queryExec('DELETE FROM '.$this->config['other_table'].' WHERE '.$this->config['other_pref'].$this->config['pref'].'id=?', array($id));
				$this->deleteCache($this->class);
			}
		}

		function editItem(){
			$this->Auth = $this->Core->getClass('Auth');
			$out = $this->getItem(false, (int)$_REQUEST['what']);
			if($out['owner_id'] && $out['owner_id'] != $this->Auth->user['id']){
				$this->ret['warning'] .= 'Внимание! Данный каталог принадлежит другому администратору! Деньги не начисляются!';
			}
			$catId = $out['ca_id'] ? $out['ca_id'] : (int)$_REQUEST['id'];
			if($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog', \Core::ADMIN => true))){
				$items = $C->getItems(array('id', 'name', 'parent_id'), false, array('priority'), 'id');
				foreach ($items as $k => $v){
					$items[$v['parent_id']]['children'][$k] = &$items[$k];
				}
				$out['catalogs'] = $this->getArrayTreeOpt(array(0 => array('id' => 0, 'name' => 'Каталог', 'children' => $items[0]['children'])), $catId);
				$out = array_merge($out, $C->getItem(array('fpc_id'), $out['ca_id']));
				unset($items);
			}
			if($out['id']){
				$out['name'] = htmlentities($out['name'], ENT_COMPAT | ENT_HTML5, 'UTF-8');
				if($out['fpc_id'] && $C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog', \Core::ADMIN => true))){
					$out['options'] = $C->showOptions($out['fpc_id'], $out['id']);
				}
				list($year, $month) = explode('-', reset(explode(' ', $out['posted'])));
				$out['posted_path'] = '/'.$year.'/'.$month;
				$out['files'] = $this->Util->getImagesArray($this->config['files_path'].$out['posted_path'].'/'.$out['id']);
				$out['related_items'] = $this->getRelatedItems($out['id']);
			}
			ob_start();
			include $this->path.'/data/admin/edit_item_product.html';
			return ob_get_clean();
		}
		
		function getItem($data, $cond, $table = '', $pref = ''){
			$afterDt = array();
			if($data){
				if(isset($data['price'])){
					$afterDt['price'] = 1;
					unset($data['price']);
				}
			} else {
				$afterDt['price'] = 1;
			}
			$item = parent::getItem($data, $cond, $table, $pref);
			if($item['id'] && $afterDt){
				$item = array_merge($item, parent::getItem(array_keys($afterDt), array('pr_id' => $item['id']), $this->config['other_table'], $this->config['other_pref']));
			}
			return $item;
		}

		function getRelatedItems($id){
			$ret = array();
			$arr = parent::getItems(array('id'), array($this->config['pref'].'id' => $id), false, 'id', $this->config['link_table'], $this->config['link_pref']);
			if($arr){
				$ret = $this->getItems(array('id', 'name', 'img_src'), array(array(array('name' => 'id', 'cond' => 'IN', 'data' => array_keys($arr)))));
			}
			return $ret;
		}

		function getItems($data = array(), $cond = array(), $order = array(), $getBy = '', $limit = 0){
			$arr = $wCond = array();
			$b = false;
			$pref = $this->config['pref'];
			$table = $this->config['table'];
			if($data){
				foreach ($data as $k => $v){
					if($v === 'price' || $k === 'price'){
						$data[$k] = $this->config['other_pref'].(is_string($k) ? $k : $v).' AS '.$v;
						continue;
					}
					$data[$k] = $pref.(is_string($k) ? $k : $v).' AS '.$v;
				}
			} else {
				$b = true;
				$data = array('*');
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
			return $ret;
		}

		function getCountItems($cond = array()){
			if($_REQUEST['find']){
				$find = trim($_REQUEST['find']);
				if(mb_strlen($find, 'UTF-8') > 1){
					mb_internal_encoding('UTF-8');
					mb_regex_encoding('UTF-8');
					$findArr = mb_split('[^А-яA-z0-9]', $_REQUEST['find']);
					foreach($findArr as $k => $v){
						if(!trim($v)){
							unset($findArr[$k]);
						}
					}
					if($findArr){
						$cond['find'] = array(
							array('name' => '', 'cond' => '(LOWER('.$this->config['pref'].'name) like LOWER("%'.implode('%") OR LOWER('.$this->config['pref'].'name) like LOWER("%', $findArr).'%"))', 'is_name' => true),
							'or',
							array('name' => '', 'cond' => $this->config['pref'].'articul LIKE ("%'.htmlspecialchars($_REQUEST['find'], ENT_COMPAT | ENT_HTML401, 'UTF-8').'%")', 'is_name' => true)
						);
					}
				}
			}
			return parent::getCountItems($cond);
		}

		function saveItem(){
			$this->Auth = $this->Core->getClass('Auth');
			$item = $this->getItem(array('id', 'ca_id', 'owner_id', 'posted'), (int)$_REQUEST['what']);
			$name = stripslashes(trim($_REQUEST['name']));
			$caId = (int)$_REQUEST['ca_id'];
			$alias = stripslashes(trim($_REQUEST['alias']));
			if(!$alias){
				$alias = $name;
			}
			$alias = strtolower(substr($this->ru_en_encode($alias), 0, 20));
			$name = html_entity_decode($name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$spec = (int)$_REQUEST['spec'];
			$price = (float)$_REQUEST['price'];
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'ca_id=?,
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'alias=?,
					'.$this->config['pref'].'content="",
					'.$this->config['pref'].'note="",
					'.$this->config['pref'].'articul=?,
					'.$this->config['pref'].'spec=?
					WHERE '.$this->config['pref'].'id=?',
					array($caId, $name, $alias, $item['id'], $spec, $item['id']));
				if(!$item['owner_id']){
					$item['owner_id'] = $this->Auth->user['id'];
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].'owner_id=?
						WHERE '.$this->config['pref'].'id=?',
						array($item['owner_id'], $item['id']));
				}
				/*--------параметры---------*/
				if(is_object($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog', \Core::ADMIN => true)))){
					if($item['ca_id'] != $caId){
						if(is_object($CA = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog', \Core::ADMIN => true)))){
							$cat = $C->getItem(array('fpc_id'), $caId);
							(int)$_REQUEST['fpc_id'] = $cat['fpc_id'];
						}
					}
					$C->saveOptions((int)$_REQUEST['fpc_id'], $item['id'], $caId);
					$C->clearFilterCache($item['ca_id']);
				}
				/*--------------------------*/
			} else {
				$item['owner_id'] = $this->Auth->user['id'];
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].'
					('.$this->config['pref'].'ca_id,
					'.$this->config['pref'].'name,
					'.$this->config['pref'].'alias,
					'.$this->config['pref'].'active,
					'.$this->config['pref'].'owner_id,
					'.$this->config['pref'].'content,
					'.$this->config['pref'].'note,
					'.$this->config['pref'].'articul,
					'.$this->config['pref'].'spec
					) VALUES(?, ?, ?, 1, ?, "", "", "", ?)', 
					array($caId, $name, $alias, $item['owner_id'], $spec));
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'articul=? WHERE '.$this->config['pref'].'id=?',
						array($item['id'], $item['id']));
			}
			//---Save Price---//
			$this->Db->queryExec('INSERT INTO '.$this->config['other_table'].' (po_pr_id, po_price) VALUES (?,?) ON DUPLICATE KEY UPDATE po_price=VALUES(po_price)', array($item['id'], $price));
			//---------------//
			$content = stripslashes(trim($_REQUEST['content']));
			$note = stripslashes(trim($_REQUEST['note']));
			$pPath = '';
			if($item['posted']){
				list($year, $month) = explode('-', reset(explode(' ', $item['posted'])));
				$pPath = '/'.$year.'/'.$month;
			} else {
				$pPath = date('/Y/m');
			}
			$this->saveImageArr($this->config['files_path'].$pPath.'/'.$item['id']);
			$files = $this->Util->getImagesArray($this->config['files_path'].$pPath.'/'.$item['id']);
			$imgSrc = '';
			if($files){
				$content = $this->replaceImageFromContent($files, $content, $item['id']);
				$note = $this->replaceImageFromContent($files, $note, $item['id']);
				$imgSrc = $pPath.'/'.$item['id'].'/'.reset($files);
			}
			$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'img_src=?
					WHERE '.$this->config['pref'].'id=?',
					array($imgSrc, $item['id']));
			if(stripos($content, '[preview]') !== false){
				$content = $this->InsertPreviews($content);
			}
			if(stripos($note, '[preview]') !== false){
				$note = $this->InsertPreviews($note);
			}
			if(stripos($content, '[SlideShow]') !== false){
				$content = $this->InsertSlide($content);
			}
			if(stripos($note, '[SlideShow]') !== false){
				$note = $this->InsertSlide($note);
			}
			$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
				'.$this->config['pref'].'content=?,
				'.$this->config['pref'].'note=?
				WHERE '.$this->config['pref'].'id=?',
				array($content, $note, $item['id']));
			if(is_array($_REQUEST['delRel'])){
				foreach ($_REQUEST['delRel'] as $k => $v){
					if((float)$k){
						$this->Db->queryExec('DELETE FROM '.$this->config['link_table'].'
							WHERE ('.$this->config['link_pref'].$this->config['pref'].'id=? AND '.$this->config['link_pref'].'id=?)',
							array($item['id'], (float)$k));
					}
				}
			}
			/*billing*/
			$arhId = 0;
			if(is_object(is_object($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Catalog', \Core::MODULE => 'catalog', \Core::ADMIN => true))))){
				$r = $C->getItem(array('id'), array(array(array('name' => 'name', 'cond' => 'LOWER(name)="архив"'))));
				$arhId = (int)$r['id'];
			}
			if($caId && $caId != $arhId && $this->Auth->user['id'] == $item['owner_id']){
				$this->Db->queryInsert('INSERT INTO '.$this->config['billing_table'].'
					('.$this->config['billing_pref'].$this->config['pref'].'id, '.$this->config['billing_pref'].'user_id)
					VALUES (?, ?) ON DUPLICATE KEY UPDATE '.$this->config['billing_pref'].'user_id='.$this->config['billing_pref'].'user_id',
					array($item['id'], $item['owner_id']));
			}
			/*------*/
			$this->deleteCache($this->class);
			$this->cacheForFind();
			if($_REQUEST['reload']){
				$_REQUEST['what'] = $item['id'];
				return $this->editItem();
			} else {
				return false;
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
				print_r1($this->Util->memcacheGet('example'));
			}
		}

		function showItems($items){
			$addCond = array();
			$out = array();
			if((int)$_REQUEST['sort']){
				$addCond[] = 'sort='.$_REQUEST['sort'];
			}
			if((int)$_REQUEST['window']){
				$addCond[] = 'window='.(int)$_REQUEST['window'];
			}
			$out['add'] = implode('&', $addCond);
			$out['items'] = $items;
			ob_start();
			include $this->path.'/data/admin/show_items_product.html';
			return ob_get_clean();
		}

		function show(){
			$out['products_count'] = $this->getCountItems(array('ca_id' => (int)$_REQUEST['id']));
			
			$out['products'] = $this->getItems(array('id', 'img_src', 'name', 'ca_id', 'active'), array('ca_id' => (int)$_REQUEST['id']), array(((int)$_REQUEST['sort'] ? 'name' : 'posted'), 'id'));
			ob_start();
			if($out['products']){
				include $this->path.'/data/admin/show_product.html';
			}
			return ob_get_clean();
		}
		
		function showHideItem(){
			parent::showHideItem();
			if(is_object($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog', \Core::ADMIN => true)))){
				$r = $this->Db->queryOne('SELECT ca_id FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array((int)$_REQUEST['what']));
				$F->clearFilterCache($r['ca_id']);
			}
			return false;
		}

		function relateItem($id = 0){
			$id = $id ? $id : (int)$_REQUEST['what'];
			if($id){
				$this->Db->queryExec('INSERT INTO '.$this->config['link_table'].' ('.$this->config['link_pref'].$this->config['pref'].'id, '.$this->config['link_pref'].'id)
					VALUES (?,?) ON DUPLICATE KEY UPDATE '.$this->config['link_pref'].'id='.$this->config['link_pref'].'id', array($this->id, $id));
				if(is_array($_REQUEST['delRel'])){
					foreach ($_REQUEST['delRel'] as $k => $v){
						if((float)$k){
							$this->Db->queryExec('DELETE FROM '.$this->config['link_table'].' WHERE ('.$this->config['link_pref'].$this->config['pref'].'id=? AND '.$this->config['link_pref'].'id=?)',
								array($this->id, (float)$k));
						}
					}
				}
				$this->ret['script'] .= '
					<script>
					if(window.opener){
						window.opener.location.reload();
					}
					</script>';
			}
			return FALSE;
		}

		function showRelatedItems($out){
			ob_start();
			include $this->path.'/data/admin/show_related_items_product.html';
			return ob_get_clean();
		}
	}
}
?>
