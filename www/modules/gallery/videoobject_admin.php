<?php
class VideoObject extends AdminFunctions implements FotoVideoObject{
	protected $config = array(
		'table' => 'gallery_video',
		'parent_table' => 'gallery',
		'items_on_page' => 20,
		'pages_in_line' => 25,
		'images_path' => '/files/gallery/video',
		'img_width' => '470',
		'img_height' => '307',
		'wopenX3_offSET' => '50',
		'wopenY3_offSET' => '80'
	), $id,
	$file_types = array(
		'image/gif'=>'gif',
		'image/jpeg'=>'jpg',
		'image/pjpeg'=>'jpg',
		'image/png'=>'png'
	);
	private $iCnt;
	public $catId;
	
	function __construct() {
		$this->id = (int)$_REQUEST['id'];
		$this->catId = (int)$_REQUEST['catId'];
	}
	
	function _getItem($id){
		return Db::queryOne('SELECT * FROM '.$this->config['table'].' WHERE id='.$id);
	}
	
	function editItem() {
		if($this->id){
			$item = $this->_getItem($this->id);
		}
		if($item['img_src']){
			$size = getimagesize(CLIENT_PATH.$this->config['images_path'].$item['img_src']);
			$info = '<a href="'.$this->config['images_path'].$item['img_src'].'" target="_blank">'.$item['img_src'].'</a>, размер: '.$size[0].' x '.$size[1].'<br>';
			$del = '<input type="checkbox" name="delete_file" value="1" />удалить';
		}
		$cat = new Gallery();
		$cItem = $cat->_getCatItem($this->catId);
		if(ob_start()){
			include'include/edit_video_item.html';
			$ret = ob_get_clean();
		}
		return $ret;
	}
	
	function editItem2() {
		if($this->id){
			$item = $this->_getItem($this->id);
		}
		$name = trim(stripslashes($_REQUEST['name']));
		$parent_id = $item['parent_id'] ? $item['parent_id'] : $this->catId;
		$link = trim(stripslashes($_REQUEST['link']));
		$content = trim(stripslashes($_REQUEST['content']));
		if($this->id && !$_REQUEST['is_copy']){
			Db::queryExec('UPDATE '.$this->config['table'].' SET name=?, link=?, content=? WHERE id=?', array($name, $link, $content, $this->id));
		} else {
			$r = Db::queryOne('SELECT MAX(priority) AS m FROM '.$this->config['table'].' WHERE parent_id='.$parent_id);
			$pr = $r['m'] + 1;
			$this->id = Db::queryInsert('INSERT INTO '.$this->config['table'].' (parent_id, name, link, content, active, priority)
				VALUES (?, ?, ?, ?, 1, ?)', array($parent_id, $name, $link, $content, $pr));
		}
		if($_FILES['header']['size']>0){
			$file = $_FILES['header'];	
			if($this->file_types[$file['type']]){
				$ext = $this->file_types[$file['type']];
				$img_src = '/'.$this->id.'/img_src.'.$ext;
				$file_path = $this->config['images_path'].$img_src;
				if(!is_dir(CLIENT_PATH.$this->config['images_path'].'/'.$this->id)){
					$this->mkdirRecursive($this->config['images_path'].'/'.$this->id);
				}
				$r = Db::queryOne('SELECT img_src FROM '.$this->config['table'].' WHERE id='.$this->id);
				if($r['img_src']){
					$this->deleteImage($this->config['images_path'].$r['img_src']);
				}
				copy($file['tmp_name'], CLIENT_PATH.$file_path);
				$size = getimagesize(CLIENT_PATH.$file_path);
				if($size[0] != $this->config['img_width'] || $size[1] != $this->config['img_height']){
					$this->resizeImage(CLIENT_PATH.$file_path, $this->config['img_width'], $this->config['img_height'], 100, 1);
				}
				Db::queryExec('UPDATE '.$this->config['table'].' SET img_src=? WHERE id=?', array($img_src, $this->id));
			} else {
				$this->ret['warning'] .= 'неизвестный тип файла: '.$file['type'];
				return $this->editItem();
			}
		}
		if($_REQUEST['delete_file']){
			$r = Db::queryOne('SELECT img_src FROM '.$this->config['table'].' WHERE id='.$this->id);
			if($r['img_src']){
				$this->deleteImage($this->config['images_path'].$r['img_src']);
				Db::queryExec('UPDATE '.$this->config['table'].' SET img_src="" WHERE id='.$this->id);
			}
		}
		$this->deleteCache('Gallery');
		$r = Db::queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE active=1 AND parent_id='.$parent_id);
		if($r['cnt']){
			Db::queryExec('UPDATE '.$this->config['parent_table'].' SET video_size='.$r['cnt'].' WHERE id='.$parent_id);
		}
		if(!$_REQUEST['reload']){
			$this->ret['warning'] .= '<center><b>элемент изменен</b></center>';
		}
	}
	
	function setId($id){
		if((int)$id){
			$this->id = (int)$id;
		}
	}
	
	function delItem() {
		if($this->id){
			$ret = '<p align=center><b><font color="red">Вы уверены?</font></b></p>';
			$ret .= '<p align=center><a href="?action=delItem2&catId='.$this->catId.'&id='.$this->id.'&v=1"><b>[Да]</b></a>&nbsp;&nbsp;<a href="?action=show&catId='.$this->catId.'&v=1"><b>[Нет]</b></a></p>';
		}
		return $ret;
	}
	
	function delItem2($id = 0) {
		$id = $id ? $id : $this->id;
		if($id){
			$item = $this->_getItem($id);
			if($item['img_src']){
				$this->deleteImage($this->config['images_path'].$item['img_src']);
			}
			Db::queryExec('DELETE FROM '.$this->config['table'].' WHERE id='.$id);
			$this->deleteCache('Gallery');
			$r = Db::queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE active=1 AND parent_id='.$item['parent_id']);
			if($r['cnt']){
				Db::queryExec('UPDATE '.$this->config['parent_table'].' SET video_size='.$r['cnt'].' WHERE id='.$item['parent_id']);
			}
			$this->ret['warning'] = 'Элемент удален';
		}
		return $id;
	}
	
	function showHideItem() {
		if($this->id){
			Db::queryExec('UPDATE '.$this->config['table'].' SET active=IF(active=1, 0, 1) WHERE id='.$this->id);
			$this->deleteCache('Gallery');
			$item = $this->_getItem($this->id);
			$r = Db::queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE active=1 AND parent_id='.$item['parent_id']);
			if($r['cnt']){
				Db::queryExec('UPDATE '.$this->config['parent_table'].' SET video_size='.$r['cnt'].' WHERE id='.$item['parent_id']);
			}
		}
	}
	
	function itemChangePriority() {
		if($this->id){
			$item = $this->_getItem($this->id);
			$el = Db::query('SELECT id, priority FROM '.$this->config['table'].' WHERE parent_id='.$item['parent_id'].' ORDER BY priority');
			foreach($el as $k => $v){
				if($v['id'] == $this->id){ 
					$pos = $k;
					$pr = $v['priority'];
				}
			}
			$n_id = 0;
			$n_pr = 0;
			if((int)$_REQUEST['p']){
				$n_pos = $pos - 1;
				if($n_pos >= 0){
					$n_id = $el[$n_pos]['id'];
					$n_pr = $el[$n_pos]['priority'];
				}
			} else {
				$n_pos = $pos + 1;
				if($n_pos < sizeof($el)){
					$n_id = $el[$n_pos]['id'];
					$n_pr = $el[$n_pos]['priority'];
				}
			}
			if($n_id){
				Db::queryExec('UPDATE '.$this->config['table'].' SET priority='.$n_pr.' WHERE id='.$this->id);
				Db::queryExec('UPDATE '.$this->config['table'].' SET priority='.$pr.' WHERE id='.$n_id);
			}
		}
	}
	
	function showPrevItems() {
		$ret = '';
		$items = $this->_getItems();
		if($items){
			$ret .= $this->_getPages();
			$ret .= '<div class="elements" style="overflow: hidden;">';
			foreach($items as $v){
				$img = '<img src="'.$this->config['images_path'].$v['img_src'].'" width="233" height="175" border="0" alt="'.$v['name'].'" />';
				$adm = '<input type="button"  onclick="window.opener.getVideo(\''.$_REQUEST['idArea'].'\', '.$v['id'].', '.$v['parent_id'].', \''.$v['name'].'\'); window.close();" value=">>" class="buttons" />';
				$ret .= '<table class="oneelem hovered">
			    <tr>
				<td rowspan="2">'.$img.'</td>
				<td rowspan="2" style="padding-left:10px;"></td>
				<td valign="top" style="width: 100%;"><b>'.$v['name'].'</b></td>
				<td rowspan="2" nowrap style="padding-right: 10px;">'.$adm.'</td>
			    </tr>
			    <tr>
				<td>'.$v['note'].'</td>
			    </tr>
			</table>';
			}
			$ret .= '</div>';
		}
		return $ret;
	}
	
	function moveItem() {
		return '<div class="admin_content">'.$this->_getMoveForm().'</div>';
	}
	
	function moveItem2(){
		$ret = $this->catId;
		if($this->id){
			Db::queryExec('UPDATE '.$this->config['table'].' SET parent_id=? WHERE id=?', array((int)$_REQUEST['move_to'], $this->id));
		}
		if((int)$_REQUEST['return_to'] == 2){
			$ret = (int)$_REQUEST['move_to'];
		}
		return $ret;
	}
	
	function _getMoveForm(){
		$ret = '';
		if($this->id){
			$item = $this->_getItem($this->id);
			$r = Db::query('SELECT id, parent_id, name FROM '.$this->config['parent_table']);
			$cat = array();
			foreach ($r as $v){
				$cat[$v['id']] = $v;
			}
			foreach ($cat as $k => $v){
				$cat[$v['parent_id']]['children'][$k] = &$cat[$k];
			}
			$cArr[] = $cat[0];
			$category = '<select class="textfield" name="move_to">'.$this->_getArrayTreeOpt($cArr, $item['parent_id']).'</select>';
			if(ob_start()){
				include'include/item_move_form.html';
				$ret .= ob_get_clean();
			}
		}
		return $ret;
	}
	
	function itemShowOne(){
		
	}
	
	function showItems() {
		$ret = '';
		$items = $this->_getItems();
		if($items){
			if((int)$_REQUEST['window']){
				if($this->id){
					$it = $this->_getItem($this->id);
				} else {
					$it = reset($items);
				}
				$size = sizeof($items);
				foreach($items as $k => $v){
					$num = $k + 1;
					if($v['id'] == $it['id']){
						$prev = ($k > 0) ? $items[$k-1]['id'] : 0;
						$next = ($k < $size) ? $items[$k+1]['id'] : 0;
						$_SESSION['next'] = $next;
						$_SESSION['prev'] = $prev;
						$nLinks .= '<span class=z><font style="font-weight:bold;font-size:12px;">&nbsp;'.$num.'&nbsp;</font></span>';
					} else {
						$nLinks .= '&nbsp;<a href="?action=showItems&catId='.$this->catId.'&id='.$v['id'].'&window=1&v=1"><b class="title"><u>'.$num.'</u></b></a>&nbsp;';
					}
				}
				$nPrev = $prev ? '<a href="?action=showItems&catId='.$this->catId.'&id='.$prev.'&window=1&v=1"> <b>предыдущая << </b> </a>' : '';
				$nNext = $next ? '<a href="?action=showItems&catId='.$this->catId.'&id='.$next.'&window=1&v=1"><b> >>  следующая</b> </a>' : '';
				$nav = $nPrev.' &nbsp; '.$nNext.'<hr>'.$nLinks;
				$name = $it['name'];
				$img = '';
				if($it['img_src']){
					$img = '<img id="slide" src="'.$this->config['images_path'].$it['img_src'].'" width="'.$this->config['img_width'].'" height="'.$this->config['img_height'].'" border="0" alt="'.$name.'">';
				}
				if(ob_start()){
					include'include/video_show_window.html';
					$ret .= ob_get_clean();
				}
			} else {
				$wWidth =  + $this->config['wopenX3_offSET'];
				$wHeight =  + $this->config['wopenY3_offSET'];
				$dHeight = $this->config['img_height'] + 65;
				$dWidth = $this->config['img_width'] + 10;
				$ret .= '<div>'.$this->_getPages().'<div style="overflow: hidden;">';
				foreach($items as $v){
					$link = $this->_itemAdminLinks($v);
					$name = $v['name'];
					$img = '';
					if($v['img_src']){
						$img = '<img src="'.$this->config['images_path'].$v['img_src'].'" width="'.$this->config['img_width'].'" height="'.$this->config['img_height'].'" border="0" alt="'.$name.'" />';
					}
					$ret .= '<div class="floatingbox" style="width:'.$dWidth.'px; height:'.$dHeight.'px;">
			<table width="100%" height="100%" cellpadding="0" cellspacing="0">
			<tr>
				<td align="center">'.$link.'</td>
			</tr>
			<tr>
				<td align="center" valign="middle">'.$img.'</td>
			</tr>
			<tr>
				<td align="center">'.$name.'</td>
			</tr>
			</table>
			</div>';
				}
				$ret .= '</div></div>';
			}
		}
		return $ret; 
	}
	
	function _getItems(){
		$start = (int)$_REQUEST['page'] * $this->config['items_on_page'];
		$r = Db::query('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE parent_id='.$this->catId);
		$this->iCnt = $r['cnt'];
		return Db::query('SELECT * FROM '.$this->config['table'].' WHERE parent_id='.$this->catId.' ORDER BY priority LIMIT '.$start.', '.$this->config['items_on_page']);
	}
	
	function getAllItemsId(){
		return Db::query('SELECT id FROM '.$this->config['table'].' WHERE parent_id='.$this->catId);
	}
	
	function _getPages(){
		$page = (int)$_REQUEST['page'];
		$start = $page * $this->config['items_on_page'];
		$count = $this->cnt_n;
		$iter_num = ceil($count / $this->config['items_on_page']);
		if($iter_num <= 1){
			return;
		}
		$pages_iters = floor($page / $this->config['pages_in_line']);
		$first = $pages_iters *  $this->config['pages_in_line'];
		if(($iter_num <= $this->config['pages_in_line']) || ($end >= $iter_num)){
			$end = $iter_num;
		} else {
			$end = $first + $this->config['pages_in_line'];
		}
		if($end >= $iter_num){
			$end = $iter_num;
		}
		$ret = '<center>';
		$i = $first;
		if($_REQUEST['m']){
			$my = '&m='.$_REQUEST['m'].'&y='.$_REQUEST['y'];
		}
		if(($page + 1) > $this->config['pages_in_line']){
			$start = $i - 1;
			$link = 'page='.$start.$my;
			$ret .= '<a href="?'.$link.'&v=1"><b>назад</b></a> |';
		}
		while ($i < $end){
			$start = $i;
			$link = 'page='.$start.$my;
			$step = $i + 1;
			if ($i != $page){
				$ret .= '<a href="?'.$link.'&v=1"><b>$step</b>|</a>';
			} else {
				$ret .= '<span><b class=z>'.$step.'</b></span>|';
			}
			$i++;
		}
		if($iter_num > $end){
		    $start = $i;
			$link = 'page='.$start.$my;
			$ret .= '<a href="?'.$link.'&v=1"><b>далее</b></a>';
		}
		$ret .= '</center><hr>';
		return $ret;
    }
	
	function _itemAdminLinks($item){
		$page = (int)$_REQUEST['page'] ? '&page='.(int)$_REQUEST['page'] : '';
		$ret = '<div class="admin_links">';
		$ret .=	'<a href="?catId='.$this->catId.'&action=showHideItem&id='.$item['id'].$page.'&v=1">'.($item['active'] ? '<img src="/admin/images/admin_hide.gif" width="16" height="16" border="0" alt="Спрятать" />' : '<img src="/admin/images/admin_show.gif" width="16" height="16" border="0" alt="Показать" />').'</a>';
		$ret .= '<a href="?catId='.$this->catId.'&action=delItem&id='.$item['id'].$page.'&v=1"><img src="/admin/images/admin_delete.gif" width="16" height="16" border="0" alt="Удалить" /></a>';
		$ret .= '<a href="?catId='.$this->catId.'&action=moveItem&id='.$item['id'].$page.'&v=1"><img src="/admin/images/admin_move.gif" width="16" height="16" border="0" alt="Переместить" /></a>';
		$ret .= '<a href="?catId='.$this->catId.'&action=editItem&id='.$item['id'].$page.'&v=1"><img src="/admin/images/admin_edit.gif" width="16" height="16" border="0" alt="Редактировать" /></a>';
		$ret .= '<a href="?catId='.$this->catId.'&action=itemChangePriority&id='.$item['id'].$page.'&p=1&v=1"><img src="/admin/images/admin_up.gif" width="16" height="16" border="0" alt="Увеличить приоритет" /></a>';
		$ret .= '<a href="?catId='.$this->catId.'&action=itemChangePriority&id='.$item['id'].$page.'&p=0&v=1"><img src="/admin/images/admin_down.gif" width="16" height="16" border="0" alt="Уменьшить приоритет" /></a>';
		$ret .= '</div>';
		return $ret;
	}
}
?>
