<?php
namespace core\admin{
	use core\admin as core;
	class Content extends core\CContent{
		protected $config = array(
			'admin' => 'content',
			'header' => 'Страницы сайта',
			'table' => 'content',
			'pref' => 'co_',
			'reqId' => 'id'
		);

		private $wi;

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			$add = '';
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=moveItem&what='.$item['id'].$add, 'text' => '[move]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
			}
			$ret[] = array('type' => 'link', 'link' => '?id='.$item['id'].'&action=editItem'.$add, 'text' => '[new]');
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=priorityItem&pr=0&what='.$item['id'].$add, 'text' => '[+]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$this->id.'&action=priorityItem&pr=1&what='.$item['id'].$add, 'text' => '[-]');
			}
			return $ret;
		}

		function delete($id){
			$r = $this->getItems(array('id'), array('parent_id' => $id));
			foreach ($r as $v){
				$this->delete($v['id']);
			}
			$path = $this->Util->getrealpath($this->config['files_path'].'/'.$id);
			if(is_dir($path)){
				try{
					$dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
					foreach ($dir as $v){
						if($v->isFile()){
							$this->deleteImage($v->getPath().'/'.$v->getFilename());
						}
					}
					rmdir($path);
				} catch(\UnexpectedValueException $e){
					echo $e->getMessage();
				}
			}
            $this->Db->queryExec('DELETE FROM '.$this->aliasesTable.' WHERE type=? AND type_id=?', array($this->module, $id));
			$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($id));
		}

		function deleteItem2(){
			$this->delete((int)$_REQUEST['what']);
			$this->ret['warning'] = 'Элемент удален';
			$this->deleteCache($this->class);
			return $this->show();
		}

		function editItem(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(false, (int)$_REQUEST['what']);
				if($out['id']){
					$out['path'] = $this->config['files_path'];
					if($out['img_src']){
						$fr = finfo_open(FILEINFO_MIME_TYPE);
						$fi = finfo_file($fr, CLIENT_PATH.$out['path'].$out['img_src']);
						finfo_close($fr);
						$out['size'] = filesize(CLIENT_PATH.$out['path'].$out['img_src']);
						$out['type'] = $this->fTypes[$fi];
					}
					$out['files'] = $this->Util->getImagesArray($this->config['files_path'].'/'.$out['id']);
				}
			}
			$out['module'] = $this->module;
			$out['path'] = $this->config['files_path'];
			$out['parent_id'] = $out['id'] ? $out['parent_id'] : $this->id;
			$out['templates'] = $CFG['templates'];
			//$out['all_types'] = $this->getTypes();
			ob_start();
			include $this->path.'/data/admin/edit_item.html';
			return ob_get_clean();
		}

		function getItem($data, $cond){
			if(!$data){
				$data = array('id', 'parent_id', 'name', 'content', 'img_src', 'title', 'description', 'keywords', 'toprint', 'parent', 'active', 'is_text', 'on_top', 'header');
			}
			$item = parent::getItem($data, $cond);
			if($item){
				$itTypes = $item['types'];
				$item['types'] = array();
				if((int)$itTypes){
					$types = $this->getTypes();
					foreach ($types as $k => $v){
						if($itTypes & $k){
							$item['types'][$k] = $v['name'];
						}
					}
				}
                $q = $this->Db->queryOne('SELECT aliases FROM '.$this->aliasesTable.' WHERE type=? AND type_id=?', array($this->module, $item['id']));
                if($q['aliases']) $item['alias'] = $q['aliases'];
			} else {
				$item = array(
					'id' => 0,
					'parent_id' => 0,
					'name' => $this->config['header'],
					'title' => $this->config['header']
				);
			}
			return $item;
		}

		function getParents($id){
			$ret = array();
			$items = $this->getItems(array('id', 'parent_id', 'name', 'active'));
			$cArr = array();
			$pArr = array();
			foreach ($items as $v){
				$cArr[$v['id']] = $v;
				$pArr[$v['parent_id']] += 1;
			}
			unset($items);
			if($cArr){
				while($parent = $cArr[$id]){
					$parent['cnt_child'] = $pArr[$id];
					$ret[]= $parent;
					$id = $parent['parent_id'];
				}
			}
			array_push($ret, array('id' => 0, 'name' => $this->config['header']));
			return array_reverse($ret);
		}

		function moveItem(){
			$ret = '';
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(false, (int)$_REQUEST['what']);
				$r = $this->getItems(array('id', 'parent_id', 'name'), false, array('parent_id'));
				$cat = array();
				foreach ($r as $v){
					$cat[$v['id']] = $v;
				}
				foreach ($cat as $k => $v){
					$cat[$v['parent_id']]['children'][$k] = &$cat[$k];
				}
				$cat[0]['id'] = 0;
				$cat[0]['parent_id'] = 0;
				$cat[0]['name'] = $this->config['header'];
				$cArr[] = $cat[0];
				$out['move_to'] = $this->getArrayTreeOpt($cArr, $out['parent_id']);
				$out['parent_id'] = $this->id;
				ob_start();
				include $this->path.'/data/admin/move_item.html';
				$ret = ob_get_clean();
			}
			return $ret;
		}

		function saveItem(){
			$item = $this->getItem(false, (int)$_REQUEST['what']);
			$name = trim(stripslashes($_REQUEST['name']));
			$err = array();
			if(!$name){
				$err[] = 'имя - обязательно';
			}
			if(!$err){
				$title = trim(stripslashes($_REQUEST['title']));
				$alias = ltrim(str_replace('/content/', '', $_REQUEST['alias']), '/');
				if(!$alias){
					$alias = $this->ru_en_encode($name);
				}
				$alias = $this->Core->getClass([\Core::CLASS_NAME => 'Admin', \Core::ADMIN => true])->checkAlias($alias, (int)$item['id']);
				$content = str_replace("\n", "\n ", trim(stripslashes($_REQUEST['content'])));
				$desc = trim(stripslashes($_REQUEST['description']));
				$keywords = trim(stripslashes($_REQUEST['keywords']));
				$header = trim(stripslashes($_REQUEST['header']));
				$types = 0;
				if(is_array($_REQUEST['rtype'])){
					foreach ($_REQUEST['rtype'] as $v){
						if((int)$v){
							$types = $types | (int)$v;
						} else {
							$types = 0;
							break;
						}
					}
				}
				if($item['id']){
					$posted = date('Y-m-d H:i:s');
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET 
						'.$this->config['pref'].'name=?,
						'.$this->config['pref'].'content="",
						'.$this->config['pref'].'title=?,
						'.$this->config['pref'].'description=?,
						'.$this->config['pref'].'keywords=?,
						'.$this->config['pref'].'toprint=?,
						'.$this->config['pref'].'is_text=?,
						'.$this->config['pref'].'posted=?,
						'.$this->config['pref'].'on_top=?,
						'.$this->config['pref'].'header=?
						WHERE '.$this->config['pref'].'id=?', 
						array($name, $title, $desc, $keywords, (int)$_REQUEST['toprint'], (int)$_REQUEST['is_text'], $posted, (int)$_REQUEST['on_top'], $header, $item['id']));
					if(!$_REQUEST['reload']){
						$this->ret['warning'] .= 'Элемент изменен';
					}
                    $this->Db->queryExec('UPDATE '.$this->aliasesTable.' SET aliases=? WHERE type=? AND type_id=?', array($alias, $this->module, $item['id']));
				} else {
					$r = $this->Db->queryOne('SELECT MAX('.$this->config['pref'].'priority) AS m FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'parent_id=?',
							array($this->id));
					$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].'
						('.$this->config['pref'].'parent_id,
						'.$this->config['pref'].'name,
						'.$this->config['pref'].'content,
						'.$this->config['pref'].'title,
						'.$this->config['pref'].'description,
						'.$this->config['pref'].'keywords,
						'.$this->config['pref'].'toprint,
						'.$this->config['pref'].'active,
						'.$this->config['pref'].'priority,
						'.$this->config['pref'].'is_text,
						'.$this->config['pref'].'header,
						'.$this->config['pref'].'on_top) VALUES (?, ?, "", ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)',
						array($this->id, $name, $title, $desc, $keywords, (int)$_REQUEST['toprint'], ++$r['m'], (int)$_REQUEST['is_text'], $header, (int)$_REQUEST['on_top']));
					if(!$_REQUEST['reload']){
						$this->ret['warning'] = 'Элемент добавлен ('.$item['id'].')';
					}
                    $this->Db->queryInsert('INSERT INTO '.$this->aliasesTable.' (aliases, type, type_id) VALUES (?, ?, ?)', array($alias, $this->module, $item['id']));
				}

				$this->saveImageArr($this->config['files_path'].'/'.$item['id']);
				$files = $this->Util->getImagesArray($this->config['files_path'].'/'.$item['id']);
				if($files){
					$content = $this->replaceImageFromContent($files, $content, $item['id']);
				}
				if(stripos($content, '[preview]') !== false){
					$content = $this->InsertPreviews($content);
				}
				if(stripos($content, '[SlideShow]') !== false){
					$content = $this->InsertSlide($content);
				}
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'content=? WHERE '.$this->config['pref'].'id=?',
						array($content, $item['id']));
				if($_FILES['header']['size']>0){
					$file = $_FILES['header'];	
					if($this->fTypes[$file['type']]){
						$ext = $this->fTypes[$file['type']];
						$imgSrc = '/'.$item['id'].'/img_src.'.$ext;
						$filePath = $this->config['files_path'].$imgSrc;
						if(!is_dir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'])){
							mkdir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'], 0777, true);
						}
						copy($file['tmp_name'], CLIENT_PATH.$filePath);
						$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'img_src=? WHERE '.$this->config['pref'].'id=?',
							array($imgSrc, $item['id']));
					} else {
						$err[] = 'неизвестный тип файла: '.$file['type'];
					}
				}
				if($_REQUEST['delete_file']){
					$r = $this->getItem(array('img_src'), $item['id']);
					if($r['img_src']){
						$this->deleteImage($this->config['files_path'].$r['img_src']);
						$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'img_src="" WHERE '.$this->config['pref'].'id=?', array($item['id']));
					}
				}
				$this->deleteCache($this->class);
			}
			if($err || $_REQUEST['reload']){
				$this->ret['warning'] = implode(' ', $err);
				$_REQUEST['what'] = $item['id'];
				return $this->editItem();
			} else {
				return $this->show();								
			}
		}

		function show(){
			$this->ret['title'] = $this->ret['keywords'] = $this->ret['description'] = $this->config['header'];
			$out = array();
			ob_start();
			if((int)$_REQUEST['types']){
				//$out['items'] = $this->getTypes();
				$out['header'] = 'Типы страниц';
				include $this->path.'/data/admin/show_types_page.html';
			} else {
				$item = $this->getItem(false, $this->id);
				if($item){
					$out['parents'] = $this->getParents($item['id']);
					$cond = array('parent_id' => $item['id']);
					if((int)$_REQUEST['type']){
						$cond[] = array(array('name' => 'types', 'cond' => '&', 'data' => (int)$_REQUEST['type']));
					}
					$out['sub_cats'] = $this->getItems(array('id', 'parent_id', 'name', 'title', 'active', 'is_text'), $cond, array('priority'));
				}
				$out['header'] = $this->config['header'];
				$out['item'] = $item;
				//$out['types'] = $this->getTypes();
				include $this->path.'/data/admin/show.html';
			}
			return ob_get_clean();
		}

		function showItems($items){
			ob_start();
			$out = array();
			$addArr = array();
			if((int)$_REQUEST['type']){
				$addArr[] = 'type='.(int)$_REQUEST['type'];
			}
			if((int)$_REQUEST['window']){
				$addArr[] = 'window='.(int)$_REQUEST['window'];
			}
			$add = '&'.implode('&', $addArr);
			$i = $this->wi;
			foreach ($items as $v){
				$out['width'] = ($i * 15) + 3;
				$out['admin_links'] = array();
				$out['name'] = $v['name'].' ('.$v['title'].')';
				if(!$_REQUEST['window']){
					$out['admin_links'] = $this->adminLinks($v);
				}
				$out['link'] = '?id='.$v['id'].$add;
				$out['image'] = $v['is_text'] ? '/admin/images/file.gif' : '/admin/images/folder.gif';
				$out['id'] = $v['id'];
				include $this->path.'/data/admin/show_items.html';
			}
			return ob_get_clean();;
		}

		function showParents($items){
			ob_start();
			$i = 0;
			$addArr = array();
			if((int)$_REQUEST['type']){
				$addArr[] = 'type='.(int)$_REQUEST['type'];
			}
			if((int)$_REQUEST['window']){
				$addArr[] = 'window='.(int)$_REQUEST['window'];
			}
			$add = '&'.implode('&', $addArr);
			$out = array();
			foreach ($items as $v){
				$out['width'] = ($i * 15) + 3;
				$out['admin_links'] = array();
				$out['name'] = $v['name'];
				if(!$_REQUEST['window']){
					$out['admin_links'] = $this->adminLinks($v);
				}
				$out['link'] = '?id='.$v['id'].$add;
				++$i;
				include $this->path.'/data/admin/show_parents.html';
			}
			$this->wi = $i;
			return ob_get_clean();
		}
	}
}
?>