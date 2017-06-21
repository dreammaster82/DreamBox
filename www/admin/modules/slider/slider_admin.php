<?php
namespace core\admin{
	use core\admin as core;
	class Slider extends core\CContent{
		protected $config = array(
			'admin' => 'slider',
			'table' => 'slider',
			'pref' => 'sl_',
			'header' => 'Слайдер на главной',
			'items_on_page' => 20,
			'reqId' => 'id'
		),
		$type = array(
			'Большой на главной',
			'Низ на главной'
		);

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
		}

		function show(){
			$out = array();
			$out['header'] = $this->ret['title'] = $this->ret['keywords'] = $this->ret['description'] = $this->config['header'];
			$out['items'] = $this->getItems(array('id', 'name', 'active', 'type'), false, array('priority'), false, false, false, $this->config['items_on_page']);
			ob_start(); 
			include $this->path.'/data/show.html';
			return ob_get_clean();
		}

		function showItems($out){
			ob_start();
			include $this->path.'/data/show_items.html';
			return ob_get_clean();
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			$add = '&'.implode('&', $addArr);
			if($item['id']){
				$ret[] = array('type' => 'link', 'link' => '?action=showHideItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?action=deleteItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?action=editItem&what='.$item['id'].$add, 'text' => '[edit]');
				$ret[] = array('type' => 'link', 'link' => '?action=priorityItem&pr=0&what='.$item['id'].$add, 'text' => '[+]');
				$ret[] = array('type' => 'link', 'link' => '?action=priorityItem&pr=1&what='.$item['id'].$add, 'text' => '[-]');
			}
			return $ret;
		}

		function editItem(){
			$out = array();
			if((int)$_REQUEST['what']){
				$out['item'] = $this->getItem(array(), (int)$_REQUEST['what']);
			}
			$out['path'] = $this->config['files_path'];
			if($out['item']['img_src']){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$out['path'].$out['item']['img_src']);
				finfo_close($fr);
				$out['image']['size'] = filesize(CLIENT_PATH.$out['path'].$out['item']['img_src']);
				$out['width'] = $out['image']['size'][0] > 500 ? 500 : $out['image']['size'][0];
				$out['image']['type'] = $this->fTypes[$fi];
			}
			ob_start();
			include $this->path.'/data/edit_item.html';
			return ob_get_clean();
		}

		function saveItem(){
			$item = array();
			$name = html_entity_decode(stripslashes(trim($_REQUEST['name'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$alt = html_entity_decode(stripslashes(trim($_REQUEST['alt'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$link = html_entity_decode(stripslashes(trim($_REQUEST['link'])), ENT_COMPAT | ENT_HTML5, 'UTF-8');
			$content = trim(stripslashes($_REQUEST['content']));
			if((int)$_REQUEST['what']){
				$item = $this->getItem(array('id'), (int)$_REQUEST['what']);
			}
			if($item['id']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
					'.$this->config['pref'].'name=?,
					'.$this->config['pref'].'link=?,
					'.$this->config['pref'].'alt=?,
					'.$this->config['pref'].'active=?,
					'.$this->config['pref'].'content=?,
					'.$this->config['pref'].'type=?
					WHERE '.$this->config['pref'].'id=?', array($name, $link, $alt, (int)$_REQUEST['active'], $content, (int)$_REQUEST['slider_id'], $item['id']));
				$this->ret['warning'] .= 'Элемент изменен';
			} else {
				$r = $this->Db->queryOne('SELECT MAX('.$this->config['pref'].'priority) AS m FROM '.$this->config['table']);
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].'
					('.$this->config['pref'].'name,
					'.$this->config['pref'].'link,
					'.$this->config['pref'].'alt,
					'.$this->config['pref'].'active,
					'.$this->config['pref'].'priority,
					'.$this->config['pref'].'content,
					'.$this->config['pref'].'type)
					VALUES (?, ?, ?, ?, ?, ?, ?)', array($name, $link, $alt, (int)$_REQUEST['active'], ++$r['m'], $content, (int)$_REQUEST['slider_id']));
			}
			if($_FILES['img_src']['size']>0){
				$file = $_FILES['img_src'];	
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
			$this->deleteCache($this->class);
			if(!$_REQUEST['reload']){
				return $this->show();
			} else {
				$_REQUEST['what'] = $item['id'];
				return $this->editItem();
			}
		}

		function deleteItem2(){
			if((int)$_REQUEST['what']){
				$item = $this->getItem(array('id', 'img_src'), (int)$_REQUEST['what']);
				if($item['img_src'] && is_file($this->Util->getrealpath($this->config['files_path'].$item['img_src']))){
					$this->deleteImage($this->config['files_path'].$item['img_src']);
				}
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE '.$this->config['pref'].'id=?', array($item['id']));
				$this->ret['warning'] = 'Элемент удален';
				$this->deleteCache($this->class);
			}
			return $this->show();
		}

		function priorityItem(){
			return parent::priorityItem(0);
		}
	}
}
?>
