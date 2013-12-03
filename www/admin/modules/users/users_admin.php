<?php
namespace admin{
	class Users extends CContent{

		public $config = array(
			'admin' => 'users',
			'header' => 'Администраторы',
			'table' => 'admin_user',
			'pref' => 'au_',
			'parents_table' => 'admin_parents',
			'parents_pref' => 'ap_',
			'reqId' => 'id'
		), $catId;
		
		function __construct($m) {
			parent::__construct(__CLASS__, $m);
			$this->catId = (int)$_REQUEST['catId'];
		}
		
		function adminLinks($item){
			$ret = array();
			$addArr = array();
			$addArr[] = 'catId='.$item['ap_id'];
			$add = '';
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?action=moveItem&what='.$item['id'].$add, 'text' => '[move]');
				$ret[] = array('type' => 'link', 'link' => '?action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
			}
			return $ret;
		}
		
		function adminLinksParents($item){
			$ret = array();
			$addArr = array();
			$add = '';
			if($addArr){
				$add = '&'.implode('&', $addArr);
			}
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?catId='.$this->catId.'&action=deleteParent&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?catId='.$this->catId.'&action=editParent&what='.$item['id'].$add, 'text' => '[edit]');
				$ret[] = array('type' => 'link', 'link' => '?catId='.$item['id'].'&action=editParent'.$add, 'text' => '[new]');
			}
			return $ret;
		}

		function showGroups(){
			$out = array();
			if($this->catId){
				$out['parents'] = $this->getParents($this->catId);
			}
			$out['items'] = $this->getItems(array('id', 'name', 'note'), array('parent_id' => $this->catId), false, false, $this->config['parents_table'], $this->config['parents_pref']);
			ob_start();
			include $this->path.'/data/show_groups.html';
			return ob_get_clean();
		}
		
		function show(){
			$this->ret['title'] = $this->ret['keywords'] = $this->ret['description'] = $this->config['header'];
			$out = $this->getItem(array('id', 'login', $this->config['parents_pref'].'id'), $this->catId);
			if($out['id']){
				$out['items'] = $this->getItems(array('id', 'login', $this->config['parents_pref'].'id', 'active'), array($this->config['parents_pref'].'id' => $out['id']));
			}
			ob_start();
			include $this->path.'/data/show.html';
			return ob_get_clean();
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function editItem(){
			$out = $this->getItem(false, (int)$_REQUEST['what']);
			if($out['permis']){
				$out['permission'] = $this->Auth->getPermission($out['permis']);
			}
			if(!$out && $this->Auth->user['permission']['root']){
				$out['permission']['root'] = 1;
			}
			if($out['permission']['root']){
				$out['checking'][0xFFFFFFFF] = array('Суперпользователь', ((!$item && $USER['permission']['is_root']) ? '' : ' checked'));
			}
			foreach($this->Core->cfg['admin_menu'] as $k => $v){
				if(!$v['noviews']){
					$out['checking'][$k] = array($v['text'], ($out['permission'][$v['admin']] ? ' checked' : ''));
				}
			}	
			ob_start();
			include $this->path.'/data/edit_item.html';
			return ob_get_clean();
		}	

		function saveItem(){
			$login = trim(stripslashes($_REQUEST['login']));
			$what = (int)$_REQUEST['what'];
			$pass = trim(stripslashes($_REQUEST['pass']));
			$hPass = md5($pass);
			if(!$login || (!$pass && !$what)){
				$this->ret['warning'] = 'Необходимо указать логин и пароль!';
				return $this->editItem();
			}
			$level = 0;
			foreach ($_REQUEST['checking'] as $k => $v){
				if((int)$v){
					$level = $level | (1 << $k);
				}
			}
			if((int)$_REQUEST['checking'][0xFFFFFFFF]){
				$level = 0xFFFFFFFF;
			}
			if($what){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'login=?,
					'.$this->config['pref'].'permis=CONV("'.dechex($level).'", 16, 10)
					WHERE '.$this->config['pref'].'id=?',
					array($login, $what));
				if($pass){
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].'pass=?
						WHERE '.$this->config['pref'].'id=?',
						array($hPass, $what));
				}
				if($this->Auth->user['id'] == $what){
					$this->Auth->getUserById($what);
					$this->ret['menu'] = $this->showAdminMenu();
				}
			} else {
				$_REQUEST['what'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].'
					('.$this->config['pref'].'login,
					'.$this->config['pref'].'pass,
					'.$this->config['pref'].$this->config['parents_pref'].'id,
					'.$this->config['pref'].'permis,
					'.$this->config['pref'].'active)
					VALUES (?, ?, ?, CONV("'.dechex($level).'", 16, 10), 1)',
					array($login, $hPass, $this->catId));
			}
			$this->ret['warning'] = 'пользователь изменен';
			if($_REQUEST['reload']){
				return $this->editItem(); 
			} else {
				return $this->show();
			}
		}	

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array((int)$_REQUEST['what']));
			}
			return $this->show();			
		}

		function moveItem(){
			$out = $this->getItem(array('login', $this->config['parents_pref'].'id'), (int)$_REQUEST['what']);
			$r = $this->getItems(array('id', 'parent_id', 'name'), false, array('parent_id'), false, $this->config['parents_table'], $this->config['parents_pref']);
			$arr = array();
			foreach ($r as $v){
				$arr[$v['id']] = $v;
			}
			foreach ($arr as $k => $v){
				$arr[$v['parent_id']]['children'][$k] = &$arr[$k];
			}
			$out['parents_options'] = $this->getArrayTreeOpt($arr[0]['children'], $out['ap_id']);
			//$out['parents_options'] = ''.$this->getArrayTreeOpt($arr[0]['children'], $item['ap_id']).'</select>';
			ob_start();
			include $this->path.'/data/move_item.html';
			return ob_get_clean();
		}

		function moveItem2(){
			$to = (int)$_REQUEST['move_to'];
			$what = (int)$_REQUEST['what'];
			$ret = (int)$_REQUEST['return_to'];
			if($this->catId != $to){
				if($what){
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].$this->config['parents_pref'].'id=?
						WHERE '.$this->config['pref'].'id=?',
						array($to, $what));
				}
			} else {
				$this->ret['warning'] = 'попытка подключить к старой группе!';
			}
			if($ret == 2){
				$ret = $this->catId; 
			} else {
				$ret = $to;
			}
			$this->catId = $ret;
			return $this->show();								
		}

		function editParent(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(false, (int)$_REQUEST['what'], $this->config['parents_table'], $this->config['parents_pref']);
				foreach($this->Core->cfg['admin_menu'] as $k => $v){
					if(($r['permission'] >> $k) & 1){
						$out['permission'][$v['admin']] = 1;
					}
				}
				if($r['permission'] == 0xFFFFFFFF){
					$out['permission']['root'] = 1;
				}
			}
			$out['checking'][0xFFFFFFFF] = array('Суперпользователь', $this->Auth->user['permission']['root'] || $out['permission']['root'] ? ' checked' : '');
			foreach($this->Core->cfg['admin_menu']['admin_menu'] as $k => $v){
				if(!$v['noviews']){
					$out['checking'][$k] = array($v['text'], ($this->Auth->user['permission'][$v['admin']] || $out['permission'][$v['admin']] ? ' checked' : ''));
				}
			}	
			ob_start();
			include $this->path.'/data/edit_parent.html';
			return ob_get_clean();
		}	

		function saveParent(){
			$name = trim(stripslashes($_REQUEST['name']));
			$note = trim(stripslashes($_REQUEST['note']));
			$level = 0;
			foreach ($_REQUEST['checking'] as $k => $v){
				if((int)$v){
					$level = $level | (1 << $k);
				}
			}
			if((int)$_REQUEST['checking'][0xFFFFFFFF]){
				$level = 0xFFFFFFFF;
			}
			if((int)$_REQUEST['what']){
				$this->Db->queryExec('UPDATE '.$this->config['parents_table'].' SET
					'.$this->config['parents_pref'].'parent_id=?,
					'.$this->config['parents_pref'].'name=?,
					'.$this->config['parents_pref'].'note=?,
					'.$this->config['parents_pref'].'permission=CONV("'.dechex($level).'", 16, 10)
					WHERE '.$this->config['parents_pref'].'id=?',
					array($this->catId, $name, $note, (int)$_REQUEST['what']));
			} else {
				$_REQUEST['what'] = $this->Db->queryInsert('INSERT INTO '.$this->config['parents_table'].' 
					('.$this->config['parents_pref'].'parent_id,
					'.$this->config['parents_pref'].'name,
					'.$this->config['parents_pref'].'note,
					'.$this->config['parents_pref'].'permission) values(?, ?, ?, CONV("'.dechex($level).'", 16, 10))', array($this->catId, $name, $note));
			}
			$this->ret['warning'] = 'группа изменена';
			if($_REQUEST['reload']){
				return $this->editParent(); 
			} else {
				return $this->show();
			}
		}	

		function deleteParent(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out['what'] = (int)$_REQUEST['what'];
				$out['parent'] = true;
				ob_start();
				include $this->path.'/data/delete_item.html';
				$ret = ob_get_clean();
			} else {
				$ret = $this->show();
			}
			return $ret;
		}

		function deleteParent2(){
			if((int)$_REQUEST['what']){
				$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE '.$this->config['parents_pref'].'id=?', array((int)$_REQUEST['what']));
				if($r['cnt'] > 0){
					$this->ret['warning'] = 'нельзя удалить - в группе есть пользователи';
				}
				$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['parents_table'].' WHERE '.$this->config['parents_pref'].'parent_id=?', array((int)$_REQUEST['what']));
				if($r['cnt'] > 0){
					$this->ret['warning'] = 'нельзя удалить - в группе есть подгруппы';
				}
				if(!$this->ret['warning']){
					$this->delete((int)$_REQUEST['what']);
				}
			}
			return $this->show();
		}	

		function delete($what){
			$this->Db->queryExec('DELETE FROM '.$this->config['parents_table'].' WHERE '.$this->config['parents_pref'].'id=?', array($what));
			$this->ret['warning'] = 'Группа удалена';
		}

		function getParents($id){
			$ret = array();		
			$r = $this->getItems(array('id', 'name', 'note', 'parent_id'), false, false, false, $this->config['parents_table'], $this->config['parents_pref']);
			$arr = array();
			foreach ($r as $v){
				$arr[$v['id']] = $v;
			}
			while($parent = $arr[$id]['parent_id']){
				$ret[]= $arr[$parent];
				$id = $parent['parent_id'];
			}
			return array_reverse($ret);
		}

		function showItem($item){
			return $this->show();
		}
	}
}
?>
