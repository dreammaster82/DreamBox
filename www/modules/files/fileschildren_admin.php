<?php
namespace admin{
	class FilesChildren extends CContent{

		public $config = array(
			'pref' => 'fc_',
			'items_on_page' => 20,
			'pages_in_line' => 25,
			'table' => 'files_children'
		);

		public $ret, $serv;

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
			$this->config['files_path'] = '/files/_db_files';
		}
		
		function adminLinks($item){
			$ret = array();
			$addArr = array();
			if((int)$_REQUEST['page']){
				$addArr[] = 'page='.(int)$_REQUEST['page'];
			}
			if($_REQUEST['sort']){
				$addArr[] = 'sort='.$_REQUEST['sort'];
			}
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			if((int)$_REQUEST['window']){
				$ret[] = array('type' => 'button', 'name' => 'relate', 'text' => ' >> ', 'onclick' => 'return OpenerFilePathInsert('.$item['id'].');');
			} else {
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=showHideItemFiles&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=deleteItemFiles&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=moveItemFiles&what='.$item['id'].$add, 'text' => '[move]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=editItemFiles&what='.$item['id'].$add, 'text' => '[edit]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=priorityItemFiles&pr=0&what='.$item['id'].$add, 'text' => '[+]');
				$ret[] = array('type' => 'link', 'link' => '?id='.$item['parent_id'].'&action=priorityItemFiles&pr=1&what='.$item['id'].$add, 'text' => '[-]');
			}
			return $ret;
		}

		function delete($id){
			$id = $id ? $id : (int)$_REQUEST['what'];
			if($id){
				$r = $this->getItem(array('id', 'file_src'), $id);
				if($r['id']){
					if(file_exists(CLIENT_PATH.$this->config['files_path'].$r['file_src'])){
						unlink(CLIENT_PATH.$this->config['files_path'].$r['file_src']);
					}
					$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($r['id']));
					$this->ret['warning'] .= 'Файл удален!';
				}
			}
		}

		function show(){
			$out = $this->getItem(array('name', 'note', 'description'), $this->id);
			ob_start();
			include $this->path.'/data/admin/show_fileschildren.html';
			return ob_get_clean();
		}
		
		function showHideItem(){
			$ret = false;
			if((int)$_REQUEST['what']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET '.$this->config['pref'].'active=IF('.$this->config['pref'].'active=1, 0, 1) WHERE '.$this->config['pref'].'id=?', array((int)$_REQUEST['what']));
				$this->deleteCache($this->class);
				$ret = true;
			}
			return $ret;
		}
		
		function priorityItem($isParent = 1){
			$ret = false;
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
							$ret = true;
						}
					}
				}
			}
			$this->deleteCache($this->class);
			return $ret;
		}

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$this->delete((int)$_REQUEST['what']);
				$this->ret['warning'] .= 'Элемент удален';
			}
		}

		function showItems($out, $parId = 0){
			ob_start();
			include $this->path.'/data/admin/show_items_fileschildren.html';
			return ob_get_clean(); 
		}

		function editItem(){
			$out = $this->getItem(array('id', 'parent_id', 'file_src', 'name', 'description'), (int)$_REQUEST['what']);
			$out['parent_id'] = $out['parent_id'] ? $out['parent_id'] : (int)$_REQUEST['id'];
			$out['parent'] = $this->Core->getClass(array(\Core::CLASS_NAME => 'Files', \Core::MODULE => 'files', \Core::ADMIN => true))->getItem(array('name'), $out['parent_id']);
			if($out['file_src'] && file_exists(CLIENT_PATH.$this->config['files_path'].$out['file_src'])){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$this->config['files_path'].$out['file_src']);
				finfo_close($fr);
				$out['size'] = filesize(CLIENT_PATH.$this->config['files_path'].$out['file_src']);
				$out['type'] = $this->fTypes[$fi];
			}
			// список файлов из temp
			$dir = CLIENT_PATH.'/files/temp/';
			try{
				$d = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::NEW_CURRENT_AND_KEY | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
				foreach ($d as $k => $v){
					if($v->isFile()){
						$out['loaded_files'][]['filename'] = $v->getFilename();
					}
				}
			} catch (\UnexpectedValueException $e){
				$out['load_error'] = 'Директория не найдена!';
			}
			// список файлов из /files/temp/
			ob_start();
			include $this->path.'/data/admin/edit_item_fileschildren.html';
			return ob_get_clean();
		}
		
		function moveItem(){
			$out = array();
			$out = $this->getItem(array('id', 'name', 'parent_id'), (int)$_REQUEST['what']);
			$arr = $this->Core->getClass(array(\Core::CLASS_NAME => 'Files', \Core::MODULE => 'files', \Core::ADMIN => true))->getItems(array('id', 'parent_id', 'name'), false, array('priority'));
			foreach ($arr as $k => $v){
				if($k){
					$arr[$v['parent_id']]['children'][$k] = &$arr[$k];
				}
			}
			$arr = array($arr[0]);
			$out['catalogs'] = $this->getArrayTreeOpt($arr, $out['parent_id']);
			ob_start();
			include $this->path.'/data/admin/move_item_fileschildren.html';
			return ob_get_clean();
		}

		function moveItem2(){
			$item = $this->getItem(array('id', 'parent_id', 'file_src'), (int)$_REQUEST['what']);
			$moveTo = (int)$_REQUEST['move_to'];
			$returnTo = (int)$_REQUEST['return_to'];
			if($item['id']){
				$path = $this->Core->getClass(array(\Core::CLASS_NAME => 'Files', \Core::MODULE => 'files', \Core::ADMIN => true))->getPath($moveTo);
				$arr = explode('/', $item['file_src']);
				$fileSrc = $path.'/'.array_pop($arr);
				rename(CLIENT_PATH.$this->config['files_path'].$item['file_src'], CLIENT_PATH.$this->config['files_path'].$fileSrc);
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'parent_id=?,
					'.$this->config['pref'].'file_src=?
					WHERE '.$this->config['pref'].'id=?', array($moveTo, $fileSrc, $item['id']));
			}
			if($returnTo == 1){
				$returnTo = $item['parent_id']; 
			} else {
				$returnTo = $moveTo;
			}
			return $returnTo;
		}

		function saveItem(){
			$ret = false;
			$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			$desc = stripslashes(trim($_REQUEST['description']));
			$name = stripslashes(trim($_REQUEST['name']));
			$item['parent_id'] = $item['parent_id'] ? $item['parent_id'] : (int)$_REQUEST['id'];
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'description=?
					WHERE '.$this->config['pref'].'id=?',
					array($name, $desc, $item['id']));
			} else {
				$this->Auth = $this->Core->getClass('Auth');
				$r = $this->Db->queryOne('SELECT MAX('.$this->config['pref'].'priority) AS p FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'parent_id=?', array($item['parent_id']));
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' (
					'.$this->config['pref'].'parent_id,
					'.$this->config['pref'].'name,
					'.$this->config['pref'].'description,
					'.$this->config['pref'].'active,
					'.$this->config['pref'].'priority,
					'.$this->config['pref'].'owner,
					'.$this->config['pref'].'view,
					'.$this->config['pref'].'real_file,
					'.$this->config['pref'].'hache) VALUES 
					(?, ?, ?, 1, ?, ?, "", "", "")',
						array($item['parent_id'], $name, $desc, ++$r['p'], $this->Auth->user['login']));
			}
			$ret = true;
			if(!$_REQUEST['reload']){
				$this->ret['warning'] .= 'Элемент изменен!';
			}
			$path = $this->Core->getClass(array(\Core::CLASS_NAME => 'Files', \Core::MODULE => 'files', \Core::ADMIN => true))->getPath($item['parent_id']);
			if(!is_dir(CLIENT_PATH.$this->config['files_path'].$path)){
				mkdir(CLIENT_PATH.$this->config['files_path'].$path, 0775, true);
			}

			if($_FILES['file_src']['size']>0){
				$file = $_FILES['file_src'];	
				$fSize = $file['size'];
				$fDate = date('Y-m-d H:i:s');
				$tmpName = explode('.', $file['name']);
				$ext = $tmpName[count($tmpName)-1];
				$fName = $path.'/'.$item['id'].'_loaded_file.'.$ext;
				$file['name'] = '_'.str_replace(' ','_',$file['name']);
				if (copy($file['tmp_name'], CLIENT_PATH.$this->config['files_path'].$fName)){
					$this->ret['warning'] .= 'Файл загружен!';
					$ret = true;
				} else {
					$this->ret['warning'] .= 'Файл НЕ загружен!';
				}
				$hache = substr(md5($file['name']),0,8);
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'file_src=?,
					'.$this->config['pref'].'file_size=?,
					'.$this->config['pref'].'file_date=?,
					'.$this->config['pref'].'file_type=?,
					'.$this->config['pref'].'file_mime=?,
					'.$this->config['pref'].'real_file=?,
					'.$this->config['pref'].'hache=?
					WHERE '.$this->config['pref'].'id=?',
						array($fName, $fSize, $fDate, $ext, $file['type'], $file['name'], $hache, $item['id']));
			} elseif($_REQUEST['delete_file']){
				if(is_file(CLIENT_PATH.$this->config['files_path'].$item['file_src'])){
					unlink(CLIENT_PATH.$this->config['files_path'].$item['file_src']);
				}
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($item['id']));
				$ret = true;
			} elseif ($_REQUEST['select_file']){
				$dir = CLIENT_PATH.'/files/temp/';
				$selFile = $_REQUEST['select_file'];
				if(is_file($dir.$selFile)){
					$tmpName = explode('.', $select_file);
					$ext = $tmpName[count($tmpName)-1];
					$fName = $path.'/'.$item['id'].'_loaded_file.'.$ext;
					if(copy($dir.$selFile,  CLIENT_PATH.$this->config['files_path'].$fName)){
						$fSize = filesize(CLIENT_PATH.$this->config['files_path'].$fName);
						$fDate = date('d.m.y', filemtime(CLIENT_PATH.$this->config['files_path'].$fName));
						$fi = finfo_open(FILEINFO_MIME_TYPE);
						$fMime = finfo_file($fi, CLIENT_PATH.$this->config['files_path'].$fName);
						$rFile = '_'.str_replace(' ','_',$selFile);
						$hache = substr(md5($rFile),0,8);
						unlink($dir.$selFile);
						$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
							'.$this->config['pref'].'file_src=?,
							'.$this->config['pref'].'file_size=?,
							'.$this->config['pref'].'file_date=?,
							'.$this->config['pref'].'file_type=?,
							'.$this->config['pref'].'file_mime=?,
							'.$this->config['pref'].'real_file=?,
							'.$this->config['pref'].'hache=?
							WHERE '.$this->config['pref'].'id=?',
								array($fName, $fSize, $fDate, $ext, $fMime, $rFile, $hache, $item['id']));
						$ret = true;
					} else {
						$this->ret['warning'] .= 'Файл НЕ загружен!';
					}
				}
			}
			if($_REQUEST['reload']){
				$ret = false;
			}
			if(!$ret){
				$_REQUEST['what'] = $item['id'];
			}
			return $ret;
		}
	}
}
?>