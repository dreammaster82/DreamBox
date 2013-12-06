<?php
if(file_exists(realpath($_SERVER['DOCUMENT_ROOT']).'/cache'.$_SERVER['REQUEST_URI']) && stppos($_SERVER['REQUEST_URI'], '?') === false){
	include realpath($_SERVER['DOCUMENT_ROOT']).'/cache'.$_SERVER['REQUEST_URI'];
} else {
	include_once 'include/core.php';
	if($CONFIG['no_apache'] && $_REQUEST['req']){
		$arr = explode('/', ltrim($_REQUEST['req'], '/'));
		$m = array_shift($arr);
		if($m == 'admin'){
			$_REQUEST['module'] = array_shift($arr);
			if(strpos($_REQUEST['module'], 'admin_') !== false){
				$_REQUEST['module'] = str_replace('admin_', '', $_REQUEST['module']);
				$_REQUEST['admin'] = 1;
			}
			include_once ADMIN_PATH.'/include/admin_functions.php';
			include_once ADMIN_PATH.'/include/ccontent_admin.php';
			$_REQUEST['admin_section'] = 1;
		} else {
			$_REQUEST['module'] = $m;
		}
		$_REQUEST['req'] = implode('/', $arr);
	}
	if($_REQUEST['module'] == 'basket'){
		$_REQUEST['module'] = 'catalog';
		$_REQUEST['req'] = 'basket/';
	}

	$out = array();
	if($CONFIG['no_apache'] && $_REQUEST['admin_section']){
		$Core = new Core(Core::UTIL | Core::CONNECTED);
		$Core->getClass('Auth')->process();
		$out['index'] = false;
		if($_REQUEST['module']){
			if($_REQUEST['admin']){
				$out['content'] = $Core->process(array(Core::CLASS_NAME => ucfirst($_REQUEST['module']), Core::MODULE => 'admin', Core::ADMIN => true));
			} else {
				$out['content'] = $Core->process(array(Core::ADMIN => true));
			}
		} else {
			include ADMIN_PATH.'/include/admin.php';
			$out['content'] = $Core->process(array(Core::CLASS_NAME => 'Admin', Core::MODULE => 'admin', Core::ADMIN => true));
		}
			$out['js'] = $Core->ret['js_before'].$Core->ret['js_after'];
			unset($Core->ret['js_before']);
			unset($Core->ret['js_after']);
			if($Core->ret){
				foreach ($Core->ret as $k => $v){
					$out[$k] = $v;
					unset($Core->ret[$k]);
				}
			}


		$out['template'] = $out['template'] ? $out['template'] : 'admin';

		if($_REQUEST['window']){
			$out['template'] = 'gallery_window';
		}
		include ADMIN_PATH.'/data/'.$out['template'].'.html';
	} else {
		$Core = new Core();
		$out['index'] = false;
		if($_REQUEST['module']){
			$out['content'] = $Core->process();
			$out['js'] = $Core->ret['js_before'].$Core->ret['js_after'];
			unset($Core->ret['js_before']);
			unset($Core->ret['js_after']);
			if($Core->ret){
				foreach ($Core->ret as $k => $v){
					$out[$k] = $v;
					unset($Core->ret[$k]);
				}
			}
		} elseif($_REQUEST['error']){
			$out['content'] = $Core->getClass('Util')->error(false);
		} else {
			$out['index'] = true;
			include 'include/main.php';
			ob_start();
			include CLIENT_PATH.'/data/main_page.html';
			$out['content'] = ob_get_clean();
			$out['title'] = $Core->globalConfig['title'];
			$out['description'] = $Core->globalConfig['title'];
			$out['keywords'] = $Core->globalConfig['title'];
		}
		$out['template'] = $out['template'] ? $out['template'] : 'content';

		/*---Include Global Items---*/
		include 'include/global.php';

		if((int)$_REQUEST['print']){
			include CLIENT_PATH.'/data/toprint.html';
		} else {
			if((int)$_REQUEST['window']){
				include CLIENT_PATH.'/data/window.html';
			} else {
				if($out['cache_it']){
					mkdir($out['cache_it'], 0775, true);
					ob_start();
					include CLIENT_PATH.'/data/'.$out['template'].'.html';
					file_put_contents(CLIENT_PATH.'/cache'.$_SERVER['REQUEST_URI'], ob_get_clean());
					include CLIENT_PATH.'/cache'.$_SERVER['REQUEST_URI'];
				} else {
					include CLIENT_PATH.'/data/'.$out['template'].'.html';
				}
			}
		}
	}
}
?>
