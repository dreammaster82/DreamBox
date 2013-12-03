<?php
namespace admin{
	class Catalog extends CContent{

		protected $config = array(
			'admin' => 'catalog',
			'table' => 'catalogs',
			'pref' => 'ca_',
			'link_table' => 'catalog_link',
			'link_pref' => 'cl_',
			'header' => 'Администрирование каталога',
			'reqId' => 'id'
		);

		private $wi = 1;

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
		}

		function editItem(){
			$tData = array('id', 'parent_id', 'co_id', 'name', 'alias', 'title', 'description', 'keywords', 'img_src', 'fpc_id');
			$out = $this->getItem($tData, (int)$_REQUEST['what']);
			if(!$out['id']){
				$out = array(
					'title' => trim($_REQUEST['title']),
					'description' => trim($_REQUEST['description']),
					'keywords' => trim($_REQUEST['keywords']),
					'fpc_id' => 0
				);
			} else {
				$l = $this->getLinkedItems($data['id']);
				if($l){
					$data['link'] = $this->getItems(false, array(array(array('name' => 'id', 'cond' => 'IN', 'data' => $l))));
				}
			}
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Content', \Core::MODULE => 'content'));
			if(is_object($C)){
				$r = $C->getItems(array('id', 'parent_id', 'name'), array('active' => 1), array('parent_id'));
				if($r){
					$cat = array();
					foreach ($r as $v){
						$cat[$v['id']] = $v;
					}
					foreach ($cat as $k => $v){
						$cat[$v['parent_id']]['children'][$k] = &$cat[$k];
					}
					$out['content_options'] = $this->getArrayTreeOpt($cat[0]['children'], $out['co_id']);
				}
			}
			if($C = $this->Core->getClass(array(\Core::CLASS_NAME => 'Filters', \Core::MODULE => 'catalog', \Core::ADMIN => true))){
				$out['param_options'] = $C->getOptions($out['fpc_id']);
			}
			if($out['img_src']){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$this->config['files_path'].$out['img_src']);
				finfo_close($fr);
				$out['image']['size'] = filesize(CLIENT_PATH.$this->config['files_path'].$out['img_src']);
				$out['image']['type'] = $this->fTypes[$fi];
			}
			ob_start();
			include $this->path.'/data/admin/edit_item.html';
			return ob_get_clean();
		}

		function moveItem(){
			$ret = '';
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(array('id', 'name'), (int)$_REQUEST['what']);
				ob_start();
				include $this->path.'/data/admin/move_item.html';
				$ret = ob_get_clean();
			}
			return $ret;
		}

		function saveItem(){
			$name = stripslashes(trim($_REQUEST['name']));
			if(!$name){
				$this->ret['warning'] = 'Введите название';
				return $this->editItem();
			}
			$alias = stripslashes(trim($_REQUEST['alias']));
			if(!$alias){
				$alias = $name;
			}
			$alias = strtolower(substr($this->ru_en_encode($alias), 0, 32));
			$name = html_entity_decode($name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$title = html_entity_decode(stripslashes(trim($_REQUEST['title'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$description = html_entity_decode(stripslashes(trim($_REQUEST['description'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$keywords = html_entity_decode(stripslashes(trim($_REQUEST['keywords'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$coId = (int)$_REQUEST['co_id'];
			$ctgId = (int)$_REQUEST['category_id'];
			$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'co_id=?,
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'alias=?,
					'.$this->config['pref'].'title=?,
					'.$this->config['pref'].'description=?,
					'.$this->config['pref'].'keywords=?,
					'.$this->config['pref'].'fpc_id=?
					WHERE '.$this->config['pref'].'id=?',
					array($coId, $name, $alias, $title, $description, $keywords, $ctgId, $item['id']));
				$this->ret['warning'] = 'Категория изменена';
			} else {
				$r = $this->Db->queryOne('SELECT MAX('.$this->config['pref'].'priority) AS pr FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'parent_id=?', array($this->id));
				$item['id'] = $_REQUEST['what'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].'
				('.$this->config['pref'].'parent_id,
				'.$this->config['pref'].'co_id,
				'.$this->config['pref'].'name,
				'.$this->config['pref'].'priority,
				'.$this->config['pref'].'alias,
				'.$this->config['pref'].'active,
				'.$this->config['pref'].'title,
				'.$this->config['pref'].'description,
				'.$this->config['pref'].'keywords,
				'.$this->config['pref'].'fpc_id)
				VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?)',
				array($this->getId(), $coId, $name, ++$r['pr'], $alias, $title, $description, $keywords, $ctgId));
				$this->ret['warning'] = 'Категория создана';
			}
			//файл предпросмотра
			if($_FILES['header']['size']>0){
				$file = $_FILES['header'];	
				if($this->fTypes[$file['type']]){
					$ext = $this->fTypes[$file['type']];
					$imgSrc = '/'.$item['id'].'/img_src.'.$ext;
					$fPath = $this->config['files_path'].$imgSrc;
					if(!is_dir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'])){
						mkdir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'], 0777, true);
					}
					$r = $this->getItem(array('img_src'), $item['id']);
					if($r['img_src']){
						$this->deleteImage($this->config['files_path'].$r['img_src']);
					}
					copy($file['tmp_name'], CLIENT_PATH.$fPath);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'img_src=? WHERE '.$this->config['pref'].'id=?', array($imgSrc, $item['id']));
				} else {
					$this->ret['warning'] .= 'неизвестный тип файла: '.$file['type'];
					return $this->editItem();
				}
			}
			if($_REQUEST['delete_file']){
				$r = $this->getItem(array('img_src'), $item['id']);
				if($r['img_src']){
					$this->deleteImage($this->config['files_path'].$r['img_src']);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'img_src="" WHERE '.$this->config['pref'].'id=?', array($item['id']));
				}
			}
			if(is_array($_REQUEST['delRel'])){
				foreach ($_REQUEST['delRel'] as $k => $v){
					if((float)$k){
						$this->Db->queryExec('DELETE FROM '.$this->config['link_table'].' WHERE ('.$this->config['link_pref'].$this->config['pref'].'id=? AND '.$this->config['link_pref'].$this->config['pref'].'id_l=?)',
								array($item['id'], (float)$k));
					}
				}
			}
			$this->deleteCache($this->class);
			if(!$_REQUEST['reload']){
				return $this->show();
			} else {
				return $this->editItem();
			}
		}

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$this->delete((int)$_REQUEST['what']);
				$this->ret['warning'] = 'Категория удалена';
			} else {
				$this->ret['warning'] = 'Не указана категория';
			}
			return $this->show();
		}

		function delete($id){
			$r = $this->getItems(array('id'), array('parent_id' => $id));
				if($r){
					foreach ($r as $v){
					$this->delete($v['id']);
				}
			}
			if(is_object($P = $this->Core->getClass(array(\Core::CLASS_NAME => 'Product', \Core::MODULE => 'catalog', \Core::ADMIN => true)))){
				$P->deleteItems($id);
			}
			$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($id));
			$this->deleteCache($this->class);
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			$add = '';
			if((int)$_REQUEST['page']){
				$addArr[] = 'page='.(int)$_REQUEST['page'];
			}
			if((int)$_REQUEST['sort']){
				$addArr[] = 'sort='.$_REQUEST['sort'];
			}
			if($addArr){
				$add .= '&'.implode('&', $addArr);
			}
			$id = $this->getId();
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=moveItem&what='.$item['id'].$add, 'text' => '[move]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
			}
			$ret[] = array('type' => 'link', 'link' => '?id='.$item['id'].'&action=editItem'.$add, 'text' => '[new]');
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=priorityItem&pr=0&what='.$item['id'].$add, 'text' => '[+]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$id.'&action=priorityItem&pr=1&what='.$item['id'].$add, 'text' => '[-]');
			}
			return $ret;
		}

		function show(){
			$out = array();
			if((int)$_REQUEST['window']){
				$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = 'Привязка продуктов';
			} else {
				$this->ret['title'] = $this->ret['description'] = $this->ret['keywords'] = 'Администрирование каталога';
			}
			$out['header'] = $this->config['header'];
			$P = $this->Core->getClass(array(\Core::CLASS_NAME => 'Product', \Core::MODULE => 'catalog', \Core::ADMIN => true));
			if($P){
				if($P->getId()){
					$out['product'] = $P->getItem(false, $P->getId());
					if($out['product'] && !(int)$_REQUEST['window']){
						$out['item'] = $this->getItem(false, $out['product'][$this->config['pref'].'id']);
					}
				}
			}
			if(!$out['item']){
				$out['item'] = $this->getItem(false, $this->id);
			}
			if(!(int)$_REQUEST['window']){
				$out['sort_option'] = array('дате', 'имени');
			} else {
				if($out['product']){
					$out['related_items'] = $P->getRelatedItems($out['product']['id']);
				}
			}
			$out['parents'] = $this->getParents((int)$out['item']['id']);
			$out['items'] = $this->getItems(false, array('parent_id' => (int)$out['item']['id']), array('priority'));

			if((int)$_REQUEST['window'] && (int)$_REQUEST['insCat']){
				if((int)$_REQUEST['rId']){
					$l = $this->getLinkedItems((int)$_REQUEST['rId']);
					if($l){
						$out['link_items'] = $this->getItems(false, array(array(array('name' => 'id', 'cond' => 'IN', 'data' => $l))));
					}
				} else {
					$this->ret['js'] = '<script>
				function insertcat(id, name){
				var opener = window.opener;
				if(id && opener){
					opener.document.getElementById("move_to").value = id;
					opener.document.getElementById("cat_name").value = name;
					window.close();
				}
				}
				</script>';
				}
			}
			ob_start();
			include $this->path.'/data/admin/show.html';
			return ob_get_clean();
		}

		function showItems($items, $isParents = false){
			$out = array();
			$addArr = array();
			$add = '';
			if((int)$_REQUEST['window']){
				$addArr[] = 'window='.(int)$_REQUEST['window'];
			}
			if((int)$_REQUEST['sort']){
				$addArr[] = 'sort='.(int)$_REQUEST['sort'];
			}
			if((int)$_REQUEST['insCat']){
				$addArr[] = 'insCat='.(int)$_REQUEST['insCat'];
			}
			if((int)$_REQUEST['window'] && !(int)$_REQUEST['insCat']){
				$addArr[] = 'prodId='.$_REQUEST['prodId'];
			}
			if((int)$_REQUEST['rId']){
				$addArr[] = 'rId='.$_REQUEST['rId'];
			}
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			ob_start();
			if($isParents){
				$out = array('id' => 0, 'name' => 'Каталог');
				$out['width'] = 0;
				$out['is_parents'] = $isParents;
				$out['admin_links'] = array();
				if(!$_REQUEST['window']){
					$out['admin_links'] = $this->adminLinks($out);
				} elseif((int)$_REQUEST['insCat']){
					if(!(int)$_REQUEST['rId']){
						$out['admin_links'][0] = array('type' => 'button', 'name' => 'ins'.$out['id'], 'text' => '>>',
						'onclick' => 'insertcat('.$out['id'].', \''.$out['name'].'\')');
					} else {
						$out['admin_links'][0] = array('type' => 'text', 'text' => '<i style="display:block;width:53px"></i>');
					}
				}
				$out['add'] = $add;
				include_once $this->path.'/data/admin/show_items.html';
			}
			foreach ($items as $out){
				$out['width'] = ($this->wi * 15) + 3;
				$out['is_parents'] = $isParents;
				$out['admin_links'] = array();
				if(!$_REQUEST['window']){
					$out['admin_links'] = $this->adminLinks($out);
				} elseif((int)$_REQUEST['insCat']){
					if((int)$_REQUEST['rId']){
						if((int)$_REQUEST['rId'] == $out['id']){
							$out['admin_links'][0] = array('type' => 'text', 'text' => '<i style="display:block;width:53px"></i>');
						} else {
							$out['admin_links'][0] = array('type' => 'button', 'name' => 'ins'.$out['id'], 'text' => '>>',
						'onclick' => 'window.location.href=\'?action=linkItem&id='.$this->id.'&what='.$out['id'].$add.'\'');
						}
					} else {
						$out['admin_links'][0] = array('type' => 'button', 'name' => 'ins'.$out['id'], 'text' => '>>',
						'onclick' => 'insertcat('.$out['id'].', \''.$out['name'].'\')');
					}
				}
				$out['add'] = $add;
				include $this->path.'/data/admin/show_items.html';
				if($isParents){
					++$this->wi;
				}
			}
			return ob_get_clean();
		}

		function getLinkedItems($id){
			$r = $this->getItems(array($this->config['pref'].'id_l' => 'linked'), array($this->config['pref'].'id' => $id), false,
					false, $this->config['link_table'], $this->config['link_pref']);
			$ret = array();
			foreach ($r as $v){
				$ret[] = $v['linked'];
			}
			return $ret;
		}

		function getParents($pId){
			$par = array();
			if($pId){
				$r = $this->getItems(array('id', 'parent_id', 'name', 'priority', 'active'));
				$cat = array();
				foreach ($r as $v){
					$cat[$v['id']] = $v;
				}
				while($cat[$pId]){
					$par[]= $cat[$pId];
					$pId =$cat[$pId]['parent_id'];
				}
				$par = array_reverse($par);
			}
			return $par;
		}

		function showLinkedItems($out){
			ob_start();
			include $this->path.'/data/admin/show_linked_items.html';
			return ob_get_clean();
		}

		function linkItem($id = 0, $rId = 0){
			$id = $id ? $id : (int)$_REQUEST['what'];
			$rId = $rId ? $rId : (int)$_REQUEST['rId'];
			if($id && $rId){
				$this->Db->queryExec('INSERT INTO '.$this->config['link_table'].' ('.$this->config['link_pref'].$this->config['pref'].'id, '.$this->config['link_pref'].$this->config['pref'].'id_l)
					VALUES (?,?) ON DUPLICATE KEY UPDATE '.$this->config['link_pref'].$this->config['pref'].'id_l='.$this->config['link_pref'].$this->config['pref'].'id_l', array($rId, $id));
				if(is_array($_REQUEST['delRel'])){
					foreach ($_REQUEST['delRel'] as $k => $v){
						if((float)$k){
							$this->Db->queryExec('DELETE FROM '.$this->config['link_table'].' WHERE ('.$this->config['link_pref'].$this->config['pref'].'id=? AND '.$this->config['link_pref'].$this->config['pref'].'id_l=?)',
									array($rId, (float)$k));
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
			return $this->show();
		}
		
		function getCatalogArray(){
			static $catArr = array();
			if(!$catArr){
				if(!$catArr = $this->Util->memcacheGet(__FUNCTION__)){
					$catArr = $this->getItems(array('id', 'parent_id', 'name', 'alias', 'img_src'), array('active' => 1), array('priority'), 'id');
					if($catArr){
						foreach ($catArr as $k => $v){
							$catArr[$v['parent_id']]['children'][$k] = &$catArr[$k];
						}
						$this->Util->memcacheSet(__FUNCTION__, $catArr, $this->class);
					}
				}
			}
			return $catArr;
		}
		
		function getAlias($id){
			static $alArr = array();
			if(!$alArr[$id]){
				$arr = array();
				$ca = $this->getCatalogArray();
				$arr[] = $ca[$id]['alias'];
				$pId = $ca[$id]['parent_id'];
				while($pId){
					$arr[] = $ca[$pId]['alias'];
					$pId = $ca[$pId]['parent_id'];
				}
				$alArr[$id] = '/'.implode('/', array_reverse($arr));
			}
			return $alArr[$id];
		}
	}
}
?>
