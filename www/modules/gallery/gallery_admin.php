<?php
namespace admin{
	class Gallery extends CContent{
		protected $config = array(
			'admin' => 'gallery',
			'header' => 'Редактирование фото галлереи',
			'table' => 'gallery',
			'items_on_page' => 20,
			'pages_in_line' => 25,
			'max_image_width' => 640,
			'image_quality' => 75,
			'is_cache' => 1,
			'is_main_cache' => 1,
			'reqId' => 'cId'
		), $type, $cnt;


		private $wi = 0;

		public $ret;
		
		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
			$this->type = (int)$_REQUEST['v'];
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			if((int)$_REQUEST['page']){
				$addArr[] = 'page='.(int)$_REQUEST['page'];
			}
			if($this->type){
				$addArr[] = 'v='.$this->type;
			}
			if((int)$_REQUEST['window']){
				$addArr[] = 'window='.(int)$_REQUEST['window'];
			}
			$addArr[] = $this->config['reqId'].'='.$this->id;
			if($addArr){
				$add = '&'.implode('&', $addArr);
			} else {
				$add = '';
			}
			if((int)$_REQUEST['window']){
				if((int)$_REQUEST['insItem']){
					$ret[] = array('type' => 'button', 'name' => 'ins'.$item['id'], 'text' => '>>', 'onclick' => 'insertItem('.$item['id'].',\''.$item['name'].'\')');
				} elseif(!(int)$_REQUEST['preview'] && $item['id']){
					$ret[] = array('type' => 'button', 'name' => 'ins'.$item['id'], 'text' => '>>', 'onclick' => 'window.opener.slideshow_insert(\''.$_REQUEST['idArea'].'\', '.$item['id'].', \''.$item['name'].'\');window.close();');
				}
			} else {
				if($item['id']){
					$ret[] = array('type' => 'link', 'link' => '?action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
					$ret[] = array('type' => 'link', 'link' => '?action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
					$ret[] = array('type' => 'link', 'link' => '?action=moveItem&what='.$item['id'].$add, 'text' => '[move]');
					$ret[] = array('type' => 'link', 'link' => '?action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
				}
				array_pop($addArr);
				$ret[] = array('type' => 'link', 'link' => '?action=editItem&cId='.$item['id'].'&'.implode('&', $addArr), 'text' => '[new]');
				if($item['id']){
					$ret[] = array('type' => 'link', 'link' => '?action=priorityItem&pr=0&what='.$item['id'].$add, 'text' => '[+]');
					$ret[] = array('type' => 'link', 'link' => '?action=priorityItem&pr=1&what='.$item['id'].$add, 'text' => '[-]');
				}
			}
			return $ret;
		}

		function getKeyByLevel($it, $level = 0){
			$ret = array();
			if(is_array($it)){
				foreach ($it as $k => $v){
					$ret[$k] = array($k, $level);
					if($v['children']){
						$itl = $this->getKeyByLevel($v['children'], $level + 1);
						foreach ($itl as $k => $v){
							$ret[$k] = $v;
						}
					}
				}
			}
			return $ret;
		}

		function getParents($id){
			$ret = array();
			$r = $this->getItems(array('id', 'parent_id', 'name', 'active', 'type'), array('type' => $this->type), array('priority'));
			$cat = array();
			foreach ($r as $v){
				$cat[$v['id']] = $v;
			}
			while($id){
				$ret[]= $cat[$id];
				$id = $cat[$id]['parent_id'];
			}
			array_push($ret, array('id' => 0, 'name' => $this->config['header']));
			return array_reverse($ret);
		}

		function showItems($items, $isPar = false){
			$out = array();
			$out['items'] = $items;
			unset($items);
			$out['is_parents'] = $isPar;
			$out['width'] = ($this->wi * 15) + 3;
			if((int)$_REQUEST['insItem']){
				$this->ret['after'] .= '
	<script>
	function insertItem(id, name){
		var opener = window.opener;
		if(opener){
			opener.document.getElementById("move_to").value = id;
			opener.document.getElementById("cat_name").value = name;
			window.close();
		}
	}
	</script>';
			}
			ob_start();
			include $this->path.'/data/admin/show_items_gallery.html';
			return ob_get_clean();
		}

		function moveItem(){
			$ret = '';
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(array('name', 'type'), (int)$_REQUEST['what']);
				ob_start();
				include $this->path.'/data/admin/move_item_gallery.html';
				$ret = ob_get_clean();
			}
			return $ret;
		}

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$this->delete((int)$_REQUEST['what']);
			}
			return $this->show();
		}

		function delete($id){
			$item = $this->getItem(array('id', 'type'), array('type' => $this->type, 'id' => $id));
			if($item['id']){
				$arr = $this->getItems(array('id', 'parent_id', 'name'), array('type' => $item['type']), false, 'id');
				foreach ($arr as $k => $v){
					$arr[$v['parent_id']]['children'][$k] = &$arr[$k];
				}
				$items = $this->getKeyByLevel($arr[$item['id']]['children']);
				$items = $this->Util->SortArray($items, array(1, 'DESC'));
				if($item['type']){
					$C = $this->Core->getClass(['VideoObject', 'gallery', true]);
				} else {
					$C = $this->Core->getClass(['FotoObject', 'gallery', true]);
				}
				foreach ($items as $v){
					if($item['type']){
						$this->Util->clearRecursiveAll($this->config['files_path'].'/video/'.$v[0]);
					} else {
						$this->Util->clearRecursiveAll($this->config['files_path'].'/foto/'.$v[0]);
					}
					$ch = $C->getAllItemsId($v[0]);
					foreach ($ch as $k1 => $v1){
						$C->delete($v1['id']);
					}
					if($v[2]){
						$this->Util->clearRecursiveAll($this->config['files_path'].'/prevfoto/'.$v[0]);
					}
					$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE id=?', array($v[0]));
				}
				if($item['type']){
					$this->clearRecursiveAll($this->config['files_path'].'/video/'.$item['id']);
				} else {
					$this->clearRecursiveAll($this->config['files_path'].'/foto/'.$item['id']);
				}
				$ch = $C->getAllItemsId($item['id']);
				foreach ($ch as $k1 => $v1){
					$C->delete($v1['id']);
				}
				$this->clearRecursiveAll($this->config['files_path'].'/prevfoto/'.$item['id']);
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE id=?', array($item['id']));
				return true;
			} else {
				return false;
			}
		}

		function editItem(){
			$out = $this->getItem(array('id', 'parent_id', 'name', 'alias', 'content', 'siteview', 'img_src'), (int)$_REQUEST['what']);
			$r = $this->getItems(array('id', 'parent_id', 'name'), array('type' => $this->type), array('priority'));
			$cat = array();
			foreach ($r as $v){
				$cat[$v['id']] = $v;
			}
			foreach ($cat as $k => $v){
				$cat[$v['parent_id']]['children'][$k] = &$cat[$k];
			}
			$cat[0]['id'] = 0;
			if($this->type){
				$cat[0]['name'] = 'Видео галлерея';
			} else {
				$cat[0]['name'] = 'Фото галлерея';
			}
			$cArr[] = $cat[0];
			unset($cat);
			unset($r);
			$out['parent_options'] = $this->getArrayTreeOpt($cArr, $out['id'] ? $out['parent_id'] : $this->id);
			if($out['img_src']){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$this->config['files_path'].'/prevfoto'.$out['img_src']);
				finfo_close($fr);
				$out['size'] = filesize(CLIENT_PATH.$this->config['files_path'].'/prevfoto'.$out['img_src']);
				$out['type'] = $this->fTypes[$fi];
			}
			ob_start();
			include $this->path.'/data/admin/edit_item_gallery.html';
			return ob_get_clean();
		}

		function saveItem(){
			$name = stripslashes(trim($_REQUEST['name']));
			if(!$name){
				$this->ret['warning'] .= 'введите название!';
				return $this->editItem();
			}
			$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			$alias = stripslashes(trim($_REQUEST['alias']));
			if(!$alias){
				$alias = $name;
			}
			$alias = $this->checkAlias(strtolower(substr($this->ru_en_encode($alias), 0, 32)), $item['id']);
			$parId = (int)$_REQUEST['parent_id'];
			$content = stripslashes(trim($_REQUEST['content']));
			if(stripos($content, '[preview]') !== false){
				$content = $this->insertPreviews($content);
			}
			if(stripos($content, '[SlideShow]') !== false){
				$content = $this->insertSlide($content);
			}
			$isCont = $content ? 1 : 0;
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					name=?,
					alias=?,
					content=?,
					parent_id=?,
					siteview=?,
					is_content=?
					WHERE id=?',
					array($name, $alias, $content, $parId, (int)$_REQUEST['siteview'], $isCont, $item['id']));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] .= 'Элемент изменен';
				}
			} else {
				$r = $this->Db->queryOne('SELECT MAX(priority) AS m FROM '.$this->config['table'].' WHERE parent_id='.$parId);
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' (
					parent_id,
					name,
					alias,
					content,
					priority,
					siteview,
					is_content,
					type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array($parId, $name, $alias, $content, ++$r['m'], (int)$_REQUEST['siteview'], $isCont, $this->type));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] = 'Элемент добавлен ('.$item['id'].')';
				}
			}
			//файл предпросмотра
			if($_FILES['header']['size']>0){
				$file = $_FILES['header'];	
				if($this->fTypes[$file['type']]){
					$ext = $this->fTypes[$file['type']];
					$imgSrc = '/'.$item['id'].'/img_src.'.$ext;
					$fPath = $this->config['files_path'].'/prevfoto'.$imgSrc;
					if(!is_dir(CLIENT_PATH.$this->config['files_path'].'/prevfoto/'.$item['id'])){
						mkdir(CLIENT_PATH.$this->config['files_path'].'/prevfoto/'.$item['id'], 0775, true);
					}
					$r = $this->getItem(array('img_src'), $item['id']);
					if($r['img_src']){
						$this->deleteImage($this->config['files_path'].'/prevfoto'.$r['img_src']);
					}
					copy($file['tmp_name'], CLIENT_PATH.$fPath);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_src=? WHERE id=?', array($imgSrc, $item['id']));
				} else {
					$this->ret['warning'] .= 'неизвестный тип файла: '.$file['type'];
					return $this->editCat();
				}
			}
			if($_REQUEST['delete_file']){
				$r = $this->getItem(array('img_src'), $item['id']);
				if($r['img_src']){
					$this->deleteImage($this->config['files_path'].'/prevfoto'.$r['img_src']);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_src="" WHERE id='.$item['id']);
				}
			}
			$this->deleteCache($this->class);
			if(!$_REQUEST['reload']){
				$this->id = $parId;
				return $this->show();
			} else {
				$_REQUEST['what'] = $item['id'];
				$_REQUEST['cId'] = $parId;
				return $this->editItem();
			}
		}

		function setId($id){
			if((int)$id){
				$this->id = (int)$id;
			}
		}

		function editObjectItem(){
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$this->ret['warning'] = $C->ret['warning'];
			return $C->editItem();
		}

		function saveObjectItem(){
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$id = $C->saveItem();
			$this->ret['warning'] = $C->ret['warning'];
			if($_REQUEST['reload']){
				$_REQUEST['what'] = $id;
				return $C->editItem();
			} else {
				return $this->show();
			}
		}

		function deleteObjectItem() {
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$ret = $C->deleteItem();
			return $ret ? $ret : $this->show();
		}

		function deleteObjectItem2() {
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$C->deleteItem2();
			$this->ret['warning'] = $C->ret['warning'];
			return $this->show();
		}

		function showHideObjectItem() {
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$C->showHideItem();
			$this->ret['warning'] = $C->ret['warning'];
			return $this->show();
		}

		function moveObjectItem() {
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$ret = $C->moveItem();
			$this->ret['warning'] = $C->ret['warning'];
			return $ret;
		}

		function moveObjectItem2(){
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			$this->id = $C->moveItem2($this->id);
			$this->ret['warning'] = $C->ret['warning'];
			return $this->show();
		}

		function priorityItem(){
			return parent::priorityItem(true);
		}

		function priorityObjectItem(){
			if($this->type){
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			return $this->show();
		}

		function show(){
			$out = array();
			$this->ret['title'] = $this->config['header'];
			if($this->type){
				$out['add'][] = 'v='.$this->type;
				$out['top_name'] = 'Foto';
				$out['top_header'] = 'Видео галлерея';
				$out['top_color'] = 'dd13db';
				$out['class_by_type'] = $this->Core->getClass(array(\Core::CLASS_NAME => 'VideoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			} else {
				$out['top_name'] = 'Video';
				$out['top_header'] = 'Фото галлерея';
				$out['top_color'] = 'dd8513';
				$out['class_by_type'] = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true));
			}
			if((int)$_REQUEST['page']){
				$out['add'][] = 'page='.(int)$_REQUEST['page'];
			}
			if(!(int)$_REQUEST['show_object_items']){
				$out['parents'] = $this->getParents($this->id);
				if($this->id){
					$out['add'][] = $this->config['reqId'].'='.$this->id;
				}
				$out['items'] = $this->getItems(array('id', 'parent_id', 'name', 'active', 'type'), array('type' => $this->type, 'parent_id' => $this->id), array('priority'));
			}
			if((int)$_REQUEST['preview'] || !(int)$_REQUEST['window'] || (int)$_REQUEST['show_object_items']){
				$out['class_items'] = $out['class_by_type']->getItems($this->id);
			}
			$this->ret['style'] .= '<link rel="stylesheet" type="text/css" href="/modules/gallery/css/admin.css">';
			ob_start(); 
			include $this->path.'/data/admin/show_gallery.html';
			return ob_get_clean();
		}	
	}
}
?>