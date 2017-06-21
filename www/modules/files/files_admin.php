<?php
namespace admin{
	class Files extends CContent{
		protected $config = array(
					'admin' => 'files',
					'header' => 'Менеджер файлов',
					'table' => 'files_parents',
					'pref' => 'fp_',
					'download_table' => 'download_stat',
					'reqId' => 'id'
				);

		function __construct($m) {
			parent::__construct(__CLASS__, $m);
			$this->config['files_path'] = '/files/_db_files';
		}

		function adminLinks($item){
			$ret = array();
			if(!$_REQUEST['window']){
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
			}
			return $ret;
		}

		function moveItem(){
			$out = array();
			$out['item'] = $this->getItem(array('id', 'name'), (int)$_REQUEST['what']);
			$arr = $this->getItems(array('id', 'parent_id', 'name'), false, array('priority'));
			foreach ($arr as $k => $v){
				if($k){
					$arr[$v['parent_id']]['children'][$k] = &$arr[$k];
				}
			}
			$arr = array($arr[0]);
			$out['catalogs'] = $this->getArrayTreeOpt($arr, $out['item']['parent_id']);
			ob_start();
			include $this->path.'/data/admin/move_item_files.html';
			return ob_get_clean();
		}

		function moveItem2(){
			$moveTo = (int)$_REQUEST['move_to'];
			$what = (int)$_REQUEST['what'];
			$returnTo = (int)$_REQUEST['return_to'];
			$par = $this->getItem(array('parent_id'), $what);
			if($par['parent_id'] <> $moveTo){
				if($what){
					$ch = $this->checkMove($what, $moveTo);
					if($ch){
						$this->ret['warning'] = $ch;
						return $this->moveItem();
					}
					$itemTo = $this->getItem(array('id', 'level'), $moveTo);
					$item = $this->getItem(array('id', 'alias'), $what);
					$oldName = $this->getPath($item['id']);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET 
						'.$this->config['pref'].'parent_id='.$moveTo.',
						'.$this->config['pref'].'level='.($itemTo['level'] + 1).'
						WHERE '.$this->config['pref'].'id='.$what);
					$newName = $this->getPath($itemTo['id']).'/'.$item['alias'];
					rename(CLIENT_PATH.$this->config['files_path'].$oldName, CLIENT_PATH.$this->config['files_path'].$newName);
				}
			} else {
				$this->ret['warning'] = 'попытка подключить самого к себе!';
			}
			if($returnTo == 1){
				$returnTo = $what; 
			} elseif($returnTo == 2){
				$returnTo = $par['parent_id']; 
			} else {
				$returnTo = $moveTo;
			}
			$this->id = $returnTo;
			return $this->show();
		}

		function checkMove($what, $to){
			$ret = '';
			$item = $this->getItem(array('id', 'name'), $to);
			if($item['name']){
				$arr = $this->getItems(array('id', 'parent_id'));
				$arr1 = array();
				$pId = $arr[$item['id']]['parent_id'];
				while($pId){
					$arr1[] = $arr[$pId];
					$pId = $arr[$pId]['parent_id'];
				}
				foreach($arr1 as $v){
					if($v['id'] == $what){
						$ret .= 'попытка подключить к своей ветке!';
						break;
					}
				}
			} else {
				$ret .= 'не верный указатель';
			}
			return $ret;
		}

		function priorityItemFiles(){
			$this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true))->priorityItem();
			return $this->show();
		}
		
		function priorityItem(){
			return parent::priorityItem(true);
		}

		function getItem($data, $cond){
			$ret = array();
			if($cond){
				$ret = parent::getItem($data, $cond);
			} else {
				$arr = array('id' => 0, 'parent_id' => 0, 'name' => 'Менеджер файлов', 'level' => 0, 'active' => 1);
				foreach ($data as $k){
					if(isset($arr[$k])){
						$ret[$k] = $arr[$k];
					}
				}
			}
			return $ret;
		}

		function getItems($data = array(), $cond = array(), $order = array()){
			if(!$cond){
				$ret = parent::getItems($data, $cond, $order, 'id');
			} else {
				$ret = parent::getItems($data, $cond, $order);
			}
			if(!$cond){
				$arr = array(array('id' => 0, 'parent_id' => 0, 'name' => 'Менеджер файлов', 'level' => 0, 'active' => 1));
				foreach ($ret as $k => $v){
					$arr[$k] = $v;
				}
				$ret = $arr;
			}
			return $ret;
		}

		function show(){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			if((int)$_REQUEST['fId']){
				$fShow = $C->show();
			}
			/*if($_REQUEST['window']){
				//$out = $this->filesChoose();
				$inc = 'show_window';
			} else */{
				$out['header'] = $this->config['header'];
				$out['files_items'] = $C->getItems(array('id', 'file_date', 'file_type', 'real_file', 'hache', 'parent_id', 'active'), array('parent_id' => $this->id), array('priority'), false, false, false, $C->config['items_on_page']);
				if ($_REQUEST['download_stat']){
					$out['download_stat'] = $this->getDownloadStat();
				}
				$out['items'] = $this->verifyItems($this->getItems(array('id', 'parent_id', 'alias', 'level', 'name', 'active'), false, array('priority')));
				
			}
			if($_REQUEST['window']){
				$this->ret['js_after'] .= '<script>
	function OpenerFilePathInsert(id){
		window.opener.InsText(\'\', \'[download]\' + id + \'[/download]\');
		self.close(); 
		return false;
	}
	</script>';
				$inc = 'show_window';
			} else {
				$inc = 'show';
			}
			$out['file_show'] = $fShow;
			ob_start();
			include $this->path.'/data/admin/'.$inc.'.html';
			return ob_get_clean();
		}

		function getDownloadStat(){
			$ret = $this->Db->query('SELECT * FROM '.$this->config['download_table'].((int)$_REQUEST['fId'] ? ' WHERE fid='.(int)$_REQUEST['fId'] : '').' LIMIT 0,50');
			return $ret;
		}

		function verifyItems($items){
			$arr = array();
			try{
				$d = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(CLIENT_PATH.$this->config['files_path'], \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS), \RecursiveIteratorIterator::SELF_FIRST);
				foreach ($d as $v){
					$arr[$v->getFilename()] = 1;
				}
			} catch(\UnexpectedValueException $e){
				if(!file_exists(CLIENT_PATH.$this->config['files_path'])){
					print_r1('Не найдена директория!');
				}
				print_r1($e->getMessage());
			}
			$bool = false;
			foreach($items as $k => $v){
				if($k && !$arr[$v['alias']]){
					$this->delete($k);
					$bool = true;
				}
			}
			if($bool){
				$items = $this->verifyItems($this->getItems(array('id', 'parent_id', 'alias', 'level', 'name')));
			}
			return $items;
		}

		function showItems($items) {
			$ret = '';
			$arr = array();
			$pId = $this->id;
			while($pId){
				$arr[] = $items[$pId];
				$pId = $items[$pId]['parent_id'];
			}
			$arr[] = $items[0];
			$arr = array_reverse($arr);
			$closed = false;
			foreach ($arr as $v){
				$ret .= $this->showTreeRow($v);
			}
			foreach ($items as $k => $v){
				if($k && $v['parent_id'] == $this->id){
					$ret .= $this->showTreeRow($v);
				}
			}
			return $ret;
		}

		function editItem(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(false, (int)$_REQUEST['what']);
			}
			$out['is_download'] = $out['is_download'] ? ' checked' : '';
			$out['is_hide'] = $out['is_hide'] ? ' checked' : '';
			ob_start();
			include $this->path.'/data/admin/edit_item.html';
			return ob_get_clean();
		}

		function saveItem(){
			$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			$name = stripslashes(trim($_REQUEST['name']));
			$alias = stripslashes(trim($_REQUEST['alias']));
			if(!$alias){
				$alias = $name;
			}
			$alias = strtolower(substr($this->ru_en_encode($alias), 0, 20));
			if($name){
				$desc = stripslashes(trim($_REQUEST['description']));
				$isDown = (int)$_REQUEST['is_download'];
				$isHide = (int)$_REQUEST['is_hide'];
				if($item['id']){
					$path = $this->getPath($item['parent_id']);
					if($item['alias'] && $alias != $item['alias'] && is_dir(CLIENT_PATH.$this->config['files_path'].$path.'/'.$item['alias'])){
						rename(CLIENT_PATH.$this->config['files_path'].$path.'/'.$item['alias'], CLIENT_PATH.$this->config['files_path'].$path.'/'.$alias);
					}
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].'name=?,
						'.$this->config['pref'].'alias=?,
						'.$this->config['pref'].'description=?,
						'.$this->config['pref'].'is_download=?,
						'.$this->config['pref'].'is_hide=?
						WHERE '.$this->config['pref'].'id=?',
							array($name, $alias, $desc, $isDown, $isHide, $item['id']));
				} else {
					$p = $this->getItem(array('id', 'level'), $this->id);
					$path = $this->getPath($p['id']);
					if(!is_dir(CLIENT_PATH.$this->config['files_path'].$path.'/'.$alias)){
						if(mkdir(CLIENT_PATH.$this->config['files_path'].$path.'/'.$alias, 0775, true)){
							$r = $this->Db->queryOne('SELECT MAX('.$this->config['pref'].'priority) AS max FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'parent_id=?', array($p['id']));
							$this->Db->queryInsert('INSERT INTO '.$this->config['table'].' SET
								'.$this->config['pref'].'parent_id=?,
								'.$this->config['pref'].'level=?,
								'.$this->config['pref'].'name=?,
								'.$this->config['pref'].'alias=?,
								'.$this->config['pref'].'description=?,
								'.$this->config['pref'].'active=?,
								'.$this->config['pref'].'priority=?,
								'.$this->config['pref'].'is_download=?,
								'.$this->config['pref'].'is_hide=?,
								'.$this->config['pref'].'view=""',
								array((int)$p['id'], (int)$p['level'] + 1, $name, $alias, $desc, 1, ++$r['max'], $isDown, $isHide));
						} else {
							$this->ret['warning'] .= 'Ошибка создания директории '.CLIENT_PATH.$this->config['files_path'].$path.'/'.$alias;
						}
					} else {
						$this->ret['warning'] .= 'Такая директория уже существует';
					}
				}
				$this->ret['warning'] .= 'Директория изменена';
			} else {
				$this->ret['warning'] .= 'Совершенно недопустимо вводить пустые значения!';
				return $this->editItem();
			}
			unset($this->dirArray);
			return $this->show();
		}

		function getPath($id){
			$items = $this->getItems();
			$str = '';
			if($items[$id]['id']){
				$arr = array();
				$arr[] = $items[$id]['alias'];
				$pId = $items[$id]['parent_id'];
				while ($items[$pId]['id']){
					$arr[] = $items[$pId]['alias'];
					$pId = $items[$pId]['parent_id'];
				}
				$arr = array_reverse($arr);
				$str = '/'.implode('/', $arr);
			}
			return $str;
		}

		function showTreeRow($out){
			$out['admin_links'] = $this->adminLinks($out);
			$out['image'] = $out['id'] == $this->id ? '/admin/images/open.gif' : '/admin/images/closed.gif';
			ob_start();
			include $this->path.'/data/admin/show_tree_row.html';
			return ob_get_clean();
		}

		function showHideItemFiles(){
			$this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true))->showHideItem();
			return $this->show();
		}

		function deleteItemFiles(){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			$ret = $C->deleteItem();
			return $ret ? $ret : $this->show();
		}

		function deleteItem2Files(){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			$C->deleteItem2();
			$this->ret['warning'] .= $C->ret['warning'];
			return $this->show();
		}

		function moveItemFiles(){
			return $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true))->moveItem();
		}

		function moveItem2Files(){
			$this->id = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true))->moveItem2();
			return $this->show();
		}

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$this->delete((int)$_REQUEST['what']);
				$this->ret['warning'] .= 'Категория удалена';
				unset($this->dirArray);
			}
			return $this->show();
		}

		function delete($id){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			$it = $C->getItems(array('id'), array('parent_id' => $id));
			foreach ($it as $v){
				$C->delete($v['id']);
			}
			$r = $this->getItems(array('id'), array('parent_id' => $id));
			foreach ($r as $v){
				$this->delete($v['id']);
			}
			$path = $this->getPath($id);
			if(is_dir(CLIENT_PATH.$this->config['files_path'].$path)){
				rmdir(CLIENT_PATH.$this->config['files_path'].$path);
			}
			$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($id));
		}

		function editItemFiles(){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			$C->serv['parent']['id'] = $this->id;
			return $C->editItem();
		}

		function saveItemFiles(){
			$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FilesChildren', \Core::MODULE => 'files', \Core::ADMIN => true));
			$C->serv['parent']['id'] = $this->id;
			if($C->saveItem()){
				$this->ret['warning'] .= $C->ret['warning'];
				return $this->show();
			} else {
				return $C->editItem();
			}
		}
	}
}
?>