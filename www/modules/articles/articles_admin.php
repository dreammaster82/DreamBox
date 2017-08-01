<?php
namespace core\admin{
	use core\admin as core;
	class Articles extends core\CContent{
		protected $config = array(
			'admin' => 'articles',
			'header' => 'Articles',
			'table' => 'articles',
			'pref' => 'ar_',
			'items_on_page' => 20,
			'pages_in_line' => 25,
			'max_image_width' => 640,
			'image_quality' => 75,
			'is_cache' => 1,
			'is_main_cache' => 1,
			'reqId' => 'id'
		);

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
			}
			return $ret;
		}

		function deleteItem2(){
			$id = (int)$_REQUEST['what'];
			if($id){
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
				$this->ret['warning'] = 'Элемент удален';
				$this->deleteCache($this->class);
			}
			return $this->show();
		}

		function editItem(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(array('id', 'name', 'annotation', 'content', 'posted', 'img_src', 'active'), (int)$_REQUEST['what']);
				if($out['id']){
					$out['files'] = $this->Util->getImagesArray($this->config['files_path'].'/'.$out['id']);
					$out['module'] = $this->module;
					$out['path'] = $this->config['files_path'];
					if($out['img_src'] && file_exists(CLIENT_PATH.$out['path'].$out['img_src'])){
						$fr = finfo_open(FILEINFO_MIME_TYPE);
						$fi = finfo_file($fr, CLIENT_PATH.$out['path'].$out['img_src']);
						finfo_close($fr);
						$out['size'] = filesize(CLIENT_PATH.$out['path'].$out['img_src']);
						$out['type'] = $this->fTypes[$fi];
					}
					$q = $this->Db->queryOne('SELECT aliases FROM '.$this->aliasesTable.' WHERE type=? AND type_id=?', array($this->module, $out['id']));
					if($q['aliases']) $out['alias'] = $q['aliases'];
				}
			}
			if(!$out['posted']){
				$out['posted'] = date('Y-m-d');
			} else {
				$out['posted'] = reset(explode(' ', $out['posted']));
			}
			ob_start();
			include $this->path.'/data/admin/edit_item.html';
			return ob_get_clean();
		}

		function saveItem(){
			if(strtotime($_REQUEST['posted'])){
				$date = date('Y-m-d', strtotime($_REQUEST['posted']));
			} else {
				$date = date('Y-m-d');
			}
			$name = stripslashes(trim($_REQUEST['name']));
			$annotation = stripslashes(trim($_REQUEST['annotation']));
			$content = stripslashes(trim($_REQUEST['textarea']));
			if(!$name && !$annotation && !$content){
				$this->ret['warning'] .= 'Введите хоть что-нибудь!';
				return $this->editItem();
			}
			$alias = stripslashes(trim($_REQUEST['alias']));
			if(!$alias){
				$alias = $this->ru_en_encode($name);
			}
			$alias = strtolower($alias);
			$name = html_entity_decode($name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
			if((int)$_REQUEST['what']){
				$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			}
			if($item['id']){
			    $this->Db->queryExec('UPDATE '.$this->aliasesTable.' SET aliases=? WHERE type=? AND type_id=?', array($alias, $this->module, $item['id']));
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'posted=?,
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'annotation=?,
					'.$this->config['pref'].'content=""
					WHERE '.$this->config['pref'].'id=?',
					array($date, $name, $annotation, $item['id']));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] .= 'Элемент изменен';
				}
			} else {
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' 
					('.$this->config['pref'].'name,
					'.$this->config['pref'].'annotation,
					'.$this->config['pref'].'content,
					'.$this->config['pref'].'posted)
					VALUES
					(?, ?, "", ?)',
					array($name, $annotation, $date));

                $this->Db->queryInsert('INSERT INTO '.$this->aliasesTable.' (aliases, type, type_id) VALUES (?, ?, ?)', array($alias, $this->module, $item['id']));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] = 'Элемент добавлен ('.$item['id'].')';
				}
			}
			$this->saveImageArr($this->config['files_path'].'/'.$item['id']);
			$files = $this->Util->getImagesArray($this->config['files_path'].'/'.$item['id']);
			if($files){
				$content = $this->replaceImageFromContent($files, $content, $item['id']);
			}
			if(stripos($content, '[preview]') !== false){
				$content = $this->insertPreviews($content);
			}
			if(stripos($content, '[SlideShow]') !== false){
				$content = $this->insertSlide($content);
			}
			$this->Db->queryExec('UPDATE '.$this->config['table'].' SET 
				'.$this->config['pref'].'content=? 
				WHERE '.$this->config['pref'].'id=?', array($content, $item['id']));
			//файл предпросмотра
			if($_FILES['header']['size'] > 0){
				$file = $_FILES['header'];	
				if($this->fTypes[$file['type']]){
					$ext = $this->fTypes[$file['type']];
					$imgSrc = '/'.$item['id'].'/img_src.'.$ext;
					$fPath = $this->config['files_path'].$imgSrc;
					if(!is_dir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'])){
						mkdir(CLIENT_PATH.$this->config['files_path'].'/'.$item['id'], 0777, true);
					}
					$r = $this->Db->queryOne('SELECT '.$this->config['pref'].'img_src AS img_src FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($item['id']));
					if($r['img_src']){
						$this->deleteImage($this->config['files_path'].$r['img_src']);
					}
					copy($file['tmp_name'], CLIENT_PATH.$fPath);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].'img_src=?
						WHERE '.$this->config['pref'].'id=?', array($imgSrc, $item['id']));
				} else {
					$this->ret['warning'] .= 'неизвестный тип файла: '.$file['type'];
					return $this->editItem();
				}
			}
			if($_REQUEST['delete_file']){
				$r = $this->Db->queryOne('SELECT '.$this->config['pref'].'img_src AS img_src FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($item['id']));
				if($r['img_src']){
					$this->deleteImage($this->config['files_path'].$r['img_src']);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'img_src="" WHERE '.$this->config['pref'].'id=?', array($item['id']));
				}
			}
			$this->deleteCache($this->class);
			if(!$_REQUEST['reload']){
				return $this->show();
			} else {
				$_REQUEST['what'] = $item['id'];
				return $this->editItem();
			}
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/admin/show_items.html';
			return ob_get_clean();
		}

		function show(){
			$out = array();
			$out['header'] = $this->ret['title'] = $this->ret['keywords'] = $this->ret['description'] = $this->config['header'];
			$out['items'] = $this->getItems(array('id', 'name', 'annotation', 'posted', 'img_src', 'active'), false, array('posted DESC'), false, false, false, $this->config['items_on_page']);
			ob_start(); 
			include $this->path.'/data/admin/show.html';
			return ob_get_clean();
		}
	}
}
?>