<?php
namespace core\admin{
	use core\admin as core;
	class Admin extends core\AdminFunctions{

		public $ret, $Auth, $path;

		function checkAlias($alias = '', $id = 0){
		    $alias = $alias ?: $_REQUEST['alias'];
		    $id = $id ?: $_REQUEST['id'];
		    if($alias){
		        $q = $this->Db->query('SELECT * FROM components_refs WHERE aliases=?', array($alias));
		        $notCur = $id ? $q[0] && $q[0]['type_id'] != $id : true;
		        if(count($q) && $notCur) return '{"error":"Не корректный алиас"}';
		        else return '{}';
            } else return '{"error":"Ошибка запроса"}';
        }

		function show(){
			$ret = '';
			if($_REQUEST['logout']){
				unset($_SESSION['menu']);
				unset($_SESSION['admin']);
				header("location: /admin/");
				exit();
			}
			if($_REQUEST['login'] && $_REQUEST['pass']){
				setSession();
				unset($_SESSION['menu']);
				$login = substr($_REQUEST['login'], 0, 32);
				$pass = substr($_REQUEST['pass'], 0, 32);
				$exp = "/[a-zA-Z0-9_\\-]{0,32}/i";
				if(!preg_match($exp, $login) && !preg_match($exp, $pass)){
					return;
				}
				$this->Auth->user = $this->Auth->getUser($login, md5($pass));
				if($this->Auth->user['id']){
					$_SESSION['admin']['hash'] = encode_hash($this->Auth->user['id'], md5($this->Auth->user['id'].$this->Auth->user['login'].$this->Auth->user['permis']));
					error_log(date('d.m.Y H:i')."\t".$login."\t".$_SERVER['REMOTE_ADDR']."\tadmin section logining\n", 3, CLIENT_PATH.'/data/log/log.log');
				}
			}
			if(!$this->Auth->user['id']){
				ob_start();
				include $this->path.'/data/show_admin.html';
				$ret .= ob_get_clean();
			}
			return $ret;
		}

		function clearimg(){
			$ret = '<p align=center><b><font color="red">Очистить кэш изображений?</font></b></p>';
			$ret .= '<p align=center><a href="?action=clearimg2"><b>[Да]</b></a>&nbsp;&nbsp;<a href="?action=show"><b>[Нет]</b></a></p>';
			return $ret;
		}

		function clearimg2(){
			$ret = '';
			if(is_dir(CLIENT_PATH.'/preview')){
				try{
					$dir = new \RecursiveDirectoryIterator($this->Util->getrealpath(CLIENT_PATH.'/preview'), \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
					foreach ($dir as $v){
						if($v->isDir() && $v->getBasename() != '.' && $v->getBasename() != '..'){
							$this->clearRecursiveAll($v->getPath().'/'.$v->getBasename());
						}
					}
					$ret .= '<div style="color: red; text-align: center; font-weight: bold;">Кэш изображений очистен</div>';
				} catch(\UnexpectedValueException $e){
					echo $e->getMessage();
				}
			}
			return $ret;
		}

		function converter(){
			global $DATA, $C;
			if(!$this->Auth->user['id']){
				return;
			}
			$out = array();
			include ADMIN_PATH.'/include/converter.php';
			$out['text'] = stripslashes($_REQUEST['text']);
			$out['convert'] = HTML_convert($out['text']);
			$DATA = parseUrlFromData($DATA);
			if($C = $this->Core->getClass(array('Gallery', 'gallery', true)) && method_exists($C, 'saveImagesFromData')){
				$files = $C->saveImagesFromData($DATA['images'], $DATA['url']);
			} else {
				$files = $this->saveImagesFromData($DATA['images'], $DATA['url']);
			}
			if($files){
				foreach ($files as $k => $v){
					$inside = '';
					foreach ($v as $k1 => $v1){
						$inside .= $k1.'="'.$v1.'" ';
					}
					$out['convert'] = str_replace('<img['.$k.']>', '<img '.$inside.'/>', $out['convert']);
				}
			} else {
				$out['convert'] = preg_replace('/<img\[[0-9]+\]>( )?/m', '', $out['convert']);
			}
			ob_start();
			include $this->path.'/data/converter_admin.html';
			return ob_get_clean();
		}
		
		function saveImagesFromData($imgArr, $url = array()){
			if(!$imgArr){
				return array();
			}
			if(!file_exists(CLIENT_PATH.'/files/upload')){
				mkdir(CLIENT_PATH.'/files/upload', 0775, true);
			}
			if(!file_exists(CLIENT_PATH.'/files/tmp')){
				mkdir(CLIENT_PATH.'/files/tmp', 0775, true);
			}
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			foreach ($imgArr as $k => $v){
				if($v['src']){
					$v['src'] = ltrim($v['src'], '/');
					$realUrl = '';
					if(strpos($v['src'], 'http://') !== false){
						if(@get_headers($v['src'])){
							$realUrl = $v['src'];
						}
					} else {
						if($url){
							foreach ($url as $v1){
								if(@get_headers($v1.$v['src'])){
									$realUrl = $v1.$v['src'];
									break;
								}
							}
						}
					}
					if($realUrl){
						$locName = $k.'_img_'.date('Y_m_d').'_'.time();
						if($f = file_get_contents($realUrl)){
							file_put_contents(CLIENT_PATH.'/files/tmp/'.$locName, $f);
							unset($f);
							$ext = finfo_file($finfo, CLIENT_PATH.'/files/tmp/'.$locName);
							if(in_array($ext, array_keys($this->fTypes))){
								rename(CLIENT_PATH.'/files/tmp/'.$locName, CLIENT_PATH.'/files/upload/'.$locName.'.'.$this->fTypes[$ext]);
								$imgArr[$k]['src'] = '/files/upload/'.$locName.'.'.$this->fTypes[$ext];
							} else {
								unlink(CLIENT_PATH.'/files/tmp/'.$locName);
								unset($imgArr[$k]);
							}
						}
					} else {
						unset($imgArr[$k]);
					}
				}
			}
			finfo_close($finfo);
			return $imgArr;
		}

		function process(){
			global $MODULE;
			$this->ret['title'] = 'Администрирование сайта';
			$this->Auth = $this->Core->getClass('Auth');
			$this->path = ADMIN_PATH;
			$action = $_REQUEST['action'];
			if(!is_dir(CLIENT_PATH.'/data/log/')){
				mkdir(CLIENT_PATH.'/data/log/', 0775, true);
			}
			$action = method_exists($this, $action) ? $action : 'show';
			error_log(date('d.m.Y H:i').'	'.$this->Auth->user['login'].'	action='.$action.' id='.$this->id.'	'.($_REQUEST['what'] ? 'what='.$_REQUEST['what'].' ' : '').$MODULE.' operations'."\n", 3, CLIENT_PATH.'/data/log/log.log');
			$ret = $this->$action();
			$this->ret['menu'] = $this->showAdminMenu();
			return $ret;
		}		
	}
}
?>