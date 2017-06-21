<?php
namespace core\admin{
	use core\admin as core;
	class Reviews extends core\CContent{
		protected $config = array(
			'admin' => 'reviews',
			'header' => 'Отзывы и вопросы',
			'table' => 'reviews',
			'pref' => 'rev_',
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
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($id));
				$this->ret['warning'] = 'Элемент удален';
				$this->deleteCache($this->class);
			}
			return $this->show();
		}

		function editItem(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out = $this->getItem(false, (int)$_REQUEST['what']);
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
			$email = stripslashes(trim($_REQUEST['email']));
			$text = stripslashes(trim($_REQUEST['text']));
			$ask = stripslashes(trim($_REQUEST['ask']));
			if(!$name && !$text){
				$this->ret['warning'] .= 'Введите хоть что-нибудь!';
				return $this->editItem();
			}
			$name = html_entity_decode($name, ENT_COMPAT | ENT_HTML5, 'UTF-8');
			if((int)$_REQUEST['what']){
				$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			}
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'posted=?,
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'email=?,
					'.$this->config['pref'].'text=?,
					'.$this->config['pref'].'ask=?,
					'.$this->config['pref'].'type=?,
					'.$this->config['pref'].'raiting=?
					WHERE '.$this->config['pref'].'id=?',
					array($date, $name, $email, $text, $ask, (int)$_REQUEST['type'], (int)$_REQUEST['raiting'], $item['id']));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] .= 'Элемент изменен';
				}
			} else {
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' 
					('.$this->config['pref'].'name,
					'.$this->config['pref'].'email,
					'.$this->config['pref'].'text,
					'.$this->config['pref'].'ask,
					'.$this->config['pref'].'type,
					'.$this->config['pref'].'raiting,
					'.$this->config['pref'].'posted)
					VALUES
					(?, ?, ?, ?, ?, ?, ?)',
					array($name, $email, $text, $ask, (int)$_REQUEST['type'], (int)$_REQUEST['raiting'], $date));
				if(!$_REQUEST['reload']){
					$this->ret['warning'] = 'Элемент добавлен ('.$item['id'].')';
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
			$out['items'] = $this->getItems(array('id', 'name', 'email', 'posted', 'type', 'raiting', 'active'), false, array('posted DESC'), false, false, false, $this->config['items_on_page']);
			ob_start(); 
			include $this->path.'/data/admin/show.html';
			return ob_get_clean();
		}
	}
}
?>