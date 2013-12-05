<?php
namespace admin{
	class FotoObject extends CContent{
		protected $config = array(
			'table' => 'gallery_foto',
			'parent_table' => 'gallery',
			'small_pic_width' => '800',
			'small_pic_height' => '500',
			'small_pic_quality' =>'100',
			'big_pic_width' => '1000',
			'big_pic_height' => '800',
			'big_pic_quality' => '80',
			'big_to_cadr_width' => '500',
			'big_to_cadr_height' => '500',
			'wopenX2' => '435',
			'wopenY2' => '435',
			'wopenX3' => '400',
			'wopenY3' => '400',
			'wopenX2_offSET' => '100',
			'wopenY2_offSET' => '105',
			'wopenX3_offSET' => '50',
			'wopenY3_offSET' => '80',
			'items_on_page' => 18,
			'pages_in_line' => 25,
			'reqId' => 'id'
		), $id;
		
		private $catId;

		public $ret;

		function __construct($m = ''){
			parent::__construct(__CLASS__, $m);
			$this->catId = (int)$_REQUEST['cId'];
			$this->config['files_path'] .= '/foto';
		}

		function adminLinks($item){
			$ret = array();
			$addArr = array();
			if((int)$_REQUEST['page']){
				$addArr[] = 'page='.(int)$_REQUEST['page'];
			}
			$addArr[] = 'cId='.$this->catId;
			if($addArr){
				$add = '&'.implode('&', $addArr);
			} else {
				$add = '';
			}
			if((int)$_REQUEST['preview']){
				$ret[] = array('type' => 'button', 'name' => '', 'text' => '>>', 'onclick' => 'window.opener.preview_insert(\''.$_REQUEST['idArea'].'\', '.$item['id'].', \''.$item['name'].'\'); window.close();');
			} else {
				$ret[] = array('type' => 'link', 'link' => '?action=showHideObjectItem&what='.$item['id'].$add, 'text' => $item['active'] ? '[hide]' : '[show]');
				$ret[] = array('type' => 'link', 'link' => '?action=deleteObjectItem&what='.$item['id'].$add, 'text' => '[del]');
				$ret[] = array('type' => 'link', 'link' => '?action=moveObjectItem&what='.$item['id'].$add, 'text' => '[move]');
				$ret[] = array('type' => 'link', 'link' => '?action=editObjectItem&what='.$item['id'].$add, 'text' => '[edit]');
				$ret[] = array('type' => 'link', 'link' => '?action=priorityObjectItem&pr=0&what='.$item['id'].$add, 'text' => '[+]');
				$ret[] = array('type' => 'link', 'link' => '?action=priorityObjectItem&pr=1&what='.$item['id'].$add, 'text' => '[-]');
			}
			return $ret;
		}

		function setId($id){
			if((int)$id){
				$this->id = (int)$id;
			}
		}

		function editItem() {
			$out = $this->getItem(false, (int)$_REQUEST['what']);
			if($out['img_big_src'] && file_exists(CLIENT_PATH.$this->config['files_path'].$out['img_big_src'])){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$this->config['files_path'].$out['img_big_src']);
				finfo_close($fr);
				$out['big_size'] = filesize(CLIENT_PATH.$this->config['files_path'].$out['img_big_src']);
				$out['big_type'] = $this->fTypes[$fi];
			}
			if($out['img_small_src'] && file_exists(CLIENT_PATH.$this->config['files_path'].$out['img_small_src'])){
				$fr = finfo_open(FILEINFO_MIME_TYPE);
				$fi = finfo_file($fr, CLIENT_PATH.$this->config['files_path'].$out['img_small_src']);
				finfo_close($fr);
				$out['small_size'] = filesize(CLIENT_PATH.$this->config['files_path'].$out['img_small_src']);
				$out['small_type'] = $this->fTypes[$fi];
			}
			$out['category'] = reset($this->Core->getClass(array(\Core::CLASS_NAME => 'Gallery', \Core::MODULE => 'gallery', \Core::ADMIN => true))->getItem(array('name'), $this->catId));
			$video = '<span class="video_obj">';
			if($item['video_id']){
				$v = new VideoObject();
				$vItem = $v->_getItem($item['video_id']);
				if($vItem){
					$video .= '<a href="./?action=showItems&catId='.$vItem['parent_id'].'&id='.$vItem['id'].'&v=1&window=1" target="_blank">'.$vItem['name'].'</a>&nbsp;<input type="checkbox" name="video_del" value="1" />&nbsp;Удалить&nbsp;&nbsp;';
				}
			}
			$video .= '</span>';
			ob_start();
			include $this->path.'/data/admin/edit_item_foto.html';
			return ob_get_clean();
		}

		function saveItem() {
			$item = $this->getItem(false, (int)$_REQUEST['what']);
			if($_REQUEST['small_pic_width']){
				$smWidth = $_SESSION['small_pic_width'] = $_REQUEST['small_pic_width'];
			} else {
				$smWidth = $this->config['small_pic_width'];
			}
			if($_REQUEST['big_pic_width']){
				$bgWidth = $_SESSION['big_pic_width'] = $_REQUEST['big_pic_width'];
			} else {
				$bgWidth = $this->config['big_pic_width'];
			}
			$description = str_replace('\'', '"', trim(stripslashes($_REQUEST['textarea'])));
			$note = str_replace('\'', '"', trim(stripslashes($_REQUEST['note'])));
			$name = trim(stripslashes($_REQUEST['name']));
			$parent_id = $item['parent_id'] ? $item['parent_id'] : $this->catId;
			$video_id = (int)$_REQUEST['video_del'] ? 0 : (int)$_REQUEST['video_id'];
			if($item['id'] && !$_REQUEST['is_copy']){
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET name=?, note=?, description=?, video_id=? WHERE id=?', array($name, $note, $description, $video_id, $item['id']));
			} else {
				$r = $this->Db->queryOne('SELECT MAX(priority) AS m FROM '.$this->config['table'].' WHERE parent_id=?', array($parent_id));
				$pr = $r['m'] + 1;
				$item['id'] = $this->Db->queryInsert('INSERT INTO '.$this->config['table'].' (parent_id, name, note, description, active, priority, video_id)
					VALUES (?, ?, ?, ?, 1, ?, ?)', array($parent_id, $name, $note, $description, $pr, $video_id));
			}
			if(!$_REQUEST['reload']){
				$this->ret['warning'] .= '<center><b>элемент изменен</b></center>';
			}
			$path = $this->config['files_path'];
			$realPath = $this->Util->getrealpath($path);
			if(!is_dir($realPath.'/'.$parent_id)){
				mkdir($realPath.'/'.$parent_id, 0775, true);
			}
			if($_FILES['img1']['size']>0){
				$file = $_FILES['img1'];	
				if(in_array($file['type'], array_keys($this->fTypes))){
					$ext = $this->fTypes[$file['type']];
					$fName = '/'.$parent_id.'/'.$item['id'].'_img1.'.$ext;
					$size = getimagesize($file['tmp_name']);
					$width = $size[0];
					$height = $size[1];
					copy($file['tmp_name'], $realPath.$fName);
					$rArr = $this->getResizeParam($width, $height, 'small');
					if($rArr){
						$width = $rArr[0];
						$height = $rArr[1];
						$this->resizeImage($realPath.$fName, $width, $height, $this->config['small_pic_quality'], $rArr[2]);
					}
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_small_src=?, img_small_x=?, img_small_y=? WHERE id=?', array($fName, $width, $height, $item['id']));
				}
			} elseif($_REQUEST['delete_bimg']){
				if($item['img_small_src']){
					$this->deleteImage($path.$item['img_small_src']);
				}
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_small_src="", img_small_x=0, img_small_y=0 WHERE id=?', array($item['id']));
			}
			if($_FILES['img2']['size']>0){
				$file = $_FILES['img2'];	
				if(in_array($file['type'], array_keys($this->fTypes))){
					$ext = $this->fTypes[$file['type']];
					$fName = '/'.$parent_id.'/'.$item['id'].'_img2.'.$ext;
					$size = getimagesize($file['tmp_name']);
					$width = $size[0];
					$height = $size[1];
					copy($file['tmp_name'], $realPath.$fName);
					if(($width > $this->config['big_pic_width']) || ($height > $this->config['big_pic_height'])){
						if($width > $this->config['big_pic_width']){
							$width = $this->config['big_pic_width'];
							$height = round(($width / $size[0]) * $size[1],0);
						}
						if($height > $this->config['big_pic_height']){
							$height = $this->config['big_pic_height'];
							$width = round(($height / $size[1]) * $size[0],0);
						}
						/*$wh = $width."x".$height;
						$shell_command = "mogrify -strip -resize $wh -antialias -quality {$galleryCFG[big_pic_quality]} $real_file_path";
						shell_exec($shell_command);*/
						$this->resizeImage($realPath.$fName, $width, $height, $this->config['big_pic_quality'], 0);
					}
					//$imagetag = "<img src=\'data/{$id}_img2.$ext\' width=\'$width\' height=\'$height\' border=\'0\' alt=\'\'>";
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_big_src=?, img_big_x=?, img_big_y=? WHERE id=?', array($fName, $width, $height, $item['id']));
				}
			} elseif($_REQUEST['delete_bsimg']){
				if($item['img_big_src']){
					$this->deleteImage($path.$item['img_big_src']);
				}
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_big_src="", img_big_x=0, img_big_y=0 WHERE id=?', array($item['id']));
			}
			$item = $this->getItem(false, $item['id']);
			if($_REQUEST['resize_img2'] && $item['img_big_src']){
				$width = $bgWidth;
				$height = round(($width / $item['img_big_x']) * $item['img_big_y'], 0);
				$this->resizeImage($realPath.$item['img_big_src'], $width, 0, $this->config['big_pic_quality']);
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_big_x=?, img_big_y=? WHERE id=?', array($width, $height, $item['id']));
				$item['img_big_x'] = $width;
				$item['img_big_y'] = $height;
			}
			if($_REQUEST['img2_to_img1'] && !$_REQUEST['img_1'] && !$_FILES['img1']['size'] && ($item['img_big_src'] || ($_FILES['img2']['size'] > 0))){
				if($_FILES['img2']['size'] > 0){
					$file = $_FILES['img2'];	
					if(in_array($file['type'], array_keys($this->fTypes))){
						$ext = $this->fTypes[$file['type']];
						$size = getimagesize($file['tmp_name']);
						$width = $size[0];
						$height = $size[1];
						$fName = '/'.$parent_id.'/'.$item['id'].'_img1.'.$ext;
						$rbfName = $file['tmp_name'];
					} else {
						return;
					}
				} else {
					$rbfName = $realPath.$item['img_big_src'];
					$info = pathinfo($rbfName);
					$fName = '/'.$parent_id.'/'.$item['id'].'_img1.'.$info['extension'];
					$width = $item['img_big_x'];
					$height = $item['img_big_y'];
				}
				copy($rbfName, $realPath.$fName);
				$rArr = $this->getResizeParam($width, $height, 'small');
				if($rArr){
					$width = $rArr[0];
					$height = $rArr[1];
					$this->resizeImage($realPath.$fName, $width, $height, $this->config['small_pic_quality'], $rArr[2]);
				}
				$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_small_src=?, img_small_x=?, img_small_y=? WHERE id=?', array($fName, $width, $height, $item['id']));
				$item['img_small_src'] = $fName;
				$item['img_small_x'] = $width;
				$item['img_small_y'] = $height;
			}
			if($_REQUEST['big_resize_height']){
				$width = $item['img_big_x'];
				$height = $item['img_big_y'];
				$nHeight = $_REQUEST['big_resize_height'];
				if($nHeight < $height){
					$width = $width * $new_height / $height;
					$this->resizeImage($realPath.$item['img_big_src'], $width, $nHeight, 100, 0);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_big_x=?, img_big_y=? WHERE id=?', array($width, $nHeight, $item['id']));
					$item['img_big_x'] = $width;
					$item['img_big_y'] = $nHeight;
				}
			}
			if($_REQUEST['big_cadr_height']){
				$width = $item['img_big_x'];
				$height = $item['img_big_y'];
				$nHeight = $_REQUEST['big_cadr_height'];
				if($nHeight < $height){
					$this->resizeImage($realPath.$item['img_big_src'], $width, $item['img_small_y'], 100, 1);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET img_big_x=?, img_big_y=? WHERE id=?', array($width, $nHeight, $item['id']));
					$item['img_big_x'] = $width;
					$item['img_big_y'] = $nHeight;
				}
			}
			if($_REQUEST['small_pic_width'] && $item['img_small_src']){
				$rArr = $this->getResizeParam($item['img_small_x'], $item['img_small_y'], 'small');
				if($rArr){
					$width = $rArr[0];
					$height = $rArr[1];
					$this->resizeImage($realPath.$item['img_small_src'], $width, $height, $this->config['small_pic_quality'], $rArr[2]);
					$this->Db->queryExec('UPDATE '.$this->config['table'].' SET
						'.$this->config['pref'].'img_small_x=?,
						'.$this->config['pref'].'img_small_y=?
						WHERE '.$this->config['pref'].'id=?',
						array($width, $height, $item['id']));
				}
				$item['img_small_x'] = $width;
				$item['img_small_y'] = $height;
			}
			if($_REQUEST['SET_proportions1']){
				$_SESSION['use_proportions1'] = 1;
				$_SESSION['width1'] = $item['img_small_x'];
				$_SESSION['height1'] = $item['img_small_y'];
			}
			if($_REQUEST['SET_proportions2']){
				$_SESSION['use_proportions2'] = 1;
				$_SESSION['width2'] = $item['img_big_x'];
				$_SESSION['height2'] = $item['img_big_y'];
			}
			$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE active=1 AND parent_id=?', array($parent_id));
			if($r['cnt']){
				$this->Db->queryExec('UPDATE '.$this->config['parent_table'].' SET foto_size='.$r['cnt'].' WHERE id=?', array($parent_id));
			}
			return $item['id'];
		}

		function getResizeParam($width, $height, $type){
			$ret = array();
			if($type == 'small'){
				if(!(int)$_REQUEST['small_resize_height']){
					$type = 0;
					$w = (int)$_REQUEST['small_pic_width'] ? (int)$_REQUEST['small_pic_width'] : $this->config['small_pic_width'];
					$h = $height;
					if((int)$_REQUEST['small_cadr_height'] && (int)$_REQUEST['small_cadr_height'] < $height){
						$type = 1;
						$h = (int)$_REQUEST['small_cadr_height'];
						if(!(int)$_REQUEST['small_pic_width']){
							$w = $width * $h / $height;
							if($w > $this->config['small_pic_width']){
								$w = $this->config['small_pic_width'];
							}
						}
					} else {
						if($width != $w){
							$h = round(($w / $width) * $height, 0);
						}
						if($h > $this->config['small_pic_height']){
							$h = $this->config['small_pic_height'];
							$w = round(($h / $height) * $width, 0);
						}
					}
				} else {
					$type = 0;
					$h = (int)$_REQUEST['small_resize_height'];
					$w = $width * $h / $height;
				}
				$ret = array(0 => $w, 1 => $h, 2 => $type, 'width' => $w, 'height' => $h, 'type' => $type);
			}
			return $ret;
		}

		function deleteItem2() {
			if((int)$_REQUEST['what']){
				$this->delete((int)$_REQUEST['what']);
			}
			return $this->show();
		}

		function delete($id = 0) {
			$id = $id ? $id : $this->id;
			if($id){
				$item = $this->getItem(array('id', 'img_big_src', 'img_small_src', 'parent_id'), $id);
				if($item['img_big_src']){
					$this->deleteImage($this->config['files_path'].$item['img_big_src']);
				}
				if($item['img_small_src']){
					$this->deleteImage($this->config['files_path'].$item['img_small_src']);
				}
				$this->Db->queryExec('DELETE FROM '.$this->config['table'].' WHERE id=?', array($id));
				$this->deleteCache('Gallery');
				$r = $this->Db->queryOne('SELECT COUNT(*) AS cnt FROM '.$this->config['table'].' WHERE active=1 AND parent_id=?', array($item['parent_id']));
				if($r['cnt']){
					$this->Db->queryExec('UPDATE '.$this->config['parent_table'].' SET foto_size=? WHERE id=?', array($r['cnt'], $item['parent_id']));
				}
				$this->ret['warning'] = 'Элемент удален';
			}
			return $id;
		}

		function priorityItem(){
			parent::priorityItem(true);
		}

		function showPrevItems($out, $parId = 0) {
			ob_start();
			include $this->path.'/data/admin/show_prev_items_foto.html';
			return ob_get_clean();
		}

		function moveItem($pId = 0){
			$ret = '';
			$data = array();
			if((int)$_REQUEST['what']){
				$data = $this->getItem(array('name', 'type'), (int)$_REQUEST['what']);
				ob_start();
				include $this->path.'/data/admin/move_item_foto_object.html';
				$ret = ob_get_clean();
			}
			return $ret;
		}

		function moveItem2($pId = 0){
			$ret = $pId;
			if($this->id){
				Db::queryExec('UPDATE '.$this->config['table'].' SET parent_id=? WHERE id=?', array((int)$_REQUEST['move_to'], $this->id));
			}
			if((int)$_REQUEST['return_to'] == 2){
				$ret = (int)$_REQUEST['move_to'];
			}
			return $ret;
		}

		function showItems($out, $parId = 0){
			ob_start();
			if((int)$_REQUEST['window']){
				$items = $out;
				if($this->id){
					$out = $this->getItem(false, $this->id);
				} else {
					$out = reset($out);
				}
				$size = sizeof($items);
				foreach($items as $k => $v){
					$num = $k + 1;
					if($v['id'] == $out['id']){
						$prev = ($k > 0) ? $items[$k-1]['id'] : 0;
						$next = ($k < $size) ? $items[$k+1]['id'] : 0;
						$_SESSION['next'] = $next;
						$_SESSION['prev'] = $prev;
						$out['links'] .= '<span class=z><font style="font-weight:bold;font-size:12px;">&nbsp;'.$num.'&nbsp;</font></span>';
					} else {
						$out['links'] .= '&nbsp;<a href="?cId='.$this->catId.'&id='.$v['id'].'&window=1&show_object_items=1"><b class="title"><u>'.$num.'</u></b></a>&nbsp;';
					}
				}
				$nPrev = $prev ? '<a href="?cId='.$this->catId.'&id='.$prev.'&window=1&show_object_items=1"> <b>предыдущая << </b> </a>' : '';
				$nNext = $next ? '<a href="?cId='.$this->catId.'&id='.$next.'&window=1&show_object_items=1"><b> >>  следующая</b> </a>' : '';
				$out['navigate'] = $nPrev.' &nbsp; '.$nNext.'<hr>'.$nLinks;
				$templ = 'show_items_window_foto';
			} else {
				$templ = 'show_items_foto';
				$items = $out;
				$out = array();
				$out['items'] = $items;
				unset($items);
			}
			$arr = [];
			if((int)$_REQUEST['cId']){
				$arr[] = 'cId='.(int)$_REQUEST['cId'];
			}
			$out['lnk'] = implode('&', $arr);
			if($parId){
				$out['parent_id'] = $parId;
			}
			include $this->path.'/data/admin/'.$templ.'.html';
			return ob_get_clean();
		}

		function show($parId = 0){
			return $this->showItems($this->getItems($parId));
		}

		function getItems($parId = 0){
			$tData = array('id', 'parent_id', 'name', 'img_big_src', 'img_big_x', 'img_big_y', 'note', 'img_small_src', 'img_small_x', 'img_small_y', 'active');
			return parent::getItems($tData, array('parent_id' => $parId), array('priority'), false, false, false, $this->config['items_on_page']);
		}

		function getAllItemsId($parId = 0){
			return parent::getItems(array('id'), array('parent_id' => $parId));
		}
	}
}
?>