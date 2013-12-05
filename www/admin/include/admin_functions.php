<?php
namespace admin{
	class AdminFunctions{
		protected 
		$fTypes=array(
			'image/gif'=>'gif',	
			'image/jpeg'=>'jpg',
			'image/pjpeg'=>'jpg',
			'image/png'=>'png',
			'image/x-png'=>'png'
		);

		public function insertPreviews($content, $cfg = array()){
			if(!$content || !$C = $this->Core->getClass(array(\Core::CLASS_NAME => 'FotoObject', \Core::MODULE => 'gallery', \Core::ADMIN => true))){
				return;
			}
			include ADMIN_PATH.'/include/replacePreview.php';
			$config = array(
				'preview_children' => 'gallery_foto',
				'preview_root' => '/files/gallery/foto',
				'preview_win_x_offset' => 40,
				'preview_win_y_offset' => 180,
				'preview_win_x_min' => 400,
				'preview_win_y_min' => 400,
				'open' => '[preview][',
				'close' => '][/preview]'
			);
			$config = array_merge($config, $cfg);
			
			$lOpen = strlen($config['open']);
			$lClose = strlen($config['close']);
			$pos = 0;
			
			while($pos = strpos($content, $config['open'], $pos + 1)){
				$end = strpos($content, $config['close'], $pos+1);
				$id = substr($content, $pos+ $lOpen, ($end - $pos - $lClose+1));
				$content = substr($content, 0, $pos).getPreview($id, $C, $config) . substr($content, $end + $lClose);
			}
			return $content;
		}

		public function insertSlide($cont) {
			if(!$cont){
				return;
			}
			$config = array(
				'foto_table' => 'gallery_foto',
				'images_path' => '/files/gallery/foto'
			);
			$open = '[SlideShow][';
			$close = '][/SlideShow]';
			$openl = strlen($open);
			$closel = strlen($close);
			while($pos = strpos($cont, $open, $pos + 1)){
				$posEnd = strpos($cont, $close, $pos + 1);
				$slideId = substr($cont, $pos + $openl, ($posEnd - $pos - $closel + 1));
				$insCont = '';
				if((int)$slideId){
					$insCont .= '<div class="slide_show"><ul>';
					$SQL = 'SELECT id, name, img_small_src, img_big_src FROM gallery_foto WHERE parent_id=? AND active=1 ORDER BY priority';
					$slImgs = $this->Db->query($SQL, array($slideId));
					foreach($slImgs as $key => $slide){
						$imgSrc = $config['images_path'].$slide['img_small_src'];
						$insCont .= '<li><a href="'.$$config['images_path'].$slide['img_big_src'].'" target="details" title="'.$slide['name'].'" jqLink="'.$config['images_path'].$slide['img_big_src'].'"><img src="'.$imgSrc.'" border="0" alt="'.$slide['name'].'" /></a></li>';
					}
					$insCont .= '</ul></div>';
				}
				$cont = substr($cont, 0, $pos).$insCont.substr($cont, $posEnd + $closel);
			}
			return $cont;
		}

		public function deleteImage($path){
			$nPath = $this->Util->getrealpath($path);
			if(is_file($nPath)){
				unlink($nPath);
				$this->deletePreviewFiles($path);
			}
		}

		public function deletePreviewFiles($path){
			$d = dir(CLIENT_PATH.'/preview/');
			$path = str_replace(CLIENT_PATH, '/', $path);
			while ($dir = $d->read()){
				if(is_dir($d->path.$dir) && $dir != '.' && $dir != '..'){
					if(is_file($d->path.$dir.$path)){
						unlink($d->path.$dir.$path);
					}
				}
			}
		}

		public function resizeImage($path, $width = 0, $height = 0, $quality = 0, $zc = 2){
			if(!$width && !$height){
				return false;
			}
			if(!class_exists('timthumb')){
				include_once $_SERVER['DOCUMENT_ROOT'].'/preview/preview.php';
			}
			$path = str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($path)));            
			$_GET['src'] = $path;
			if($width){
				$_GET['w'] = $width;
			}
			if($height){
				$_GET['h'] = $height;
			}
			$_GET['zc'] = $zc;
			$_GET['cache_dir'] = realpath($_SERVER['DOCUMENT_ROOT'].'/preview/cache/');
			$_GET['notview'] = 1;
			if($quality){
				$_GET['q'] = $quality;
			}
			$pr = new \timthumb();            
			if($pr->start()){
				rename($pr->cachefile, realpath($_SERVER['DOCUMENT_ROOT']).$path);
			}
			$this->clearRecursiveDir('/preview');
		}

		public function clearRecursiveDir($path){
			try{
				$dir = new \RecursiveDirectoryIterator($this->Util->getrealpath($path), \FilesystemIterator::NEW_CURRENT_AND_KEY | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
				foreach ($dir as $v){
					if($v->isDir()){
						$path = $v->__toString();
						if(sizeof(scandir($path)) > 2){
							$this->clearRecursiveDir($path);
						} else {
							rmdir($path);
							if(sizeof(scandir($v->getPath())) == 2){
								rmdir($v->getPath());
							}
						}
					}
				}
			} catch(\UnexpectedValueException $e){
				echo $e->getMessage();
			}
		}

		public function clearRecursiveAll($path){
			$rPath = $this->Util->getrealpath($path);
			if(is_dir($rPath)){
				try{
					$dir = new \RecursiveDirectoryIterator($rPath, \FilesystemIterator::NEW_CURRENT_AND_KEY | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
					foreach ($dir as $v){
						if($v->isDir()){
							$this->clearRecursiveAll($v->getPath().'/'.$v->getBasename());
						} elseif($v->isFile()) {
							unlink($v->getPath().'/'.$v->getFilename());
						}
					}
				} catch(\UnexpectedValueException $e){
					echo $e->getMessage();
				}
				return rmdir($rPath);
			} else {
				return false;
			}
		}

		public function saveImageArr($path){
			$files = $this->Util->getImagesArray($path);
			if($_REQUEST['image']){
				$rPath = $this->Util->getrealpath($path);
				if(!is_dir($rPath)){
					mkdir($rPath, 0777, true);
				}
				foreach($_REQUEST['image'] as $k=>$v){
					if($_REQUEST['url'][$k]){
						$url = $_REQUEST['url'][$k];
						if($size = getimagesize($url)){
							if($this->fTypes[$size['mime']]){
								$ext = $this->fTypes[$size['mime']];
								$file_path = $rPath.'/'.$k.'_img.'.$ext;
								if($files[$k]){
									$this->deleteImage($path.'/'.$files[$k]);
								}
								copy($url,CLIENT_PATH.$file_path);
							}
						}
					} elseif($_FILES['files']['size'][$k] > 0) { 
						$file = $_FILES['files'];
						if($this->fTypes[$file['type'][$k]]){
							$ext = $this->fTypes[$file['type'][$k]];
							$file_path = $rPath.'/'.$k.'_img.'.$ext;
							if($files[$k]){
								$this->deleteImage($path.'/'.$files[$k]);
							}
							copy($file['tmp_name'][$k],$file_path);
						}
					}
					if($_REQUEST['delete_img'][$k]){
						$this->deleteImage($path.'/'.$files[$k]);
					}
				}
			}
		}

		function showAdminMenu(){
			$data = array();
			$data['user'] = $this->Core->getClass('Auth')->user;
			if($data['user']['id'] && $this->Core->cfg['admin_menu']){
				$data['admin_menu'] = $this->Core->cfg['admin_menu'];
				ob_start();
				include ADMIN_PATH.'/data/show_admin_menu.html';
				return ob_get_clean();
			} else {
				return '';
			}
		}

		public function replaceAll($Array,$text){
			for($i=0; $i<=count($Array);$i+=2){
				$text = str_replace($Array[$i],$Array[$i+1],$text);
			}
			return $text;
		}

		public function deleteCache($cl){
			$files = array();
			if($this->config['is_main_cache']){
				$files[] = 'main.tmp';
			}
			if($this->config['is_cache']){
				if(is_dir(CLIENT_PATH.'/cache/'.$cl)){
					try{
						$dir = new \RecursiveDirectoryIterator(CLIENT_PATH.'/cache/'.$cl, \FilesystemIterator::NEW_CURRENT_AND_KEY | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
						foreach ($dir as $v){
							if($v->isFile()) {
								$files[] = '/'.$cl.'/'.$v->getBasename();
							}
						}
					} catch(\UnexpectedValueException $e){
						echo $e->getMessage();
					}
				}
			}
			if($this->config['is_main_cache'] || $this->config['is_cache']){
				foreach ($files as $v){
					if(is_file(CLIENT_PATH.'/cache/'.$v)){
						unlink(CLIENT_PATH.'/cache/'.$v);
					}
				}
				if(is_dir(CLIENT_PATH.'/cache/'.$cl)){
					rmdir(CLIENT_PATH.'/cache/'.$cl);
				}
			}
			$this->Util->memcacheFlush($cl);
		}

		public function ru_en_encode($str){
			$strtr_arr = array(
				'А' => 'a',
				'Б' => 'b',
				'В' => 'v',
				'Г' => 'g',
				'Д' => 'd',
				'Е' => 'e',
				'Ё' => 'e',
				'Ж' => 'zh',
				'З' => 'z',
				'И' => 'i',
				'Й' => 'j',
				'К' => 'k',
				'Л' => 'l',
				'М' => 'm',
				'Н' => 'n',
				'О' => 'o',
				'П' => 'p',
				'Р' => 'r',
				'С' => 's',
				'Т' => 't',
				'У' => 'u',
				'Ф' => 'f',
				'Х' => 'h',
				'Ц' => 'c',
				'Ч' => 'ch',
				'Ш' => 'sh',
				'Щ' => 'sh',
				'Ъ' => '',
				'Ы' => 'i',
				'Ь' => '',
				'Э' => 'e',
				'Ю' => 'u',
				'Я' => 'a',
				'а' => 'a',
				'б' => 'b',
				'в' => 'v',
				'г' => 'g',
				'д' => 'd',
				'е' => 'e',
				'ё' => 'e',
				'ж' => 'zh',
				'з' => 'z',
				'и' => 'i',
				'й' => 'j',
				'к' => 'k',
				'л' => 'l',
				'м' => 'm',
				'н' => 'n',
				'о' => 'o',
				'п' => 'p',
				'р' => 'r',
				'с' => 's',
				'т' => 't',
				'у' => 'u',
				'ф' => 'f',
				'х' => 'h',
				'ц' => 'c',
				'ч' => 'ch',
				'ш' => 'sh',
				'щ' => 'sh',
				'ъ' => '',
				'ы' => 'i',
				'ь' => '',
				'э' => 'e',
				'ю' => 'u',
				'я' => 'a',
				' ' => '_',
			);
			return preg_replace('/[^A-z0-9_]/i', '', strtr($str, $strtr_arr));
		}

		public function getArrayTreeOpt($arr, $id, $level = 0){
			$ret = array();
			$add = '';
			for($i = 0; $i < $level; $i++){
				$add .= '-';
			}
			foreach ($arr as $v){
				$name = mb_substr($v['name'], 0, 30, 'UTF-8');
				$ret[] = array('value' => $v['id'], 'select' => $v['id'] == $id ? ' selected' : '', 'text' => $add.$name);
				if($v['children']){
					$ret = array_merge($ret, $this->getArrayTreeOpt($v['children'], $id, $level + 1));
				}
			}
			return $ret;
		}

		function checkRequest($item){
			foreach ($item as $k => $v){
				if($_REQUEST[$k]){
					if(is_array($item[$k]) && is_array($_REQUEST[$k])){
						$item[$k] = $_REQUEST[$k];
					} elseif(!is_array($_REQUEST[$k])){
						if((int)$_REQUEST[$k]){
							$item[$k] = (int)$_REQUEST[$k];
						} else {
							$item[$k] = $_REQUEST[$k];
						}
					}
				}
			}
			return $item;
		}
	}
}
?>
